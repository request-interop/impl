<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestFactoryImpl;
use RequestInterop\Impl\RequestFactoryTestCase;
use RequestInterop\Interface\Body;
use UnexpectedValueException;

class MutableRequestFactoryTest extends RequestFactoryTestCase
{
    /**
     * @inheritdoc
     * @return MutableRequestFactory
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
        return new MutableRequestFactory(
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

        $this->assertInstanceof(MutableRequest::class, $this->newRequestFactory()->newRequest());
    }

    public function testnewRequestUrl() : void
    {
        $_SERVER = [
            'SERVER_ADDR' => '127.0.0.1',
        ];

        $this->assertInstanceof(MutableRequestUrl::class, $this->newRequestFactory()->newRequestUrl());
    }

    public function testnewRequestUpload() : void
    {
        $tmpName = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        $this->assertInstanceOf(
            MutableRequestUpload::class,
            $this
                ->newRequestFactory()
                ->newRequestUpload(
                    tmpName: $tmpName,
                    error: 0,
                )
            );
    }

    public function testnewRequestBody() : void
    {
        $text = 'This is a test.';
        $phpInput = fopen('php://memory', 'r+');
        assert(is_resource($phpInput));
        fwrite($phpInput, $text);
        rewind($phpInput);
        $body = $this->newRequestFactory()->newRequestBody($phpInput);
        $this->assertInstanceOf(MutableRequestBody::class, $body);
        $this->assertSame($text, (string) $body);
    }
}
