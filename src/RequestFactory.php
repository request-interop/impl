<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Interface\RequestStructFactory;
use RequestInterop\Interface\RequestTypeAliases;
use StreamInterop\Impl\ReadonlyFileStream;
use StreamInterop\Interface\StringableStream;
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
    /**
     * @var cookies_array
     */
    protected array $_cookie;

    /**
     * @var files_array
     */
    protected array $_files;

    /**
     * @var query_array
     */
    protected array $_get;

    /**
     * @var input_array
     */
    protected array $_post;

    /**
     * @var server_array
     */
    protected array $_server;

    public function __construct(
        protected UploadFactory $uploadFactory = new UploadFactory(),
    ) {
        /** @var cookies_array $_COOKIE */
        $this->_cookie = $_COOKIE;

        /** @var files_array $_FILES */
        $this->_files = $_FILES;

        /** @var query_array $_GET */
        $this->_get = $_GET;

        /** @var input_array $_POST */
        $this->_post = $_POST;

        /** @var server_array $_SERVER */
        $this->_server = $_SERVER;
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
        $cookies ??= $this->cookies();
        $files ??= $this->files();
        $query ??= $this->query();
        $server ??= $this->server();

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
     */
    public function body(mixed $spec) : ReadonlyFileStream
    {
        return new ReadonlyFileStream($spec);
    }

    /**
     * @return cookies_array
     */
    public function cookies() : array
    {
        return $this->_cookie;
    }

    /**
     * @return query_array
     */
    public function query() : array
    {
        return $this->_get;
    }

    /**
     * @return server_array
     */
    public function server() : array
    {
        return $this->_server;
    }

    /**
     * @return files_array
     */
    public function files() : array
    {
        return $this->_files;
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
            default => $this->_post,
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
        $xml = simplexml_load_string((string) $body);
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
    public function uri(array $server) : ReadonlyUri
    {
        $args = $this->uriScheme($server)
            + $this->uriHostAndPort($server)
            + $this->uriPathAndQuery($server);

        return new ReadonlyUri(...$args);
    }

    /**
     * @param server_array $server
     * @return array{scheme:non-empty-string}
     */
    public function uriScheme(array $server) : array
    {
        $server += ['HTTPS' => ''];

        $isHttps = filter_var(
            $server['HTTPS'],
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        return $isHttps ? ['scheme' => 'https'] : ['scheme' => 'http'];
    }

    /**
     * @param server_array $server
     * @return array{host:string, port:?int}
     */
    public function uriHostAndPort(array $server) : array
    {
        $server += [
            'HTTP_HOST' => null,
            'SERVER_ADDR' => null,
            'SERVER_PORT' => null,
        ];

        if ($server['SERVER_PORT'] !== null) {
            $server['SERVER_PORT'] = (int) $server['SERVER_PORT'];
        }

        if (
            is_string($server['HTTP_HOST'])
            && preg_match(
                '~^(?<host>(\[.*]|[^:])*)(:(?<port>[^/?#]*))?$~x',
                (string) $server['HTTP_HOST'],
                $matches,
                PREG_UNMATCHED_AS_NULL
            )
        ) {
            return [
                'host' => $matches['host'],
                'port' => $matches['port'] === null
                    ? $server['SERVER_PORT']
                    : (int) $matches['port'],
            ];
        }

        if ($server['SERVER_ADDR'] === null) {
            throw new RequestException('Could not determine host and port.');
        }

        if (
            filter_var(
                $server['SERVER_ADDR'],
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            )
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
     * @param server_array $server
     * @return array{path:string, query:?string}
     */
    public function uriPathAndQuery(array $server) : array
    {
        $server += [
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

            return [
                'path' => $parts[0],
                'query' => $parts[1] ?? null
            ];
        }

        if (isset($server['REQUEST_URI'])) {
            $parts = explode('?', $server['REQUEST_URI'], 2);
            $path = $parts[0];
            $query = $parts[1] ?? null;
            $query = ($server['QUERY_STRING'] !== null) ? $server['QUERY_STRING'] : $query;
            return ['path' => $path, 'query' => $query];
        }

        return ['path' => (string) $server['PHP_SELF'], 'query' => $server['QUERY_STRING']];
    }
}
