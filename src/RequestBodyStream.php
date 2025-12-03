<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use StreamInterop\Interface\StringableStream;

class RequestBodyStream implements StringableStream
{
    /**
     * @inheritdoc
     */
    public array $metadata {
        get {
            return $this->isOpen() ? stream_get_meta_data($this->resource) : [];
        }
    }

    /**
     * @var resource
     */
    protected mixed $resource;

    public function __construct(protected string $file = 'php://input')
    {
        $resource = fopen($this->file, 'rb');
        assert(is_resource($resource));
        $this->resource = $resource;
    }

    public function __destruct()
    {
        if (! $this->isClosed()) {
            fclose($this->resource);
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString() : string
    {
        return $this->subString(0);
    }

    /**
     * @inheritdoc
     */
    public function isClosed() : bool
    {
        return strtolower(get_resource_type($this->resource)) === 'unknown';
    }

    /**
     * @inheritdoc
     */
    public function isOpen() : bool
    {
        return strtolower(get_resource_type($this->resource)) === 'stream';
    }

    /**
     * @inheritdoc
     */
    public function subString(int $offset, ?int $length = null) : string
    {
        $initial = $this->tell();

        if ($offset < 0) {
            $this->seek($offset, SEEK_END);
        } else {
            $this->seek($offset);
        }

        $string = $this->read($length);
        $this->seek($initial);
        return $string;
    }

    protected function tell() : int
    {
        $position = ftell($this->resource);

        if ($position === false) {
            throw new RequestBodyStreamException("Tell failed on {$this->file}");
        }

        return $position;
    }

    protected function seek(int $offset, int $whence = SEEK_SET) : void
    {
        $result = fseek($this->resource, $offset, $whence);

        if ($result === -1) {
            throw new RequestBodyStreamException("Seek failed on {$this->file}");
        }
    }

    protected function read(?int $length) : string
    {
        $string = stream_get_contents($this->resource, $length);

        if ($string === false) {
            throw new RequestBodyStreamException("Read failed on {$this->file}");
        }

        return $string;
    }
}
