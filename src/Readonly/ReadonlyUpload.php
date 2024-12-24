<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\Upload;

readonly class ReadonlyUpload implements Upload
{
    public function __construct(
        public readonly string $tmpName,
        public readonly int $error,
        public readonly ?string $name = null,
        public readonly ?string $fullPath = null,
        public readonly ?string $type = null,
        public readonly ?int $size = null,
    ) {
    }

    public function move(string $to) : bool
    {
        return move_uploaded_file((string) $this->tmpName, $to);
    }
}
