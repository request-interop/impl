<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\Request;
use RequestInterop\Interface\RequestTypeAliases;

/**
 * @phpstan-import-type cookies_array from RequestTypeAliases
 * @phpstan-import-type files_array from RequestTypeAliases
 * @phpstan-import-type headers_array from RequestTypeAliases
 * @phpstan-import-type input_array from RequestTypeAliases
 * @phpstan-import-type method_string from RequestTypeAliases
 * @phpstan-import-type query_array from RequestTypeAliases
 * @phpstan-import-type server_array from RequestTypeAliases
 * @phpstan-import-type uploads_array from RequestTypeAliases
 */
readonly class ReadonlyRequest implements Request
{
    /**
     * @param cookies_array $cookies
     * @param files_array $files
     * @param headers_array $headers
     * @param input_array $input
     * @param method_string $method
     * @param query_array $query
     * @param server_array $server
     * @param uploads_array $uploads
     */
    public function __construct(
        public readonly array $cookies,
        public readonly array $files,
        public readonly array $headers,
        public readonly array $input,
        public readonly string $method,
        public readonly array $query,
        public readonly array $server,
        public readonly array $uploads,
        public readonly ReadonlyRequestUrl $url,
        public readonly ReadonlyRequestBody $body,
    ) {
    }
}
