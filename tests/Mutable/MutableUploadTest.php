<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\UploadTestCase;
use RequestInterop\Interface\Upload;

class MutableUploadTest extends UploadTestCase
{
    /**
     * @return MutableUpload
     */
    public function newUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
    ) : Upload
    {
        return new MutableUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
        );
    }

    public function testBodyResource() : void
    {
        $tmpName = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'FakeUpload.txt';

        $upload = $this->newUpload(
            tmpName: $tmpName,
            error: 0,
        );

        $this->assertTrue(is_resource($upload->body));
        $expect = file_get_contents($tmpName);
        $this->assertSame($expect, (string) $upload);
    }
}
