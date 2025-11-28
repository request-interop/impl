<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestTypeAliases;
use StreamInterop\Impl\ReadonlyFileStream;
use UploadInterop\Interface\UploadTypeAliases;

/**
 * @phpstan-import-type request_cookies_array from RequestTypeAliases
 *
 * @phpstan-import-type upload_files_array from UploadTypeAliases
 *
 * @phpstan-import-type request_headers_array from RequestTypeAliases
 *
 * @phpstan-import-type request_body_array from RequestTypeAliases
 *
 * @phpstan-import-type request_method_string from RequestTypeAliases
 *
 * @phpstan-import-type request_query_array from RequestTypeAliases
 *
 * @phpstan-import-type request_server_array from RequestTypeAliases
 *
 * @phpstan-import-type upload_structs_array from UploadTypeAliases
 */
readonly class Request implements RequestStruct
{
    /**
     * @param request_body_array $body
     * @param request_cookies_array $cookies
     * @param request_headers_array $headers
     * @param request_method_string $method
     * @param request_query_array $query
     * @param request_server_array $server
     * @param upload_structs_array $uploads
     */
    public function __construct(
        public array $body,
        public array $cookies,
        public array $headers,
        public ReadonlyFileStream $bodyStream,
        public string $method,
        public array $query,
        public array $server,
        public array $uploads,
        public RequestUri $uri,
    ) {
    }
}
