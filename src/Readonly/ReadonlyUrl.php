<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\Url;

readonly class ReadonlyUrl implements Url
{
    public function __construct(
        public readonly ?string $scheme = null,
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        public readonly ?string $user = null,
        public readonly ?string $pass = null,
        public readonly ?string $path = null,
        public readonly ?string $query = null,
        public readonly ?string $fragment = null,
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
