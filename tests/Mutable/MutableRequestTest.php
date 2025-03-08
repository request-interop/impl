<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\Body;
use RequestInterop\Interface\Request;

class MutableRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance() : void
    {
        $body = fopen('php://input', 'rb');
        assert(is_resource($body));

        $actual = new MutableRequest(
            cookies: [],
            files: [],
            headers: [],
            input: [],
            method: 'FAKE',
            query: [],
            server: [],
            uploads: [],
            url: new MutableRequestUrl('http', 'example.net'),
            body: new MutableRequestBody($body),
        );

        $this->assertInstanceof(Request::class, $actual);
    }
}
