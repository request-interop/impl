<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestStructFactory;
use RequestInterop\Interface\RequestTypeAliases;
use StreamInterop\Impl\ReadonlyFileStream;
use StreamInterop\Interface\StringableStream;
use UploadInterop\Interface\UploadStructFactory;
use UploadInterop\Impl\UploadFactory;
use UploadInterop\Interface\UploadTypeAliases;
use UriInterop\Impl\ReadonlyUri;
use UriInterop\Interface\UriStruct;

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
class RequestFactory implements RequestStructFactory
{
    public function __construct(
        protected RequestGlobals $requestGlobals = new RequestGlobals(),
        protected RequestUriFactory $requestUriFactory = new RequestUriFactory(),
        protected UploadStructFactory $uploadFactory = new UploadFactory(),
    ) {
    }

    /**
     * @inheritdoc
     * @param ReadonlyUri $uri
     * @param ReadonlyFileStream $body
     * @return Request
     */
    public function newRequest(
        ?StringableStream $body = null,
        ?array $cookies = null,
        ?array $files = null,
        ?array $headers = null,
        ?array $input = null,
        ?string $method = null,
        ?array $query = null,
        ?array $server = null,
        ?array $uploads = null,
        ?UriStruct $uri = null,
    ) : RequestStruct
    {
        // no dependencies
        $cookies ??= $this->requestGlobals->_COOKIE;
        $files ??= $this->requestGlobals->_FILES;
        $query ??= $this->requestGlobals->_GET;
        $server ??= $this->requestGlobals->_SERVER;

        // one dependency
        $body ??= $this->body('php://input');
        $headers ??= $this->headers($server);
        $uploads ??= $this->uploads($files);
        $uri ??= $this->uri($server);

        // two dependencies
        $method ??= $this->method($server, $headers);
        $input ??= $this->input($headers, $body);

        // instantiate
        return new Request(
            cookies: $cookies,
            files: $files,
            headers: $headers,
            input: $input,
            method: $method,
            query: $query,
            server: $server,
            uploads: $uploads,
            uri: $uri,
            body: $body,
        );
    }

    /**
     * @param string|resource $spec
     * @return ReadonlyFileStream
     */
    public function body(mixed $spec) : StringableStream
    {
        return new ReadonlyFileStream($spec);
    }

    /**
     * @param server_array $server
     * @return headers_array
     */
    public function headers(array $server) : array
    {
        $headers = [];

        // headers prefixed with HTTP_*
        foreach ($server as $key => $val) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                $key = str_replace('_', '-', strtolower($key));
                $headers[$key] = (string) $val;
            }
        }

        // RFC 3875 headers not prefixed with HTTP_*
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $server['CONTENT_LENGTH'];
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $server['CONTENT_TYPE'];
        }

        return $headers;
    }

    /**
     * @param headers_array $headers
     * @return input_array
     */
    public function input(array $headers, StringableStream $body) : array
    {
        return match($this->inputType($headers)) {
            'application/json' => $this->inputTypeJson($body),
            'application/xml' => $this->inputTypeXml($body),
            'text/xml' => $this->inputTypeXml($body),
            default => $this->requestGlobals->_POST,
        };
    }

    /**
     * @param headers_array $headers
     */
    public function inputType(array $headers) : ?string
    {
        $type = null;

        if (! isset($headers['content-type'])) {
            return $type;
        }

        /** @var string[] */
        $parts = explode(';', (string) $headers['content-type']);
        $part = (string) array_shift($parts);
        $regex = '/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+\/[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/';

        if (preg_match($regex, $part) === 1) {
            $type = strtolower($part);
        }

        return $type;
    }

    /**
     * @return input_array
     */
    public function inputTypeJson(StringableStream $body) : array
    {
        // THROW ON ERROR?
        /** @var ?input_array $input */
        $input = json_decode((string) $body, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($input) ? $input : [];
    }

    /**
     * @return input_array
     */
    public function inputTypeXml(StringableStream $body) : array
    {
        $oldInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string((string) $body);
        // capture errors, if any, and throw
        libxml_clear_errors();
        libxml_use_internal_errors($oldInternalErrors);
        $json = (string) json_encode($xml);

        /** @var ?input_array $input */
        $input = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($input) ? $input : [];
    }

    /**
     * @param server_array $server
     * @param headers_array $headers
     * @return method_string
     */
    public function method(array $server, array $headers) : string
    {
        $method = strtoupper($server['REQUEST_METHOD'] ?? '');

        if (
            $method === 'POST'
            && isset($headers['x-http-method-override'])
        ) {
            $method = $headers['x-http-method-override'];
        }

        $method = trim($method);

        if ($method === '') {
            throw new RequestException('Could not determine HTTP method.');
        }

        return strtoupper((string) $method);
    }

    /**
     * @param files_array $files
     * @return uploads_array
     */
    public function uploads(array $files) : array
    {
        return $this->uploadFactory->newUploadsFromFiles($files);
    }

    /**
     * @param server_array $server
     */
    public function uri(array $server) : RequestUri
    {
        return $this->requestUriFactory->newRequestUri($server);
    }
}
