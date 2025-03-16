<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use InvalidArgumentException;
use RequestInterop\Interface\RequestUrl;
use UriInterop\Impl\MutableUri;

class MutableRequestUrl extends MutableUri implements RequestUrl
{
    /**
     * @inheritdoc
     * @var ?non-empty-string $scheme
     */
    public ?string $scheme {
        set (?string $scheme) {
            $scheme = trim((string) $scheme);

            if ($scheme === '') {
                throw new InvalidArgumentException("Scheme component blank or not present.");
            }

            $this->scheme = $scheme;
        }
    }

    /**
     * @inheritdoc
     * @var ?non-empty-string $host
     */
    public ?string $host {
        set (?string $host) {
            $host = trim((string) $host);

            if ($host === '') {
                throw new InvalidArgumentException("Host component blank or not present.");
            }

            $this->host = $host;
        }
    }

    public function __construct(
        string $scheme,
        string $host,
        ?int $port = null,
        string $path = '',
        ?string $query = null,
    ) {
        parent::__construct(
            scheme: $scheme,
            host: $host,
            port: $port,
            path: $path,
            query: $query,
        );
    }
}
