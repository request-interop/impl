<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\Body;
use RequestInterop\Interface\Request;

class MutableRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance() : void
    {
        $actual = new MutableRequest();
        $this->assertInstanceof(Request::CLASS, $actual);
        $this->assertInstanceof(Body::CLASS, $actual);
    }
}
