<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\Upload;

abstract class UploadTestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function newUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
    ) : Upload;

    public function test() : void
    {
        $text = 'This is a fake upload tmp file.';
        $tmpName = __DIR__ . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        $upload = $this->newUpload(
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
