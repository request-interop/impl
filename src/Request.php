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
 * @phpstan-import-type input_array from RequestTypeAliases
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
        public ReadonlyFileStream $body,
        public array $cookies,
        public array $files,
        public array $headers,
        public array $input,
        public string $method,
        public array $query,
        public array $server,
        public array $uploads,
        public ReadonlyUri $uri,
    ) {
    }
}
