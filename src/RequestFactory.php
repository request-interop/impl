<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestStructFactory;
use RequestInterop\Interface\RequestTypeAliases;
use StreamInterop\Interface\StringableStream;
use UploadInterop\Interface\UploadStructFactory;
use UploadInterop\Impl\UploadFactory;
use UploadInterop\Interface\UploadTypeAliases;

/**
 * @phpstan-import-type request_cookies_array from RequestTypeAliases
 * @phpstan-import-type request_headers_array from RequestTypeAliases
 * @phpstan-import-type request_body_array from RequestTypeAliases
 * @phpstan-import-type request_method_string from RequestTypeAliases
 * @phpstan-import-type request_query_array from RequestTypeAliases
 * @phpstan-import-type request_server_array from RequestTypeAliases
 * @phpstan-import-type upload_files_array from UploadTypeAliases
 * @phpstan-import-type upload_structs_array from UploadTypeAliases
 */
class RequestFactory implements RequestStructFactory
{
    /** @var request_body_array */
    protected array $body;

    /** @var request_headers_array */
    protected array $headers;

    /** @var request_method_string */
    protected string $method;

    /** @var upload_structs_array */
    protected array $uploads;

    protected RequestUri $uri;

    public function __construct(
        protected RequestGlobals $globals = new RequestGlobals(),
        protected RequestBodyStream $bodyStream = new RequestBodyStream('php://input'),
        protected RequestUriFactory $requestUriFactory = new RequestUriFactory(),
        protected UploadStructFactory $uploadFactory = new UploadFactory(),
    ) {
        $this->headers = $this->getHeaders();
        $this->method = $this->getMethod();
        $this->body = $this->getBody();
        $this->uploads = $this->getUploads();
        $this->uri = $this->getUri();
    }

    /**
     * @inheritdoc
     * @return Request
     */
    public function newRequest() : RequestStruct
    {
        return new Request(
            body: $this->body,
            bodyStream: $this->bodyStream,
            cookies: $this->globals->_COOKIE,
            headers: $this->headers,
            method: $this->method,
            query: $this->globals->_GET,
            server: $this->globals->_SERVER,
            uploads: $this->uploads,
            uri: $this->uri,
        );
    }

    /**
     * @return request_body_array
     */
    protected function getBody() : array
    {
        return match($this->getBodyType()) {
            'application/json' => $this->getBodyTypeJson(),
            'application/xml' => $this->getBodyTypeXml(),
            'text/xml' => $this->getBodyTypeXml(),
            default => $this->globals->_POST,
        };
    }

    protected function getBodyType() : ?string
    {
        $type = null;

        if (! isset($this->headers['content-type'])) {
            return $type;
        }

        /** @var string[] */
        $parts = explode(';', (string) $this->headers['content-type']);
        $part = (string) array_shift($parts);
        $regex = '/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+\/[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/';

        if (preg_match($regex, $part) === 1) {
            $type = strtolower($part);
        }

        return $type;
    }

    /**
     * @return request_body_array
     */
    protected function getBodyTypeJson() : array
    {
        // THROW ON ERROR?
        /** @var ?request_body_array $body */
        $body = json_decode((string) $this->bodyStream, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($body) ? $body : [];
    }

    /**
     * @return request_body_array
     */
    protected function getBodyTypeXml() : array
    {
        $oldInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string((string) $this->bodyStream);
        libxml_clear_errors();
        libxml_use_internal_errors($oldInternalErrors);
        $json = (string) json_encode($xml);

        /** @var ?request_body_array $body */
        $body = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($body) ? $body : [];
    }

    /**
     * @return request_headers_array
     */
    protected function getHeaders() : array
    {
        $headers = [];

        // headers prefixed with HTTP_*
        foreach ($this->globals->_SERVER as $key => $val) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                $key = str_replace('_', '-', strtolower($key));
                $headers[$key] = (string) $val;
            }
        }

        // RFC 3875 headers not prefixed with HTTP_*
        if (isset($this->globals->_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $this->globals->_SERVER['CONTENT_LENGTH'];
        }

        if (isset($this->globals->_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $this->globals->_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }

    /**
     * @return request_method_string
     */
    protected function getMethod() : string
    {
        $method = strtoupper($this->globals->_SERVER['REQUEST_METHOD'] ?? '');

        if (
            $method === 'POST'
            && isset($this->headers['x-http-method-override'])
        ) {
            $method = $this->headers['x-http-method-override'];
        }

        $method = trim($method);

        if ($method === '') {
            throw new RequestException('Could not determine HTTP method.');
        }

        return strtoupper((string) $method);
    }

    /**
     * @return upload_structs_array
     */
    protected function getUploads()
    {
        return $this->uploadFactory->newUploadsFromFiles(
            $this->globals->_FILES,
        );
    }

    protected function getUri() : RequestUri
    {
        return $this->requestUriFactory->newRequestUri(
            $this->globals->_SERVER,
        );
    }
}
