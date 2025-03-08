<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\RequestBody;
use RequestInterop\Interface\RequestUpload;
use Stringable;

class MutableRequestUpload implements RequestUpload
{
    /**
     * @param ?MutableRequestBody $body
     */
    public function __construct(
        public string $tmpName,
        public int $error,
        public ?string $name = null,
        public ?string $fullPath = null,
        public ?string $type = null,
        public ?int $size = null,
        public ?RequestBody $body = null,
    ) {
    }

    public function move(string|Stringable $to) : bool
    {
        return move_uploaded_file($this->tmpName, (string) $to);
    }
}
