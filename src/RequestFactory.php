<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestStructFactory;
use RequestInterop\Interface\RequestTypeAliases;
use UploadInterop\Impl\UploadFactory;
use UploadInterop\Interface\UploadStructFactory;
use UploadInterop\Interface\UploadTypeAliases;
use UriInterop\Interface\UriStruct;

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
abstract class RequestFactory implements RequestStructFactory
{
    /** @var request_body_array */
    protected array $body;

    /** @var request_headers_array */
    protected array $headers;

    /** @var request_method_string */
    protected string $method;

    /** @var upload_structs_array */
    protected array $uploads;

    protected UriStruct $uri;

    public function __construct(
        protected RequestGlobals $globals = new RequestGlobals(),
        protected RequestBodyStream $bodyStream = new RequestBodyStream(
            'php://input',
        ),
        protected UploadStructFactory $uploadFactory = new UploadFactory(),
    ) {
        $this->headers = $this->getHeaders();
        $this->method = $this->getMethod();
        $this->body = $this->getBody();
        $this->uploads = $this->getUploads();
        $this->uri = $this->getUri();
    }

    abstract public function newRequest() : RequestStruct;

    abstract protected function getUri() : UriStruct;

    /**
     * @return request_body_array
     */
    protected function getBody() : array
    {
        return match ($this->getBodyType()) {
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
        $body = json_decode(
            (string) $this->bodyStream,
            true,
            512,
            JSON_BIGINT_AS_STRING,
        );
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
            $headers['content-length'] = (string) $this->globals
                ->_SERVER['CONTENT_LENGTH'];
        }

        if (isset($this->globals->_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $this->globals
                ->_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }

    /**
     * @return request_method_string
     */
    protected function getMethod() : string
    {
        $method = strtoupper($this->globals->_SERVER['REQUEST_METHOD'] ?? '');

        if ($method === 'POST' && isset($this->headers['x-http-method-override'])) {
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
        return $this->uploadFactory->newUploadsFromFiles($this->globals->_FILES);
    }

    /**
     * @return array{scheme:non-empty-string}
     */
    protected function getUriScheme() : array
    {
        $server = $this->globals->_SERVER + ['HTTPS' => ''];

        $isHttps = filter_var(
            $server['HTTPS'],
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        return $isHttps ? ['scheme' => 'https'] : ['scheme' => 'http'];
    }

    /**
     * @return array{host:string, port:?int}
     */
    protected function getUriHostAndPort() : array
    {
        $server = $this->globals->_SERVER
            + ['HTTP_HOST' => null, 'SERVER_ADDR' => null, 'SERVER_PORT' => null];

        if ($server['SERVER_PORT'] !== null) {
            $server['SERVER_PORT'] = (int) $server['SERVER_PORT'];
        }

        if (
            is_string($server['HTTP_HOST'])
            && preg_match(
                '~^(?<host>(\[.*]|[^:])*)(:(?<port>[^/?#]*))?$~x',
                (string) $server['HTTP_HOST'],
                $matches,
                PREG_UNMATCHED_AS_NULL,
            )
        ) {
            return [
                'host' => $matches['host'] ?? '',
                'port' => $matches['port'] === null
                    ? $server['SERVER_PORT']
                    : (int) $matches['port'],
            ];
        }

        if ($server['SERVER_ADDR'] === null) {
            throw new RequestException('Could not determine host and port.');
        }

        if (
            filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            return [
                'host' => (string) $server['SERVER_ADDR'],
                'port' => $server['SERVER_PORT'],
            ];
        }

        return [
            'host' => '[' . $server['SERVER_ADDR'] . ']',
            'port' => $server['SERVER_PORT'],
        ];
    }

    /**
     * @return array{path:string, query:?string}
     */
    protected function getUriPathAndQuery() : array
    {
        $server = $this->globals->_SERVER
            + [
                'IIS_WasUrlRewritten' => '',
                'PHP_SELF' => '',
                'QUERY_STRING' => null,
                'UNENCODED_URL' => '',
            ];

        if (
            $server['IIS_WasUrlRewritten'] === '1'
            && $server['UNENCODED_URL'] !== ''
        ) {
            $parts = explode('?', (string) $server['UNENCODED_URL'], 2);

            return ['path' => $parts[0], 'query' => $parts[1] ?? null];
        }

        if (isset($server['REQUEST_URI'])) {
            $parts = explode('?', $server['REQUEST_URI'], 2);
            $path = $parts[0];
            $query = $server['QUERY_STRING'] ?? $parts[1] ?? null;
            return ['path' => $path, 'query' => $query];
        }

        return [
            'path' => (string) $server['PHP_SELF'],
            'query' => $server['QUERY_STRING'],
        ];
    }
}
