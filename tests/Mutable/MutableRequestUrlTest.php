<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestUrlTestCase;
use RequestInterop\Interface\RequestUrl;

class MutableRequestUrlTest extends RequestUrlTestCase
{
    /**
     * @param non-empty-string $scheme
     * @param non-empty-string $host
     * @return MutableRequestUrl
     */
    public function newRequestUrl(
        string $scheme,
        string $host,
        ?int $port = null,
        string $path = '',
        ?string $query = null,
    ) : RequestUrl
    {
        return new MutableRequestUrl(
            scheme: $scheme,
            host: $host,
            port: $port,
            path: $path,
            query: $query,
        );
    }
}
