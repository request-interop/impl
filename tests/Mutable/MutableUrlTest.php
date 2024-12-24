<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\UrlTestCase;
use RequestInterop\Interface\Url;

class MutableUrlTest extends UrlTestCase
{
    /**
     * @return MutableUrl
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
        return new MutableUrl(
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
