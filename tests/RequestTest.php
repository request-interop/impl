<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use StreamInterop\Impl\ReadonlyFileStream;
use UriInterop\Impl\ReadonlyUri;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance() : void
    {
        $actual = new Request(
            body: new ReadonlyFileStream('php://input'),
            cookies: [],
            files: [],
            headers: [],
            input: [],
            method: 'FAKE',
            query: [],
            server: [],
            uploads: [],
            uri: new ReadonlyUri('http', 'example.net'),
        );

        $this->assertInstanceof(RequestStruct::class, $actual);
    }
}
