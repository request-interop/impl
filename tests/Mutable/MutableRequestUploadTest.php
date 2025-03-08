<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestUploadTestCase;
use RequestInterop\Interface\RequestUpload;

class MutableRequestUploadTest extends RequestUploadTestCase
{
    /**
     * @return MutableRequestUpload
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
        return new MutableRequestUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
        );
    }
}
