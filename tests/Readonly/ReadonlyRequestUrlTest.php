<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\RequestUrlTestCase;
use RequestInterop\Interface\RequestUrl;

class ReadonlyRequestUrlTest extends RequestUrlTestCase
{
    /**
     * @param non-empty-string $scheme
     * @param non-empty-string $host
     * @return ReadonlyRequestUrl
     */
    public function newRequestUrl(
        string $scheme,
        string $host,
        ?int $port = null,
        ?string $path = null,
        ?string $query = null,
    ) : RequestUrl
    {
        return new ReadonlyRequestUrl(
            scheme: $scheme,
            host: $host,
            port: $port,
            path: $path,
            query: $query,
        );
    }
}
