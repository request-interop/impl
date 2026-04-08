<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use PHPUnit\Framework\Attributes\DataProvider;
use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestTypeAliases;
use UploadInterop\Impl\Upload;
use UploadInterop\Interface\UploadTypeAliases;
use UriInterop\Interface\UriTypeAliases;

/**
 * @phpstan-import-type request_cookies_array from RequestTypeAliases
 * @phpstan-import-type upload_files_array from UploadTypeAliases
 * @phpstan-import-type request_body_array from RequestTypeAliases
 * @phpstan-import-type request_query_array from RequestTypeAliases
 * @phpstan-import-type request_server_array from RequestTypeAliases
 * @phpstan-import-type upload_structs_array from UploadTypeAliases
 * @phpstan-import-type request_headers_array from RequestTypeAliases
 * @phpstan-import-type request_method_string from RequestTypeAliases
 * @phpstan-import-type query_params_array from UriTypeAliases
 */
#[\PHPUnit\Framework\Attributes\BackupGlobals(true)]
abstract class RequestFactoryTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return mixed[]
     */
    public static function provideUriHostAndPort() : array
    {
        return [
            [
                'HTTP_HOST' => 'example.com',
                'host' => 'example.com',
                'port' => null,
            ],
            [
                'HTTP_HOST' => 'example.com:8080',
                'host' => 'example.com',
                'port' => 8080,
            ],
            [
                'HTTP_HOST' => 'example.com',
                'SERVER_PORT' => '8080',
                'host' => 'example.com',
                'port' => 8080,
            ],
            ['SERVER_ADDR' => '8.8.8.8', 'host' => '8.8.8.8', 'port' => null],
            [
                'SERVER_ADDR' => '8.8.8.8',
                'SERVER_PORT' => '8080',
                'host' => '8.8.8.8',
                'port' => 8080,
            ],
            [
                'SERVER_ADDR' => '2001:4860:4860::8888',
                'host' => '[2001:4860:4860::8888]',
                'port' => null,
            ],
            [
                'SERVER_ADDR' => '2001:4860:4860::8888',
                'SERVER_PORT' => '8080',
                'host' => '[2001:4860:4860::8888]',
                'port' => 8080,
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function provideUriPathAndQuery() : array
    {
        return [
            ['path' => '', 'query' => null, 'queryParams' => null],
            [
                'REQUEST_URI' => '/foo/bar?baz=dib',
                'path' => '/foo/bar',
                'query' => 'baz=dib',
                'queryParams' => ['baz' => 'dib'],
            ],
            [
                'REQUEST_URI' => '/foo/bar',
                'QUERY_STRING' => 'baz=dib',
                'path' => '/foo/bar',
                'query' => 'baz=dib',
                'queryParams' => ['baz' => 'dib'],
            ],
            [
                'REQUEST_URI' => '/foo/bar?baz=dib',
                'QUERY_STRING' => 'zim=gir',
                'path' => '/foo/bar',
                'query' => 'zim=gir',
                'queryParams' => ['zim' => 'gir'],
            ],
            [
                'IIS_WasUrlRewritten' => '1',
                'UNENCODED_URL' => '/foo/bar?baz=dib',
                'path' => '/foo/bar',
                'query' => 'baz=dib',
                'queryParams' => ['baz' => 'dib'],
            ],
        ];
    }

    protected function setUp() : void
    {
        $_COOKIE = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'SERVER_ADDR' => '127.0.0.1'];
    }

    abstract protected function newRequest(
        ?RequestBodyStream $bodyStream = null,
    ) : RequestStruct;

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

    public function testBodyStream() : void
    {
        $actual = $this->newRequest();
        $this->assertSame('', (string) $actual->bodyStream);

        $this->assertSame(
            'php://input',
            $actual->bodyStream->metadata['uri'] ?? null,
        );
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
                (string) file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.json',
                ),
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
                (string) file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml',
                ),
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
                (string) file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml',
                ),
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

    public function testUploads() : void
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

    public function testUriScheme() : void
    {
        unset($_SERVER['HTTPS']);
        $expect = 'http';
        $actual = $this->newRequest()->uri;
        $this->assertSame($expect, $actual->scheme);

        $_SERVER['HTTPS'] = 'Off';
        $expect = 'http';
        $actual = $this->newRequest()->uri;
        $this->assertSame($expect, $actual->scheme);

        $_SERVER['HTTPS'] = '1';
        $expect = 'https';
        $actual = $this->newRequest()->uri;
        $this->assertSame($expect, $actual->scheme);

        $_SERVER['HTTPS'] = 'on';
        $expect = 'https';
        $actual = $this->newRequest()->uri;
        $this->assertSame($expect, $actual->scheme);
    }

    #[DataProvider('provideUriHostAndPort')]
    public function testUriHostAndPort(
        string $host,
        ?int $port,
        string ...$server,
    ) : void
    {
        foreach ($server as $key => $val) {
            $_SERVER[$key] = $val;
        }

        $actual = $this->newRequest()->uri;
        $this->assertSame($host, $actual->host);
        $this->assertSame($port, $actual->port);
    }

    public function testCannotDetermineUriHostAndPort() : void
    {
        unset($_SERVER['SERVER_ADDR']);
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Could not determine host and port.');
        $this->newRequest();
    }

    /**
     * @param query_params_array $queryParams
     */
    #[DataProvider('provideUriPathAndQuery')]
    public function testUriPathAndQuery(
        string $path,
        ?string $query,
        ?array $queryParams,
        string ...$server,
    ) : void
    {
        foreach ($server as $key => $val) {
            $_SERVER[$key] = $val;
        }

        $actual = $this->newRequest()->uri;
        $this->assertSame($path, $actual->path);
        $this->assertSame($query, $actual->query);
        $this->assertSame($queryParams, $actual->queryParams);
    }
}
