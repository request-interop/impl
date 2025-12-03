<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Impl\RequestFactory;
use RequestInterop\Interface\RequestTypeAliases;
use UploadInterop\Impl\Upload;
use UploadInterop\Interface\UploadTypeAliases;

/**
 * @phpstan-import-type request_cookies_array from RequestTypeAliases
 * @phpstan-import-type upload_files_array from UploadTypeAliases
 * @phpstan-import-type request_body_array from RequestTypeAliases
 * @phpstan-import-type request_query_array from RequestTypeAliases
 * @phpstan-import-type request_server_array from RequestTypeAliases
 * @phpstan-import-type upload_structs_array from UploadTypeAliases
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
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_ADDR' => '127.0.0.1',
        ];
    }

    protected function newRequest(?RequestBodyStream $bodyStream = null) : Request
    {
        $factory = $bodyStream
            ? new RequestFactory(bodyStream: $bodyStream)
            : new RequestFactory();

        return $factory->newRequest();
    }

    public function testNewRequest() : void
    {
        $actual = $this->newRequest();
        $this->assertInstanceof(Request::class, $actual);
    }

    public function testHeaders() : void
    {
        $_SERVER += [
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

        $actual = $this->newRequest();
        $this->assertSame($expect, $actual->headers);
    }

    public function testBody() : void
    {
        $_POST = ['foo' => 'bar'];
        $actual = $this->newRequest();
        $this->assertSame($_POST, $actual->body);
    }

    public function testBodyTypeJson() : void
    {
        $_SERVER += ['CONTENT_TYPE' => 'application/json'];
        $expect = ['foo' => 'bar'];

        $actual = $this->newRequest(
            bodyStream: new RequestBodyStream(
                'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.json',
            ),
        );

        $this->assertSame($expect, $actual->body);
    }

    public function testBodyTypeXml_application() : void
    {
        $_SERVER += ['CONTENT_TYPE' => 'application/xml'];
        $expect = ['foo' => 'bar'];

        $actual = $this->newRequest(
            bodyStream: new RequestBodyStream(
                'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml',
            ),
        );

        $this->assertSame($expect, $actual->body);

    }

    public function testBodyTypeXml_text() : void
    {
        $_SERVER += ['CONTENT_TYPE' => 'text/xml'];
        $expect = ['foo' => 'bar'];

        $actual = $this->newRequest(
            bodyStream: new RequestBodyStream(
                'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml',
            ),
        );

        $this->assertSame($expect, $actual->body);
    }

    public function testMethod() : void
    {
        $actual = $this->newRequest();
        $this->assertSame('GET', $actual->method);

        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'patch';
        $actual = $this->newRequest();
        $this->assertSame('GET', $actual->method);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $actual = $this->newRequest();
        $this->assertSame('PATCH', $actual->method);

        unset($_SERVER['REQUEST_METHOD']);
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Could not determine HTTP method.');
        $this->newRequest();
    }

    public function testUploads(): void
    {
        $_FILES = [
            'foo1' => [
                'error' => 0,
                'name' => 'foo1',
                'full_path' => '',
                'size' => 0,
                'tmp_name' => '',
                'type' => '',
            ],
        ];

        $actual = $this->newRequest();
        $this->assertCount(1, $actual->uploads);
        $this->assertInstanceOf(Upload::class, $actual->uploads['foo1']);
        $this->assertSame('foo1', $actual->uploads['foo1']->name);
    }

    public function testUri() : void
    {
        $_SERVER += [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
            'PHP_AUTH_USER' => 'watterson',
            'PHP_AUTH_PW' => 'bopass',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'baz=dib',
        ];

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

        $actual = $this->newRequest();
        $this->assertSame($expect, (array) $actual->uri);
    }
}
