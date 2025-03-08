<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\RequestBody;
use RequestInterop\Interface\RequestUpload;
use Stringable;

readonly class ReadonlyRequestUpload implements RequestUpload
{
    public readonly ?RequestBody $body;

    public function __construct(
        public readonly string $tmpName,
        public readonly int $error,
        public readonly ?string $name = null,
        public readonly ?string $fullPath = null,
        public readonly ?string $type = null,
        public readonly ?int $size = null,
    ) {
        $this->body = null;
    }

    public function move(string|Stringable $to) : bool
    {
        return move_uploaded_file($this->tmpName, (string) $to);
    }
}
