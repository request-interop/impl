<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use BadMethodCallException;
use RequestInterop\Impl\RequestFactory;
use RequestInterop\Impl\RequestFactoryTestCase;
use RequestInterop\Interface\Body;
use UnexpectedValueException;

/**
 * @phpstan-import-type BodyResource from Body
 */
class ReadonlyFactoryTest extends RequestFactoryTestCase
{
    /**
     * @inheritdoc
     * @return ReadonlyFactory
     */
    protected function newRequestFactory(
        ?array $_cookie = null,
        ?array $_files = null,
        ?array $_get = null,
        ?array $_post = null,
        ?array $_server = null,
        mixed $body = null,
    ) : RequestFactory
    {
        return new ReadonlyFactory(
            _cookie: $_cookie,
            _files: $_files,
            _get: $_get,
            _post: $_post,
            _server: $_server,
            body: $body,
        );
    }

    public function testNewRequest() : void
    {
        $this->assertInstanceof(ReadonlyRequest::CLASS, $this->newRequestFactory()->newRequest());
    }

    public function testNewUrl() : void
    {
        $this->assertInstanceof(ReadonlyUrl::CLASS, $this->newRequestFactory()->newUrl());
    }

    public function testNewUpload() : void
    {
        $this->assertInstanceOf(
            ReadonlyUpload::CLASS,
            $this
                ->newRequestFactory()
                ->newUpload(
                    tmpName: '/tmp/upload/cnlk68jwhy',
                    error: 0,
                )
            );
    }

    public function testNewBody() : void
    {
        /** @var BodyResource */
        $bodyResource = fopen('php://temp', 'r');
        $factory = $this->newRequestFactory();
        $this->expectException(BadMethodCallException::CLASS);
        $factory->newBody($bodyResource);
    }

    public function testReadonly() : void
    {
        $factory = $this->newRequestFactory();
        $this->expectException(UnexpectedValueException::CLASS);
        $this->expectExceptionMessage('Readonly values must be null, scalar, or array.');
        $factory->readonly(['foo' => new \stdClass()]);
    }
}
