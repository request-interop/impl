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
     * @param ReadonlyFileStream $input
     * @param RequestUri $uri
     * @return Request
     */
    public function newRequest(
        ?array $body = null,
        ?array $cookies = null,
        ?array $headers = null,
        ?StringableStream $input = null,
        ?string $method = null,
        ?array $query = null,
        ?array $server = null,
        ?array $uploads = null,
        ?UriStruct $uri = null,
    ) : RequestStruct
    {
        // no dependencies
        $cookies ??= $this->cookies();
        $query ??= $this->query();
        $server ??= $this->server();

        // one dependency
        $input ??= $this->input($this->requestGlobals->inputStream);
        $headers ??= $this->headers($server);
        $uploads ??= $this->uploads($this->requestGlobals->_FILES);
        $uri ??= $this->uri($server);

        // two dependencies
        $method ??= $this->method($server, $headers);
        $body ??= $this->body($headers, $input);

        // instantiate
        return new Request(
            body: $body,
            cookies: $cookies,
            headers: $headers,
            input: $input,
            method: $method,
            query: $query,
            server: $server,
            uploads: $uploads,
            uri: $uri,
        );
    }

    /**
     * @param headers_array $headers
     * @return body_array
     */
    public function body(array $headers, StringableStream $input) : array
    {
        return match($this->bodyType($headers)) {
            'application/json' => $this->bodyTypeJson($input),
            'application/xml' => $this->bodyTypeXml($input),
            'text/xml' => $this->bodyTypeXml($input),
            default => $this->requestGlobals->_POST,
        };
    }

    /**
     * @param headers_array $headers
     */
    public function bodyType(array $headers) : ?string
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
     * @return body_array
     */
    public function bodyTypeJson(StringableStream $input) : array
    {
        // THROW ON ERROR?
        /** @var ?body_array $body */
        $body = json_decode((string) $input, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($body) ? $body : [];
    }

    /**
     * @return body_array
     */
    public function bodyTypeXml(StringableStream $input) : array
    {
        $oldInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string((string) $input);
        libxml_clear_errors();
        libxml_use_internal_errors($oldInternalErrors);
        $json = (string) json_encode($xml);

        /** @var ?body_array $body */
        $body = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($body) ? $body : [];
    }

    /**
     * @return cookies_array
     */
    public function cookies() : array
    {
        return $this->requestGlobals->_COOKIE;
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
     * @param string|resource $spec
     * @return ReadonlyFileStream
     */
    public function input(mixed $spec) : StringableStream
    {
        return new ReadonlyFileStream($spec);
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
     * @return query_array
     */
    public function query() : array
    {
        return $this->requestGlobals->_GET;
    }

    /**
     * @return server_array
     */
    public function server() : array
    {
        return $this->requestGlobals->_SERVER;
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
