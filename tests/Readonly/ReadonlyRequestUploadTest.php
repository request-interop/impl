<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\RequestUploadTestCase;
use RequestInterop\Interface\RequestUpload;

class ReadonlyRequestUploadTest extends RequestUploadTestCase
{
    /**
     * @return ReadonlyRequestUpload
     */
    public function newRequestUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
    ) : RequestUpload
    {
        return new ReadonlyRequestUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
        );
    }
}
