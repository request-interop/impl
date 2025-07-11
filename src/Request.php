<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestTypeAliases;
use StreamInterop\Impl\ReadonlyFileStream;
use UploadInterop\Interface\UploadTypeAliases;
use UriInterop\Impl\ReadonlyUri;

/**
 * @phpstan-import-type cookies_array from RequestTypeAliases
 *
 * @phpstan-import-type files_array from UploadTypeAliases
 *
 * @phpstan-import-type headers_array from RequestTypeAliases
 *
 * @phpstan-import-type body_array from RequestTypeAliases
 *
 * @phpstan-import-type method_string from RequestTypeAliases
 *
 * @phpstan-import-type query_array from RequestTypeAliases
 *
 * @phpstan-import-type server_array from RequestTypeAliases
 *
 * @phpstan-import-type uploads_array from UploadTypeAliases
 */
readonly class Request implements RequestStruct
{
    /**
     * @param body_array $body
     * @param cookies_array $cookies
     * @param headers_array $headers
     * @param method_string $method
     * @param query_array $query
     * @param server_array $server
     * @param uploads_array $uploads
     */
    public function __construct(
        public array $body,
        public array $cookies,
        public array $headers,
        public ReadonlyFileStream $input,
        public string $method,
        public array $query,
        public array $server,
        public array $uploads,
        public RequestUri $uri,
    ) {
    }
}
