<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\UploadTestCase;
use RequestInterop\Interface\Upload;

class ReadonlyUploadTest extends UploadTestCase
{
    /**
     * @return ReadonlyUpload
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
        return new ReadonlyUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
        );
    }
}
