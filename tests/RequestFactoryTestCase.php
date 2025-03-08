<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Impl\RequestFactoryImpl;
use RequestInterop\Interface\RequestTypeAliases;
use RequestInterop\Interface\RequestUpload;
use RuntimeException;

/**
 * @phpstan-import-type cookies_array from RequestTypeAliases
 * @phpstan-import-type files_array from RequestTypeAliases
 * @phpstan-import-type input_array from RequestTypeAliases
 * @phpstan-import-type query_array from RequestTypeAliases
 * @phpstan-import-type server_array from RequestTypeAliases
 * @phpstan-import-type uploads_array from RequestTypeAliases
 */
#[\PHPUnit\Framework\Attributes\BackupGlobals(true)]
abstract class RequestFactoryTestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $_COOKIE = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
    }

    /**
     * @param ?cookies_array $_cookie
     * @param ?files_array $_files
     * @param ?query_array $_get
     * @param ?input_array $_post
     * @param ?server_array $_server
     * @param null|string|resource $phpInput
     */
    abstract protected function newRequestFactory(
        ?array $_cookie = null,
        ?array $_files = null,
        ?array $_get = null,
        ?array $_post = null,
        ?array $_server = null,
        mixed $phpInput = null,
    ) : RequestFactoryImpl;

    abstract public function testNewRequest() : void;

    abstract public function testnewRequestUrl() : void;

    abstract public function testnewRequestUpload() : void;

    public function testContentType() : void
    {
        $this->assertNull($this->newRequestFactory()->contentType());
        $factory = $this->newRequestFactory(_server: ['CONTENT_TYPE' => 'TEXT/plain']);
        $expect = 'text/plain';
        $actual = $factory->contentType();
        $this->assertSame($expect, $actual);
    }

    public function testCookiesArray() : void
    {
        // empty array when no superglobal
        $this->assertSame([], $this->newRequestFactory()->cookiesArray());

        // uses superglobal when no property override
        $_COOKIE = ['foo' => 'bar'];
        $actual = $this->newRequestFactory()->cookiesArray();
        $this->assertSame($_COOKIE, $actual);

        // uses property override when given
        $_cookie = ['baz' => 'dib'];
        $actual = $this->newRequestFactory(_cookie: $_cookie)->cookiesArray();
        $this->assertSame($_cookie, $actual);
    }

    public function testFilesArray() : void
    {
        // empty array when no superglobal
        $this->assertSame([], $this->newRequestFactory()->filesArray());

        // uses superglobal when no property override
        $_FILES = [
          'foo1' => [
            'error' => 0,
            'name' => '',
            'full_path' => '',
            'size' => 0,
            'tmp_name' => '',
            'type' => '',
          ],
        ];

        $actual = $this->newRequestFactory()->filesArray();
        $this->assertSame($_FILES, $actual);

        // uses property override when given
        $_files = [
          'foo2' => [
            'error' => 0,
            'name' => '',
            'full_path' => '',
            'size' => 0,
            'tmp_name' => '',
            'type' => '',
          ],
        ];

        $actual = $this->newRequestFactory(_files: $_files)->filesArray();
        $this->assertSame($_files, $actual);
    }

    public function testHeadersArray() : void
    {
        $factory = $this->newRequestFactory(_server: [
            'HTTP_HOST' => 'example.com',
            'HTTP_FOO_BAR_BAZ' => 'dib,zim,gir',
            'NON_HTTP_HEADER' => 'should not show',
            'CONTENT_LENGTH' => '123',
            'CONTENT_TYPE' => 'text/plain',
        ]);

        $expect = [
            'host' => 'example.com',
            'foo-bar-baz' => 'dib,zim,gir',
            'content-length' => '123',
            'content-type' => 'text/plain',
        ];

        $this->assertSame($expect, $factory->headersArray());
    }

    public function testInputArray() : void
    {
        $_post = ['foo' => 'bar'];

        $actual = $this->newRequestFactory(
            _post: $_post,
            phpInput: null,
        )->inputArray();

        $this->assertSame($_post, $actual);
    }

    public function testInputArrayJson() : void
    {
        $expect = ['foo' => 'bar'];

        $factory = $this->newRequestFactory(
            _server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            phpInput: 'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.json',
        );

        $this->assertSame($expect, $factory->inputArray());
    }

    public function testInputArrayXml() : void
    {
        $expect = ['foo' => 'bar'];

        $factory = $this->newRequestFactory(
            _server: [
                'CONTENT_TYPE' => 'application/xml',
            ],
            phpInput: 'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml',
        );

        $this->assertSame($expect, $factory->inputArray());

        $factory = $this->newRequestFactory(
            _server: [
                'CONTENT_TYPE' => 'text/xml',
            ],
            phpInput: 'file://' . __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.xml',
        );

        $this->assertSame($expect, $factory->inputArray());
    }

    public function testMethodString() : void
    {
        // empty string when no superglobals
        $this->assertSame('', $this->newRequestFactory()->methodString());

        // uses superglobal when no header override
        $actual = $this
            ->newRequestFactory(_server: ['REQUEST_METHOD' => 'POST'])
            ->methodString();
        $this->assertSame('POST', $actual);

        // uses header override when no property override
        $actual = $this
            ->newRequestFactory(_server: [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'patch'
            ])
            ->methodString();
        $this->assertSame('PATCH', $actual);
    }

    public function testQueryArray() : void
    {
        // empty array when no superglobal
        $this->assertSame([], $this->newRequestFactory()->queryArray());

        // uses superglobal when no property override
        $_GET = ['foo' => 'bar'];
        $actual = $this->newRequestFactory()->queryArray();
        $this->assertSame($_GET, $actual);

        // uses property override when given
        $_get = ['baz' => 'dib'];
        $actual = $this->newRequestFactory(_get: $_get)->queryArray();
        $this->assertSame($_get, $actual);
    }

    public function testServerArray() : void
    {
        // empty array when no superglobal
        $this->assertSame([], $this->newRequestFactory()->serverArray());

        // uses superglobal when no property override
        $_SERVER = ['FOO' => 'bar'];
        $actual = $this->newRequestFactory()->serverArray();
        $this->assertSame($_SERVER, $actual);

        // uses property override when given
        $_server = ['baz' => 'dib'];
        $actual = $this->newRequestFactory(_server: $_server)->serverArray();
        $this->assertSame($_server, $actual);
    }

    public function testUploadsArrayFilesFromItem(): void
    {
        $tmpName = __DIR__ . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        $actual = $this->newRequestFactory(
            _files: [
                'photo' => [
                    'tmp_name' => $tmpName,
                    'error' => 0,
                    'name' => 'calvin.jpg',
                    'full_path' => '/Users/watterson/Pictures/calvin.jpg',
                    'size' => 12345,
                    'type' => 'image/jpeg',
                ],
            ])
            ->uploadsArray();

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RequestUpload::class, $actual['photo']);
        $this->assertSame('calvin.jpg', $actual['photo']->name);

        /** @var array{profile: array{details: array{photo: RequestUpload}}} $actual */
        $actual = $this->newRequestFactory(
            _files: [
                'profile' => [
                    'details' => [
                        'photo' => [
                            'tmp_name' => $tmpName,
                            'error' => 0,
                            'name' => 'hobbes.jpg',
                            'full_path' => '/Users/watterson/Pictures/hobbes.jpg',
                            'size' => 23456,
                            'type' => 'image/jpeg',
                        ],
                    ],
                ],
            ])
            ->uploadsArray();

        $this->assertCount(1, $actual);
        $this->assertSame('hobbes.jpg', $actual['profile']['details']['photo']->name);
    }

    public function testUploadsArrayFilesFromGroup(): void
    {
        $tmpName = __DIR__ . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        /** @var array{team: array{people: array{photos: RequestUpload[]}}} $actual */
        $actual = $this->newRequestFactory(
            _files: [
                'team' => [
                    'people' => [
                        'photos' => [
                            'tmp_name' => [
                                0 => $tmpName,
                                1 => $tmpName,
                                2 => $tmpName,
                            ],
                            'error' => [
                                0 => 0,
                                1 => 0,
                                2 => 0,
                            ],
                            'name' => [
                                0 => 'calvin.jpg',
                                1 => 'hobbes.jpg',
                                2 => 'susie.jpg',
                            ],
                            'full_path' => [
                                0 => '/Users/watterson/Pictures/calvin.jpg',
                                1 => '/Users/watterson/Pictures/hobbes.jpg',
                                2 => '/Users/watterson/Pictures/susie.jpg',
                            ],
                            'size' => [
                                0 => 12345,
                                1 => 23456,
                                2 => 45678,
                            ],
                            'type' => [
                                0 => 'image/jpeg',
                                1 => 'image/jpeg',
                                2 => 'image/jpeg',
                            ],
                        ],
                    ],
                ],
            ])
            ->uploadsArray();

        $this->assertCount(3, $actual['team']['people']['photos']);
        $this->assertSame('calvin.jpg', $actual['team']['people']['photos'][0]->name);
        $this->assertSame('hobbes.jpg', $actual['team']['people']['photos'][1]->name);
        $this->assertSame('susie.jpg', $actual['team']['people']['photos'][2]->name);
    }

    public function testUploadsArrayFilesFromGroupNested(): void
    {
        $tmpName = __DIR__ . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        /** @var array{alter-egos: array<int, array{photo: RequestUpload[]}>} $actual */
        $actual = $this->newRequestFactory(
            _files: [
                'alter-egos' => [
                    'tmp_name' => [
                        0 => [
                            'photo' => [
                                0 => $tmpName,
                                1 => $tmpName,
                                2 => $tmpName,
                            ],
                        ],
                    ],
                    'error' => [
                        0 => [
                            'photo' => [
                                0 => 0,
                                1 => 0,
                                2 => 0,
                            ],
                        ],
                    ],
                    'name' => [
                        0 => [
                            'photo' => [
                                0 => 'spaceman-spiff.jpg',
                                1 => 'stupendous-man.jpg',
                                2 => 'captain-napalm.jpg',
                            ],
                        ],
                    ],
                    'full_path' => [
                        0 => [
                            'photo' => [
                                0 => '/Users/watterson/Pictures/calvin.jpg',
                                1 => '/Users/watterson/Pictures/hobbes.jpg',
                                2 => '/Users/watterson/Pictures/susie.jpg',
                            ],
                        ],
                    ],
                    'size' => [
                        0 => [
                            'photo' => [
                                0 => 12345,
                                1 => 23456,
                                2 => 34567,
                            ],
                        ],
                    ],
                    'type' => [
                        0 => [
                            'photo' => [
                                0 => 'image/jpeg',
                                1 => 'image/jpeg',
                                2 => 'image/jpeg',
                            ],
                        ],
                    ],
                ],
            ])
            ->uploadsArray();

        $this->assertCount(3, $actual['alter-egos'][0]['photo']);
        $this->assertSame('spaceman-spiff.jpg', $actual['alter-egos'][0]['photo'][0]->name);
        $this->assertSame('stupendous-man.jpg', $actual['alter-egos'][0]['photo'][1]->name);
        $this->assertSame('captain-napalm.jpg', $actual['alter-egos'][0]['photo'][2]->name);
    }

    public function testUrlProperties() : void
    {
        $factory = $this->newRequestFactory(_server: [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
            'PHP_AUTH_USER' => 'watterson',
            'PHP_AUTH_PW' => 'bopass',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'baz=dib',
        ]);

        $expect = [
            'scheme' => 'https',
            'host' => 'example.com',
            'port' => 443,
            'path' => '/foo/bar',
            'query' => 'baz=dib',
        ];

        $this->assertSame($expect, $factory->urlProperties());
    }

    public function testUrlScheme() : void
    {
        $expect = ['scheme' => 'http'];
        $actual = $this->newRequestFactory();
        $this->assertSame($expect, $actual->urlScheme());

        $expect = ['scheme' => 'http'];
        $actual = $this->newRequestFactory(_server: ['HTTPS' => 'Off']);
        $this->assertSame($expect, $actual->urlScheme());

        $expect = ['scheme' => 'https'];
        $actual = $this->newRequestFactory(_server: ['HTTPS' => '1']);
        $this->assertSame($expect, $actual->urlScheme());

        $expect = ['scheme' => 'https'];
        $actual = $this->newRequestFactory(_server: ['HTTPS' => 'on']);
        $this->assertSame($expect, $actual->urlScheme());
    }

    public function testUrlHostAndPort() : void
    {
        $expect = ['host' => 'example.com', 'port' => null];
        $actual = $this->newRequestFactory(_server: [
            'HTTP_HOST' => 'example.com',
        ]);

        $this->assertSame($expect, $actual->urlHostAndPort());

        $expect = ['host' => 'example.com', 'port' => 8080];
        $actual = $this->newRequestFactory(_server: [
            'HTTP_HOST' => 'example.com:8080',
        ]);
        $this->assertSame($expect, $actual->urlHostAndPort());

        $expect = ['host' => 'example.com', 'port' => 8080];
        $actual = $this->newRequestFactory(_server: [
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '8080',
        ]);
        $this->assertSame($expect, $actual->urlHostAndPort());

        $expect = ['host' => '8.8.8.8', 'port' => null];
        $actual = $this->newRequestFactory(_server: [
            'SERVER_ADDR' => '8.8.8.8',
        ]);

        $this->assertSame($expect, $actual->urlHostAndPort());

        $expect = ['host' => '8.8.8.8', 'port' => 8080];
        $actual = $this->newRequestFactory(_server: [
            'SERVER_ADDR' => '8.8.8.8',
            'SERVER_PORT' => '8080',
        ]);
        $this->assertSame($expect, $actual->urlHostAndPort());

        $expect = ['host' => '[2001:4860:4860::8888]', 'port' => null];
        $actual = $this->newRequestFactory(_server: [
            'SERVER_ADDR' => '2001:4860:4860::8888',
        ]);

        $this->assertSame($expect, $actual->urlHostAndPort());

        $expect = ['host' => '[2001:4860:4860::8888]', 'port' => 8080];
        $actual = $this->newRequestFactory(_server: [
            'SERVER_ADDR' => '2001:4860:4860::8888',
            'SERVER_PORT' => '8080',
        ]);
        $this->assertSame($expect, $actual->urlHostAndPort());

        $actual = $this->newRequestFactory();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not determine host and port.');
        $actual->urlHostAndPort();
    }

    public function testUrlPathAndQuery() : void
    {
        $expect = ['path' => '', 'query' => ''];
        $actual = $this->newRequestFactory();
        $this->assertSame($expect, $actual->urlPathAndQuery());

        $expect = ['path' => '/foo/bar', 'query' => 'baz=dib'];
        $actual = $this->newRequestFactory(_server: [
            'IIS_WasUrlRewritten' => '1',
            'UNENCODED_URL' => '/foo/bar?baz=dib',
        ]);
        $this->assertSame($expect, $actual->urlPathAndQuery());

        $expect = ['path' => '/foo/bar', 'query' => 'baz=dib'];
        $actual = $this->newRequestFactory(_server: [
            'REQUEST_URI' => '/foo/bar?baz=dib',
        ]);
        $this->assertSame($expect, $actual->urlPathAndQuery());

        $expect = ['path' => '/foo/bar', 'query' => 'baz=dib'];
        $actual = $this->newRequestFactory(_server: [
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'baz=dib',
        ]);
        $this->assertSame($expect, $actual->urlPathAndQuery());

        $expect = ['path' => '/foo/bar', 'query' => 'zim=gir'];
        $actual = $this->newRequestFactory(_server: [
            'REQUEST_URI' => '/foo/bar?baz=dib',
            'QUERY_STRING' => 'zim=gir',
        ]);
        $this->assertSame($expect, $actual->urlPathAndQuery());
    }
}
