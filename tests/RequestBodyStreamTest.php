<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

class RequestBodyStreamTest extends \PHPUnit\Framework\TestCase
{
    public function test__toString() : void
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.txt';

        $requestBodyStream = new RequestBodyStream(
            "file://{$file}",
        );

        $this->assertTrue($requestBodyStream->isOpen());
        $expect = file_get_contents($file);
        $this->assertSame($expect, (string) $requestBodyStream);
    }

    public function testSubString() : void
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'raw-body.txt';

        $requestBodyStream = new RequestBodyStream(
            "file://{$file}",
        );

        $expect = "ello";
        $actual = $requestBodyStream->subString(1, 4);
        $this->assertSame($expect, $actual);

        $expect = "orld";
        $actual = $requestBodyStream->subString(-6, 4);
        $this->assertSame($expect, $actual);
    }
}
