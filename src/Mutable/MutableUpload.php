<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\Body;
use RequestInterop\Interface\Upload;

/**
 * @phpstan-import-type BodyResource from Body
 */
class MutableUpload implements Upload, Body
{
    /**
     * @inheritdoc
     */
    public mixed $body {
        get {
            if ($this->body === null) {
                $this->body = fopen($this->tmpName, 'rb');
            }

            return $this->body;
        }
    }

    /**
     * @param ?BodyResource $body
     */
    public function __construct(
        public string $tmpName,
        public int $error,
        public ?string $name = null,
        public ?string $fullPath = null,
        public ?string $type = null,
        public ?int $size = null,
        mixed $body = null,
    ) {
        $this->body = $body;
    }

    public function __toString() : string
    {
        /** @var BodyResource */
        $body = $this->body;
        rewind($body);
        return (string) stream_get_contents($body);
    }

    public function move(string $to) : bool
    {
        return move_uploaded_file((string) $this->tmpName, $to);
    }
}
