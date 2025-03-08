<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\Request;
use RequestInterop\Interface\RequestBody;
use RequestInterop\Interface\RequestFactory;
use RequestInterop\Interface\RequestTypeAliases;
use RequestInterop\Interface\RequestUpload;
use RequestInterop\Interface\RequestUrl;
use RuntimeException;

/**
 * @phpstan-import-type cookies_array from RequestTypeAliases
 * @phpstan-import-type files_array from RequestTypeAliases
 * @phpstan-import-type files_item_array from RequestTypeAliases
 * @phpstan-import-type files_group_array from RequestTypeAliases
 * @phpstan-import-type headers_array from RequestTypeAliases
 * @phpstan-import-type input_array from RequestTypeAliases
 * @phpstan-import-type method_string from RequestTypeAliases
 * @phpstan-import-type query_array from RequestTypeAliases
 * @phpstan-import-type server_array from RequestTypeAliases
 * @phpstan-import-type uploads_array from RequestTypeAliases
 * @phpstan-type url_properties_array array{
 *     scheme:non-empty-string,
 *     host:non-empty-string,
 *     port:?int,
 *     path:string,
 *     query:string
 * }
 *
 */
abstract class RequestFactoryImpl implements RequestFactory
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

    /**
     * @var string|resource
     */
    protected mixed $phpInput;

    /**
     * @param ?cookies_array $_cookie
     * @param ?files_array $_files
     * @param ?query_array $_get
     * @param ?input_array $_post
     * @param ?server_array $_server
     * @param null|string|resource $phpInput
     */
    public function __construct(
        ?array $_cookie = null,
        ?array $_files = null,
        ?array $_get = null,
        ?array $_post = null,
        ?array $_server = null,
        mixed $phpInput = null,
    ) {
        /** @var cookies_array $cookie */
        $cookie = $_COOKIE;
        $this->_cookie = $_cookie ?? $cookie;

        /** @var files_array $files */
        $files = $_FILES;
        $this->_files = $_files ?? $files;

        /** @var query_array $get */
        $get = $_GET;
        $this->_get = $_get ?? $get;

        /** @var input_array $post */
        $post = $_POST;
        $this->_post = $_post ?? $post;

        /** @var server_array $server */
        $server = $_SERVER;
        $this->_server = $_server ?? $server;

        $this->phpInput ??= $phpInput ?? 'php://input';
    }

    /**
     * @inheritdoc
     */
    abstract public function newRequest(
        ?array $cookies = null,
        ?array $files = null,
        ?array $headers = null,
        ?array $input = null,
        ?string $method = null,
        ?array $query = null,
        ?array $server = null,
        ?array $uploads = null,
        ?RequestUrl $url = null,
        ?RequestBody $body = null,
    ) : Request;

    /**
     * @inheritdoc
     */
    abstract public function newRequestUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
        ?RequestBody $body = null,
    ) : RequestUpload;

    /**
     * @param server_array $server
     */
    abstract public function newRequestUrl(?array $server = null) : RequestUrl;

    /**
     * @inheritdoc
     */
    abstract public function newRequestBody(mixed $spec) : RequestBody;

    /**
     * @param ?headers_array $headers
     */
    public function contentType(?array $headers = null) : ?string
    {
        $headers ??= $this->headersArray();
        $contentType = null;

        if (! isset($headers['content-type'])) {
            return $contentType;
        }

        /** @var string[] */
        $parts = explode(';', $headers['content-type']);
        $part = (string) array_shift($parts);
        $regex = '/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+\/[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/';

        if (preg_match($regex, $part) === 1) {
            $contentType = strtolower($part);
        }

        return $contentType;
    }

    /**
     * @return cookies_array
     */
    public function cookiesArray() : array
    {
        return $this->_cookie;
    }

    /**
     * @return files_array
     */
    public function filesArray() : array
    {
        return $this->_files;
    }

    /**
     * @param server_array $server
     * @return headers_array
     */
    public function headersArray(?array $server = null) : array
    {
        $server ??= $this->serverArray();

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
     * @param ?headers_array $headers
     * @return input_array
     */
    public function inputArray(?array $headers = null, ?RequestBody $body = null) : array
    {
        $headers ??= $this->headersArray();
        $body ??= $this->newRequestBody($this->phpInput);

        return match($this->contentType($headers)) {
            'application/json' => $this->inputArrayJson($body),
            'application/xml' => $this->inputArrayXml($body),
            'text/xml' => $this->inputArrayXml($body),
            default => $this->_post,
        };
    }

    /**
     * @return input_array
     */
    public function inputArrayJson(?RequestBody $body = null) : array
    {
        $body ??= $this->newRequestBody($this->phpInput);

        /** @var ?input_array $input */
        $input = json_decode((string) $body, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($input) ? $input : [];
    }

    /**
     * @return input_array
     */
    public function inputArrayXml(?RequestBody $body = null) : array
    {
        $body ??= $this->newRequestBody($this->phpInput);
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
     * @param ?server_array $server
     * @param ?headers_array $headers
     * @return method_string
     */
    public function methodString(?array $server = null, ?array $headers = null) : string
    {
        $server ??= $this->serverArray();
        $headers ??= $this->headersArray();
        $method = strtoupper($server['REQUEST_METHOD'] ?? '');

        if (
            $method === 'POST'
            && isset($headers['x-http-method-override'])
        ) {
            $method = $headers['x-http-method-override'];
        }

        return strtoupper($method);
    }

    /**
     * @return query_array
     */
    public function queryArray() : array
    {
        return $this->_get;
    }

    /**
     * @return server_array
     */
    public function serverArray() : array
    {
        return $this->_server;
    }

    /**
     * @param files_array $files
     * @return uploads_array
     */
    public function uploadsArray(?array $files = null) : array
    {
        $files ??= $this->filesArray();
        return $this->uploadsArrayFiles($files);
    }

    /**
     * @param files_array $files
     * @return uploads_array
     */
    public function uploadsArrayFiles(array $files) : array
    {
        $uploads = [];

        /** @var files_array|files_item_array|files_group_array $file */
        foreach ($files as $field => $file) {
            if (is_string($file['tmp_name'] ?? null)) {
                /** @var files_item_array $file */
                $uploads[$field] = $this->newRequestUpload(
                    tmpName: $file['tmp_name'],
                    error: $file['error'],
                    name: $file['name'] ?? null,
                    fullPath: $file['full_path'] ?? null,
                    type: $file['type'] ?? null,
                    size: $file['size'] ?? null,
                );

                continue;
            }

            if (is_array($file['tmp_name'] ?? null)) {
                /** @var files_group_array $file */
                $group = [];

                foreach ($file['tmp_name'] as $key => $val) {
                    $group[$key]['tmp_name'] = $file['tmp_name'][$key];
                    $group[$key]['error'] = $file['error'][$key];
                    $group[$key]['name'] = $file['name'][$key] ?? null;
                    $group[$key]['full_path'] = $file['full_path'][$key] ?? null;
                    $group[$key]['type'] = $file['type'][$key] ?? null;
                    $group[$key]['size'] = $file['size'][$key] ?? null;
                }

                /** @var files_array $group */
                $uploads[$field] = $this->uploadsArrayFiles($group);
                continue;
            }

            /** @var files_array $file */
            $uploads[$field] = $this->uploadsArrayFiles($file);
        }

        /** @var uploads_array */
        return $uploads;
    }

    /**
     * @param ?server_array $server
     * @return url_properties_array
     */
    public function urlProperties(?array $server = null) : array
    {
        $server ??= $this->serverArray();

        /** @var url_properties_array */
        return $this->urlScheme($server)
            + $this->urlHostAndPort($server)
            + $this->urlPathAndQuery($server);
    }

    /**
     * @param ?server_array $server
     * @return array{scheme:non-empty-string}
     */
    public function urlScheme($server = null) : array
    {
        $server ??= $this->serverArray();
        $server += ['HTTPS' => ''];

        $isHttps = filter_var(
            $server['HTTPS'],
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        return $isHttps ? ['scheme' => 'https'] : ['scheme' => 'http'];
    }

    /**
     * @param ?server_array $server
     * @return array{host:string, port:?int}
     */
    public function urlHostAndPort($server = null) : array
    {
        $server ??= $this->serverArray();

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
            throw new RuntimeException('Could not determine host and port.');
        }

        if (filter_var(
            $server['SERVER_ADDR'],
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4
        )) {
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
     * @param ?server_array $server
     * @return array{path:string, query:string}
     */
    public function urlPathAndQuery($server = null) : array
    {
        $server ??= $this->serverArray();

        $server += [
            'IIS_WasUrlRewritten' => '',
            'PHP_SELF' => '',
            'QUERY_STRING' => '',
            'UNENCODED_URL' => '',
        ];

        if (
            $server['IIS_WasUrlRewritten'] === '1'
            && $server['UNENCODED_URL'] !== ''
        ) {
            [$path, $query] = explode('?', $server['UNENCODED_URL'], 2) + [1 => ''];
            return ['path' => $path, 'query' => $query];
        }

        if (isset($server['REQUEST_URI'])) {
            [$path, $query] = explode('?', $server['REQUEST_URI'], 2) + [1 => ''];
            $query = ($server['QUERY_STRING'] !== '') ? $server['QUERY_STRING'] : $query;
            return ['path' => $path, 'query' => $query];
        }

        return ['path' => $server['PHP_SELF'], 'query' => $server['QUERY_STRING']];
    }
}
