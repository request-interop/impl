<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use BadMethodCallException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RequestInterop\Interface\Body;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\Upload;
use RequestInterop\Interface\Url;
use UnexpectedValueException;

/**
 * @phpstan-import-type BodyResource from Body
 * @phpstan-import-type UploadsArray from Request
 */
abstract class PsrMapperTestCase extends \PHPUnit\Framework\TestCase
{
    protected Psr17Factory $psrFactory;

    protected PsrMapper $psrMapper;

    protected function setUp() : void
    {
        $this->psrFactory = new Psr17Factory();
        $this->psrMapper = $this->newPsrMapper();
    }

    abstract protected function newPsrMapper() : PsrMapper;

    protected function newPsr() : ServerRequestInterface
    {
        $bodyFile = __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.json';
        $uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'FakeUpload.txt';
        $uploadSize = strlen((string) file_get_contents($uploadFile));

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
            'QUERY_STRING' => 'foo-query=foo-query-value',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/endpoint',
        ];

        return $this->psrFactory
            ->createServerRequest('POST', 'https://bouser:bopass@example.com/api/endpoint?foo-query=foo-query-values', $server)
            ->withCookieParams([
                'foo-cookie' => 'foo-cookie-value',
            ])
            ->withQueryParams([
                'foo-query' => 'foo-query-value'
            ])
            ->withHeader('host', 'example.com')
            ->withHeader('content-type', 'application/json')
            ->withBody($this->psrFactory->createStreamFromFile($bodyFile))
            ->withParsedBody((array) json_decode((string) file_get_contents($bodyFile), true))
            ->withUploadedFiles([
                'foo-upload' => $this->psrFactory->createUploadedFile(
                    stream: $this->psrFactory->createStreamFromFile($uploadFile),
                    size: $uploadSize,
                    error: 0,
                    clientFilename: 'foo.txt',
                    clientMediaType: 'text/plain',
                ),
                'bar-upload' => $this->psrFactory->createUploadedFile(
                    stream: $this->psrFactory->createStreamFromFile($uploadFile),
                    size: $uploadSize,
                    error: 0,
                    clientFilename: 'bar.txt',
                    clientMediaType: 'text/plain',
                ),
                'baz-uploads' => [
                    0 => $this->psrFactory->createUploadedFile(
                        stream: $this->psrFactory->createStreamFromFile($uploadFile),
                        size: $uploadSize,
                        error: 0,
                        clientFilename: 'baz.txt',
                        clientMediaType: 'text/plain',
                    ),
                ],
            ]);
    }

    public function testFromPsr() : void
    {
        $psr = $this->newPsr();
        $actual = $this->psrMapper->fromPsr($psr);

        $expect = ['foo-cookie' => 'foo-cookie-value'];
        $this->assertSame($expect, $actual->cookies);

        $expect = [];
        $this->assertSame($expect, $actual->files);

        $expect = [
            'host' => 'example.com',
            'content-type' => 'application/json',
        ];
        $this->assertSame($expect, $actual->headers);

        $expect = ['foo' => 'bar'];
        $this->assertSame($expect, $actual->input);

        $expect = ['foo-query' => 'foo-query-value'];
        $this->assertSame($expect, $actual->query);

        $this->assertSame('POST', $actual->method);

        $expect = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
            'QUERY_STRING' => 'foo-query=foo-query-value',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/endpoint',
        ];
        $this->assertSame($expect, $actual->server);

        $actualUploads = $actual->uploads;
        $this->assertCount(3, $actualUploads);

        /** @var Upload */
        $actualUpload = $actualUploads['foo-upload'];
        $this->assertSame('foo.txt', $actualUpload->name);

        /** @var Upload */
        $actualUpload = $actualUploads['bar-upload'];
        $this->assertSame('bar.txt', $actualUpload->name);

        /** @var UploadsArray */
        $actualUploads = $actual->uploads['baz-uploads'];

        /** @var Upload */
        $actualUpload = $actualUploads[0];
        $this->assertSame('baz.txt', $actualUpload->name);

        $expect = [
           'scheme' => 'https',
           'host' => 'example.com',
           'port' => null,
           'user' => 'bouser',
           'pass' => 'bopass',
           'path' => '/api/endpoint',
           'query' => 'foo-query=foo-query-values',
           'fragment' => null,
        ];

        $this->assertSame($expect, (array) $actual->url);

        if ($actual instanceof Body) {
            $this->assertSame((string) $psr->getBody(), (string) $actual);
        }
    }

    public function testFromPsrParsedBody() : void
    {
        $expect = [
            'foo' => [
                'bar' => [
                    'baz' => 'dib',
                ],
            ],
        ];

        $actual = $this->psrMapper->fromPsrParsedBody($expect);
        $this->assertSame($expect, $actual);

        $this->expectException(UnexpectedValueException::CLASS);
        $this->psrMapper->fromPsrParsedBody(['foo' => fopen('php://temp', 'r')]);
    }

    public function testFromPsrQueryParams() : void
    {
        $expect = [
            'foo' => [
                'bar' => [
                    'baz' => 'dib',
                ],
            ],
        ];

        $actual = $this->psrMapper->fromPsrQueryParams($expect);
        $this->assertSame($expect, $actual);

        $this->expectException(UnexpectedValueException::CLASS);
        $this->psrMapper->fromPsrQueryParams(['foo' => fopen('php://temp', 'r')]);
    }

    public function testFromPsrServerParams() : void
    {
        $expect = [
            'foo' => 'bar',
        ];

        $actual = $this->psrMapper->fromPsrServerParams($expect);
        $this->assertSame($expect, $actual);

        $this->expectException(UnexpectedValueException::CLASS);
        $this->psrMapper->fromPsrServerParams(['foo' => fopen('php://temp', 'r')]);
    }

    public function testToPsr() : void
    {
        $expect = $this->newPsr();
        $request = $this->psrMapper->fromPsr($expect);
        $actual = $this->psrMapper->toPsr($request);

        $this->assertSame($expect->getServerParams(), $actual->getServerParams());
        $this->assertSame($expect->getCookieParams(), $actual->getCookieParams());
        $this->assertSame($expect->getQueryParams(), $actual->getQueryParams());
        $this->assertSame($expect->getParsedBody(), $actual->getParsedBody());
        $this->assertSame($expect->getMethod(), $actual->getMethod());
        $this->assertEquals($expect->getUri(), $actual->getUri());
        $this->assertSame($expect->getHeaders(), $actual->getHeaders());

        $actualUploads = $actual->getUploadedFiles();
        $this->assertCount(3, $actualUploads);

        /** @var UploadedFileInterface */
        $actualUpload = $actualUploads['foo-upload'];
        $this->assertSame('foo.txt', $actualUpload->getClientFilename());

        /** @var UploadedFileInterface */
        $actualUpload = $actualUploads['bar-upload'];
        $this->assertSame('bar.txt', $actualUpload->getClientFilename());

        /** @var UploadedFileInterface[] */
        $actualUploads = $actualUploads['baz-uploads'];

        /** @var UploadedFileInterface */
        $actualUpload = $actualUploads[0];
        $this->assertSame('baz.txt', $actualUpload->getClientFilename());

        if ($request instanceof Body) {
            $this->assertSame((string) $expect->getBody(), (string) $actual->getBody());
        }
    }

    public function testNewBody() : void
    {
        /** @var BodyResource */
        $bodyResource = fopen('php://temp', 'r');
        $this->expectException(BadMethodCallException::CLASS);
        $this->psrMapper->newBody($bodyResource);
    }
}
