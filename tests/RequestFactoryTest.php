<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Impl\RequestFactory;
use RequestInterop\Interface\RequestTypeAliases;
use RequestInterop\Interface\RequestStruct;
use StreamInterop\Impl\ReadonlyFileStream;
use UploadInterop\Impl\Upload;
use UploadInterop\Interface\UploadTypeAliases;
use UriInterop\Impl\ReadonlyUri;

/**
 * @phpstan-import-type request_cookies_array from RequestTypeAliases
 * @phpstan-import-type upload_files_array from UploadTypeAliases
 * @phpstan-import-type request_body_array from RequestTypeAliases
 * @phpstan-import-type request_query_array from RequestTypeAliases
 * @phpstan-import-type request_server_array from RequestTypeAliases
 * @phpstan-import-type upload_struct_array from UploadTypeAliases
 * @phpstan-import-type request_headers_array from RequestTypeAliases
 * @phpstan-import-type request_method_string from RequestTypeAliases
 */
#[\PHPUnit\Framework\Attributes\BackupGlobals(true)]
class RequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $_COOKIE = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
    }

    protected function newRequestFactory() : RequestFactory
    {
        return new RequestFactory();
    }

    public function testNewRequest() : void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'FAKE',
            'SERVER_ADDR' => '127.0.0.1',
        ];

        $actual = $this->newRequestFactory()->newRequest();
        $this->assertInstanceof(Request::class, $actual);
    }

    public function testInput() : void
    {
        $actual = $this->newRequestFactory()->input('php://input');
        $this->assertInstanceOf(ReadonlyFileStream::class, $actual);
    }

    public function testHeaders() : void
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_FOO_BAR_BAZ' => 'dib,zim,gir',
            'NON_HTTP_HEADER' => 'should not show',
            'CONTENT_LENGTH' => '123',
            'CONTENT_TYPE' => 'text/plain',
        ];

        $expect = [
            'host' => 'example.com',
            'foo-bar-baz' => 'dib,zim,gir',
            'content-length' => '123',
            'content-type' => 'text/plain',
        ];

        $actual = $this->newRequestFactory()->headers($server);
        $this->assertSame($expect, $actual);
    }

    public function testBody() : void
    {
        $_POST = ['foo' => 'bar'];
        $factory = $this->newRequestFactory();

        $actual = $factory->body(
            headers: [],
            input: $factory->input('php://input'),
        );

        $this->assertSame($_POST, $actual);
    }

    public function testBodyType() : void
    {
        $factory = $this->newRequestFactory();

        $actual = $factory->bodyType(
            headers: [],
        );

        $this->assertNull($actual);

        $actual = $factory->bodyType(
            headers: ['content-type' => 'TEXT/plain']
        );

        $expect = 'text/plain';
        $this->assertSame($expect, $actual);
    }

    public function testBodyTypeJson() : void
    {
        $factory = $this->newRequestFactory();
        $expect = ['foo' => 'bar'];

        $actual = $this->newRequestFactory()->body(
            headers: ['content-type' => 'application/json'],
            input: $factory->input(
                'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.json'
            ),
        );

        $this->assertSame($expect, $actual);
    }

    public function testBodyTypeXml() : void
    {
        $factory = $this->newRequestFactory();
        $expect = ['foo' => 'bar'];

        $actual = $this->newRequestFactory()->body(
            headers: ['content-type' => 'application/xml'],
            input: $factory->input(
                'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml'
            ),
        );

        $this->assertSame($expect, $actual);

        $actual = $this->newRequestFactory()->body(
            headers: ['content-type' => 'text/xml'],
            input: $factory->input(
                'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml'
            ),
        );

        $this->assertSame($expect, $actual);
    }

    public function testMethod() : void
    {
        $factory = $this->newRequestFactory();
        $server = ['REQUEST_METHOD' => 'POST'];
        $headers = ['x-http-method-override' => 'patch'];
        $actual = $factory->method($server, $headers);
        $this->assertSame('PATCH', $actual);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Could not determine HTTP method.');
        $factory->method(server: [], headers: []);
    }

    public function testUploads(): void
    {
        $files = [
            'foo1' => [
                'error' => 0,
                'name' => 'foo1',
                'full_path' => '',
                'size' => 0,
                'tmp_name' => '',
                'type' => '',
            ],
        ];

        $actual = $this->newRequestFactory()->uploads($files);
        $this->assertCount(1, $actual);
        $this->assertInstanceOf(Upload::class, $actual['foo1']);
        $this->assertSame('foo1', $actual['foo1']->name);
    }

    public function testUri() : void
    {
        $actual = (array) $this->newRequestFactory()->uri(server: [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
            'PHP_AUTH_USER' => 'watterson',
            'PHP_AUTH_PW' => 'bopass',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'baz=dib',
        ]);

        $expect = [
            'queryParams' => [
                'baz' => 'dib',
            ],
            'scheme' => 'https',
            'username' => null,
            'password' => null,
            'host' => 'example.com',
            'port' => 443,
            'path' => '/foo/bar',
            'query' => 'baz=dib',
            'fragment' => null,
        ];

        $this->assertSame($expect, $actual);
    }
}
