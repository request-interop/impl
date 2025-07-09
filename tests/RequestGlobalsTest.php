<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

#[\PHPUnit\Framework\Attributes\BackupGlobals(true)]
class RequestGlobalsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $_COOKIE = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
    }

    protected function newRequestGlobals() : RequestGlobals
    {
        return new RequestGlobals();
    }

    public function test_COOKIE() : void
    {
        $_COOKIE = ['foo' => 'bar'];
        $actual = $this->newRequestGlobals()->_COOKIE;
        $this->assertSame($_COOKIE, $actual);
    }

    public function test_GET() : void
    {
        $_GET = ['foo' => 'bar'];
        $actual = $this->newRequestGlobals()->_GET;
        $this->assertSame($_GET, $actual);
    }

    public function test_FILES() : void
    {
        $_FILES = [
            'foo1' => [
                'error' => 0,
                'name' => '',
                'full_path' => '',
                'size' => 0,
                'tmp_name' => '',
                'type' => '',
            ],
        ];

        $actual = $this->newRequestGlobals()->_FILES;
        $this->assertSame($_FILES, $actual);
    }

    public function test_POST() : void
    {
        $_POST = ['foo' => 'bar'];
        $actual = $this->newRequestGlobals()->_POST;
        $this->assertSame($_POST, $actual);
    }

    public function test_SERVER() : void
    {
        $_SERVER = ['FOO' => 'bar'];
        $actual = $this->newRequestGlobals()->_SERVER;
        $this->assertSame($_SERVER, $actual);
    }
}
