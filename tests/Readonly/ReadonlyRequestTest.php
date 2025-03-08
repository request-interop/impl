<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\Request;

class ReadonlyRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance() : void
    {
        $actual = new ReadonlyRequest(
            cookies: [],
            files: [],
            headers: [],
            input: [],
            method: 'FAKE',
            query: [],
            server: [],
            uploads: [],
            url: new ReadonlyRequestUrl('http', 'example.net'),
            body: new ReadonlyRequestBody('php://input'),
        );

        $this->assertInstanceof(Request::class, $actual);
    }
}
