<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

class FakeStream
{
    private static string $failOp = '';

    public static function failOn(string $op) : void
    {
        self::$failOp = $op;
    }

    public static function reset() : void
    {
        self::$failOp = '';
    }

    /** @var resource|null */
    public $context;

    private int $position = 0;

    /** @param ?string $openedPath */
    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath,
    ) : bool
    {
        return true;
    }

    public function stream_read(int $count) : false|string
    {
        return '';
    }

    public function stream_write(string $data) : int
    {
        return strlen($data);
    }

    public function stream_tell() : false|int
    {
        return $this->position;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET) : bool
    {
        if (self::$failOp === 'seek') {
            return false;
        }

        $this->position = $offset;
        return true;
    }

    public function stream_eof() : bool
    {
        return true;
    }

    /** @return array<mixed>|false */
    public function stream_stat() : false|array
    {
        return false;
    }

    public function stream_close() : void
    {
    }
}
