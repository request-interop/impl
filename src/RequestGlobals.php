<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestTypeAliases;
use UploadInterop\Interface\UploadTypeAliases;

/**
 * @phpstan-import-type cookies_array from RequestTypeAliases
 *
 * @phpstan-import-type files_array from UploadTypeAliases
 *
 * @phpstan-import-type body_array from RequestTypeAliases
 *
 * @phpstan-import-type query_array from RequestTypeAliases
 *
 * @phpstan-import-type server_array from RequestTypeAliases
 */
class RequestGlobals
{
    /** @var cookies_array */
    public readonly array $_COOKIE;

    /** @var query_array */
    public readonly array $_GET;

    /** @var files_array */
    public readonly array $_FILES;

    /** @var body_array */
    public readonly array $_POST;

    /** @var server_array */
    public readonly array $_SERVER;

    /** @var string|resource */
    public readonly mixed $inputStream;

    public function __construct()
    {
        /** @var cookies_array $_COOKIE */
        $this->_COOKIE = $_COOKIE;

        /** @var query_array $_GET */
        $this->_GET = $_GET;

        /** @var files_array $_FILES */
        $this->_FILES = $_FILES;

        /** @var body_array $_POST */
        $this->_POST = $_POST;

        /** @var server_array $_SERVER */
        $this->_SERVER = $_SERVER;

        $this->inputStream = 'php://input';
    }
}
