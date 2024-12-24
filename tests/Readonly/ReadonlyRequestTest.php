<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\Request;

class ReadonlyRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance() : void
    {
        $actual = new ReadonlyRequest();
        $this->assertInstanceof(Request::CLASS, $actual);
    }
}
