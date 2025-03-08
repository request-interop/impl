<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestUpload;

abstract class RequestUploadTestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function newRequestUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
    ) : RequestUpload;

    public function test() : void
    {
        $text = 'This is a fake upload tmp file.';
        $tmpName = __DIR__ . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        $upload = $this->newRequestUpload(
            tmpName: $tmpName,
            error: 0,
            name: basename($tmpName),
            fullPath: basename($tmpName),
            type: 'text/plain',
            size: strlen($text),
        );

        $this->assertFalse($upload->move(to: '/tmp/123'));
    }
}
