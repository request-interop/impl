<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\UrlTestCase;
use RequestInterop\Interface\Url;

class ReadonlyUrlTest extends UrlTestCase
{
    /**
     * @return ReadonlyUrl
     */
    public function newUrl(
        ?string $scheme = null,
        ?string $host = null,
        ?int $port = null,
        ?string $user = null,
        ?string $pass = null,
        ?string $path = null,
        ?string $query = null,
        ?string $fragment = null,
    ) : Url
    {
        return new ReadonlyUrl(
            scheme: $scheme,
            host: $host,
            port: $port,
            user: $user,
            pass: $pass,
            path: $path,
            query: $query,
            fragment: $fragment,
        );
    }
}
