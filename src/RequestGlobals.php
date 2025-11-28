<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestTypeAliases;
use UploadInterop\Interface\UploadTypeAliases;

/**
 * @phpstan-import-type request_cookies_array from RequestTypeAliases
 * @phpstan-import-type request_body_array from RequestTypeAliases
 * @phpstan-import-type request_query_array from RequestTypeAliases
 * @phpstan-import-type request_server_array from RequestTypeAliases
 * @phpstan-import-type upload_files_array from UploadTypeAliases
 */
readonly class RequestGlobals
{
    /** @var request_cookies_array */
    public array $_COOKIE;

    /** @var request_query_array */
    public array $_GET;

    /** @var upload_files_array */
    public array $_FILES;

    /** @var request_body_array */
    public array $_POST;

    /** @var request_server_array */
    public array $_SERVER;

    public function __construct()
    {
        /** @var request_cookies_array $_COOKIE */
        $this->_COOKIE = $_COOKIE;

        /** @var request_query_array $_GET */
        $this->_GET = $_GET;

        /** @var upload_files_array $_FILES */
        $this->_FILES = $_FILES;

        /** @var request_body_array $_POST */
        $this->_POST = $_POST;

        /** @var request_server_array $_SERVER */
        $this->_SERVER = $_SERVER;
    }
}
