<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use InvalidArgumentException;
use RequestInterop\Interface\RequestUrl;
use UriInterop\Impl\ReadonlyUri;

class ReadonlyRequestUrl extends ReadonlyUri implements RequestUrl
{
    /**
     * @param non-empty-string $scheme
     * @param non-empty-string $host
     */
    public function __construct(
        string $scheme,
        string $host,
        ?int $port = null,
        string $path = '',
        ?string $query = null,
    ) {
        $scheme = trim((string) $scheme);

        if ($scheme === '') {
            throw new InvalidArgumentException('Scheme component blank or not present.');
        }

        $host = trim((string) $host);

        if ($host === '') {
            throw new InvalidArgumentException('Host component blank or not present.');
        }

        parent::__construct(
            scheme: $scheme,
            host: $host,
            port: $port,
            path: $path,
            query: $query,
        );
    }
}
