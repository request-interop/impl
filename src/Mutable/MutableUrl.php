<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\Url;

class MutableUrl implements Url
{
    public function __construct(
        public ?string $scheme = null,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $user = null,
        public ?string $pass = null,
        public ?string $path = null,
        public ?string $query = null,
        public ?string $fragment = null,
    ) {
    }

    public function __toString() : string
    {
        $info = $this->user;
        $info .= $this->pass ? ":{$this->pass}" : "";
        $info .= $info ? "@" : "";
        $port = $this->port ? ":{$this->port}" : "";
        $query = $this->query ? "?{$this->query}" : "";
        $fragment = $this->fragment ? "#{$this->fragment}" : "";
        return "{$this->scheme}://{$info}{$this->host}{$port}{$this->path}{$query}{$fragment}";
    }
}
