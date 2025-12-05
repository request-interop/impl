<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

class RequestBodyStreamTest extends \PHPUnit\Framework\TestCase
{
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
}
