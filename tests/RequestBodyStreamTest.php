<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

class RequestBodyStreamTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        if (! in_array('fake', stream_get_wrappers())) {
            stream_wrapper_register('fake', FakeStream::class);
        }

        FakeStream::reset();
    }

    protected function tearDown() : void
    {
        if (in_array('fake', stream_get_wrappers())) {
            stream_wrapper_unregister('fake');
        }
    }

    public function test__toString() : void
    {
        $content = (string) file_get_contents(
            __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.txt',
        );

        $requestBodyStream = new RequestBodyStream($content);
        $this->assertTrue($requestBodyStream->isOpen());
        $this->assertSame($content, (string) $requestBodyStream);
    }

    public function testSubString() : void
    {
        $content = (string) file_get_contents(
            __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.txt',
        );

        $requestBodyStream = new RequestBodyStream($content);

        $expect = "ello";
        $actual = $requestBodyStream->subString(1, 4);
        $this->assertSame($expect, $actual);

        $expect = "orld";
        $actual = $requestBodyStream->subString(-6, 4);
        $this->assertSame($expect, $actual);
    }

    public function testNullContent() : void
    {
        $requestBodyStream = new RequestBodyStream();
        $this->assertTrue($requestBodyStream->isOpen());
        $this->assertSame('', (string) $requestBodyStream);
    }

    public function testSeekFailure() : void
    {
        $stream = new FakeRequestBodyStream('');
        $resource = fopen('fake://test', 'r+');
        assert(is_resource($resource));
        $stream->setResource($resource);
        FakeStream::failOn('seek');
        $this->expectException(RequestBodyStreamException::class);
        $this->expectExceptionMessage('Seek failed on request body');
        $stream->__toString();
    }
}
