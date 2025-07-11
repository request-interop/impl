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
            body: [],
            cookies: [],
            headers: [],
            input: new ReadonlyFileStream('php://input'),
            method: 'FAKE',
            query: [],
            server: [],
            uploads: [],
            uri: new RequestUri('http', 'example.net'),
        );

        $this->assertInstanceof(RequestStruct::class, $actual);
    }
}
