<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestFactory;
use RequestInterop\Impl\RequestFactoryTestCase;
use RequestInterop\Interface\Body;
use UnexpectedValueException;

/**
 * @phpstan-import-type BodyResource from Body
 */
class MutableFactoryTest extends RequestFactoryTestCase
{
    /**
     * @inheritdoc
     * @return MutableFactory
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
        return new MutableFactory(
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
        $this->assertInstanceof(MutableRequest::CLASS, $this->newRequestFactory()->newRequest());
    }

    public function testNewUrl() : void
    {
        $this->assertInstanceof(MutableUrl::CLASS, $this->newRequestFactory()->newUrl());
    }

    public function testNewUpload() : void
    {
        $this->assertInstanceOf(
            MutableUpload::CLASS,
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
        $text = 'This is a test.';

        /** @var BodyResource */
        $bodyResource = fopen('php://temp', 'r+');
        fwrite($bodyResource, $text);
        rewind($bodyResource);
        $mutableBody = $this->newRequestFactory()->newBody($bodyResource);
        $this->assertInstanceOf(Body::CLASS, $mutableBody);
        $this->assertInstanceOf(MutableBody::CLASS, $mutableBody);
        $this->assertSame($bodyResource, $mutableBody->body);
        $this->assertSame($text, (string) $mutableBody);
    }

    public function testBodyResource() : void
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'raw-body.json';

        $factory = $this->newRequestFactory(
            body: $file,
        );

        $bodyResource = $factory->bodyResource();
        $this->assertTrue(is_resource($bodyResource));

        $request = $factory->newRequest();
        $this->assertSame($bodyResource, $request->body);
        $this->assertSame(file_get_contents($file), (string) $request);
    }
}
