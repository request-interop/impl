<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

class RequestUriFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected RequestUriFactory $requestUriFactory;

    protected function setUp() : void
    {
        $this->requestUriFactory = new RequestUriFactory();
    }

    public function testUri() : void
    {
        $actual = (array) $this->requestUriFactory->newRequestUri(server: [
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

    public function testUriScheme() : void
    {
        $expect = ['scheme' => 'http'];
        $actual = $this->requestUriFactory->getScheme(server: []);
        $this->assertSame($expect, $actual);

        $expect = ['scheme' => 'http'];
        $actual = $this->requestUriFactory->getScheme(server: ['HTTPS' => 'Off']);
        $this->assertSame($expect, $actual);

        $expect = ['scheme' => 'https'];
        $actual = $this->requestUriFactory->getScheme(server: ['HTTPS' => '1']);
        $this->assertSame($expect, $actual);

        $expect = ['scheme' => 'https'];
        $actual = $this->requestUriFactory->getScheme(server: ['HTTPS' => 'on']);
        $this->assertSame($expect, $actual);
    }

    public function testUriHostAndPort() : void
    {
        $expect = ['host' => 'example.com', 'port' => null];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'HTTP_HOST' => 'example.com',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['host' => 'example.com', 'port' => 8080];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'HTTP_HOST' => 'example.com:8080',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['host' => 'example.com', 'port' => 8080];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '8080',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['host' => '8.8.8.8', 'port' => null];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'SERVER_ADDR' => '8.8.8.8',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['host' => '8.8.8.8', 'port' => 8080];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'SERVER_ADDR' => '8.8.8.8',
            'SERVER_PORT' => '8080',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['host' => '[2001:4860:4860::8888]', 'port' => null];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'SERVER_ADDR' => '2001:4860:4860::8888',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['host' => '[2001:4860:4860::8888]', 'port' => 8080];
        $actual = $this->requestUriFactory->getHostAndPort(server: [
            'SERVER_ADDR' => '2001:4860:4860::8888',
            'SERVER_PORT' => '8080',
        ]);
        $this->assertSame($expect, $actual);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Could not determine host and port.');
        $actual = $this->requestUriFactory->getHostAndPort(server: []);
    }

    public function testUriPathAndQuery() : void
    {
        $expect = ['path' => '', 'query' => null];
        $actual = $this->requestUriFactory->getPathAndQuery(server: []);
        $this->assertSame($expect, $actual);

        $expect = ['path' => '/foo/bar', 'query' => 'baz=dib'];
        $actual = $this->requestUriFactory->getPathAndQuery(server: [
            'IIS_WasUrlRewritten' => '1',
            'UNENCODED_URL' => '/foo/bar?baz=dib',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['path' => '/foo/bar', 'query' => 'baz=dib'];
        $actual = $this->requestUriFactory->getPathAndQuery(server: [
            'REQUEST_URI' => '/foo/bar?baz=dib',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['path' => '/foo/bar', 'query' => 'baz=dib'];
        $actual = $this->requestUriFactory->getPathAndQuery(server: [
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'baz=dib',
        ]);
        $this->assertSame($expect, $actual);

        $expect = ['path' => '/foo/bar', 'query' => 'zim=gir'];
        $actual = $this->requestUriFactory->getPathAndQuery(server: [
            'REQUEST_URI' => '/foo/bar?baz=dib',
            'QUERY_STRING' => 'zim=gir',
        ]);
        $this->assertSame($expect, $actual);
    }
}
