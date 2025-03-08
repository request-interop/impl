<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use BadMethodCallException;
use RequestInterop\Impl\RequestFactoryImpl;
use RequestInterop\Impl\RequestFactoryTestCase;
use InvalidArgumentException;

class ReadonlyRequestFactoryTest extends RequestFactoryTestCase
{
    /**
     * @inheritdoc
     * @return ReadonlyRequestFactory
     */
    protected function newRequestFactory(
        ?array $_cookie = null,
        ?array $_files = null,
        ?array $_get = null,
        ?array $_post = null,
        ?array $_server = null,
        mixed $phpInput = null,
    ) : RequestFactoryImpl
    {
        return new ReadonlyRequestFactory(
            _cookie: $_cookie,
            _files: $_files,
            _get: $_get,
            _post: $_post,
            _server: $_server,
            phpInput: $phpInput,
        );
    }

    public function testNewRequest() : void
    {
        $_SERVER = [
            'SERVER_ADDR' => '127.0.0.1',
        ];

        $this->assertInstanceof(ReadonlyRequest::class, $this->newRequestFactory()->newRequest());
    }

    public function testnewRequestUrl() : void
    {
        $_SERVER = [
            'SERVER_ADDR' => '127.0.0.1',
        ];

        $this->assertInstanceof(ReadonlyRequestUrl::class, $this->newRequestFactory()->newRequestUrl());
    }

    public function testnewRequestUpload() : void
    {
        $this->assertInstanceOf(
            ReadonlyRequestUpload::class,
            $this
                ->newRequestFactory()
                ->newRequestUpload(
                    tmpName: '/tmp/upload/cnlk68jwhy',
                    error: 0,
                )
            );
    }

    public function testReadonly() : void
    {
        $factory = $this->newRequestFactory();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Readonly values must be null, scalar, or array.');
        $factory->readonly(['foo' => new \stdClass()]);
    }
}
