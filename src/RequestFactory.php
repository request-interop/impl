<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\Body;
use RequestInterop\Interface\Factory;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\Upload;
use RequestInterop\Interface\Url;

/**
 * @phpstan-import-type BodyResource from Body
 * @phpstan-import-type CookiesArray from Request
 * @phpstan-import-type FilesArray from Request
 * @phpstan-import-type FilesArrayItem from Request
 * @phpstan-import-type FilesArrayGroup from Request
 * @phpstan-import-type HeadersArray from Request
 * @phpstan-import-type InputArray from Request
 * @phpstan-import-type MethodString from Request
 * @phpstan-import-type QueryArray from Request
 * @phpstan-import-type ServerArray from Request
 * @phpstan-import-type UploadsArray from Request
 * @phpstan-import-type UrlArray from Url
 */
abstract class RequestFactory implements Factory
{
    /**
     * @var CookiesArray
     */
    protected array $_cookie;

    /**
     * @var FilesArray
     */
    protected array $_files;

    /**
     * @var QueryArray
     */
    protected array $_get;

    /**
     * @var InputArray
     */
    protected array $_post;

    /**
     * @var ServerArray
     */
    protected array $_server;

    /**
     * @var string|BodyResource
     */
    protected mixed $body;

    /**
     * @param ?CookiesArray $_cookie
     * @param ?FilesArray $_files
     * @param ?QueryArray $_get
     * @param ?InputArray $_post
     * @param ?ServerArray $_server
     * @param null|string|BodyResource $body
     */
    public function __construct(
        ?array $_cookie = null,
        ?array $_files = null,
        ?array $_get = null,
        ?array $_post = null,
        ?array $_server = null,
        mixed $body = null,
    ) {
        /** @var CookiesArray $cookie */
        $cookie = $_COOKIE;
        $this->_cookie = $_cookie ?? $cookie;

        /** @var FilesArray $files */
        $files = $_FILES;
        $this->_files = $_files ?? $files;

        /** @var QueryArray $get */
        $get = $_GET;
        $this->_get = $_get ?? $get;

        /** @var InputArray $post */
        $post = $_POST;
        $this->_post = $_post ?? $post;

        /** @var ServerArray $server */
        $server = $_SERVER;
        $this->_server = $_server ?? $server;

        $this->body = $body ?? 'php://input';
    }

    public function contentType() : ?string
    {
        $contentType = null;

        if (! isset($this->_server['CONTENT_TYPE'])) {
            return $contentType;
        }

        /** @var string[] */
        $parts = explode(';', $this->_server['CONTENT_TYPE']);
        $part = (string) array_shift($parts);
        $regex = '/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+\/[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/';

        if (preg_match($regex, $part) === 1) {
            $contentType = strtolower($part);
        }

        return $contentType;
    }

    /**
     * @return CookiesArray
     */
    public function cookiesArray() : array
    {
        return $this->_cookie;
    }

    /**
     * @return FilesArray
     */
    public function filesArray() : array
    {
        return $this->_files;
    }

    /**
     * @return HeadersArray
     */
    public function headersArray() : array
    {
        $headers = [];

        // headers prefixed with HTTP_*
        foreach ($this->_server as $key => $val) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                $key = str_replace('_', '-', strtolower($key));
                $headers[$key] = (string) $val;
            }
        }

        // RFC 3875 headers not prefixed with HTTP_*
        if (isset($this->_server['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $this->_server['CONTENT_LENGTH'];
        }

        if (isset($this->_server['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $this->_server['CONTENT_TYPE'];
        }

        return $headers;
    }

    /**
     * @return InputArray
     */
    public function inputArray() : array
    {
        return match($this->contentType()) {
            'application/json' => $this->inputArrayJson(),
            'application/xml' => $this->inputArrayXml(),
            'text/xml' => $this->inputArrayXml(),
            default => $this->_post,
        };
    }

    /**
     * @return InputArray
     */
    public function inputArrayJson() : array
    {
        $body = (string) stream_get_contents($this->bodyResource());

        /** @var ?InputArray $input */
        $input = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($input) ? $input : [];
    }

    /**
     * @return InputArray
     */
    public function inputArrayXml() : array
    {
        $oldInternalErrors = libxml_use_internal_errors(true);
        $body = (string) stream_get_contents($this->bodyResource());
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($oldInternalErrors);
        $json = (string) json_encode($xml);

        /** @var ?InputArray $input */
        $input = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        return is_array($input) ? $input : [];
    }

    /**
     * @return MethodString
     */
    public function methodString() : string
    {
        $method = strtoupper($this->_server['REQUEST_METHOD'] ?? '');

        if (
            $method === 'POST'
            && isset($this->_server['HTTP_X_HTTP_METHOD_OVERRIDE'])
        ) {
            $method = $this->_server['HTTP_X_HTTP_METHOD_OVERRIDE'];
        }

        return strtoupper($method);
    }

    /**
     * @return QueryArray
     */
    public function queryArray() : array
    {
        return $this->_get;
    }

    /**
     * @return BodyResource
     */
    public function bodyResource() : mixed
    {
        if (is_string($this->body)) {
            $body = fopen($this->body, 'rb');
            assert(is_resource($body));
            $this->body = $body;
        }

        return $this->body;
    }

    /**
     * @return ServerArray
     */
    public function serverArray() : array
    {
        return $this->_server;
    }

    /**
     * @return UploadsArray
     */
    public function uploadsArray() : array
    {
        return $this->uploadsArrayFiles($this->_files);
    }

    /**
     * @param FilesArray $files
     * @return UploadsArray
     */
    public function uploadsArrayFiles(array $files) : array
    {
        $uploads = [];

        /** @var FilesArray|FilesArrayItem|FilesArrayGroup $file */
        foreach ($files as $field => $file) {
            if (is_string($file['tmp_name'] ?? null)) {
                /** @var FilesArrayItem $file */
                $uploads[$field] = $this->newUpload(
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
                /** @var FilesArrayGroup $file */
                $group = [];

                foreach ($file['tmp_name'] as $key => $val) {
                    $group[$key]['tmp_name'] = $file['tmp_name'][$key];
                    $group[$key]['error'] = $file['error'][$key];
                    $group[$key]['name'] = $file['name'][$key] ?? null;
                    $group[$key]['full_path'] = $file['full_path'][$key] ?? null;
                    $group[$key]['type'] = $file['type'][$key] ?? null;
                    $group[$key]['size'] = $file['size'][$key] ?? null;
                }

                $uploads[$field] = $this->uploadsArrayFiles($group);
                continue;
            }

            /** @var FilesArray $file */
            $uploads[$field] = $this->uploadsArrayFiles($file);
        }

        return $uploads;
    }

    /**
     * @return UrlArray
     */
    public function urlArray() : array
    {
        return $this->urlArrayScheme()
            + $this->urlArrayUser()
            + $this->urlArrayHost()
            + $this->urlArrayData()
            + ['fragment' => null];
    }

    /**
     * @return non-empty-array{scheme:lowercase-string}
     */
    public function urlArrayScheme() : array
    {
        $server = $this->_server + ['HTTPS' => ''];

        $isHttps = filter_var(
            $server['HTTPS'],
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        return ['scheme' => $isHttps ? 'https' : 'http'];
    }

    /**
     * @return non-empty-array{user:?string, pass:?string}
     */
    public function urlArrayUser() : array
    {
        $server = $this->_server + [
            'PHP_AUTH_USER' => null,
            'PHP_AUTH_PW' => null,
            'HTTP_AUTHORIZATION' => ''
        ];

        if ($server['PHP_AUTH_USER'] !== null) {
            $server['PHP_AUTH_USER'] = (string) $server['PHP_AUTH_USER'];
        }

        if ($server['PHP_AUTH_PW'] !== null) {
            $server['PHP_AUTH_PW'] = (string) $server['PHP_AUTH_PW'];
        }

        $server['HTTP_AUTHORIZATION'] = (string) $server['HTTP_AUTHORIZATION'];
        $user = $server['PHP_AUTH_USER'];
        $pass = $server['PHP_AUTH_PW'];

        $isBasicAuth = str_starts_with(
            strtolower($server['HTTP_AUTHORIZATION']),
            'basic',
        );

        if ($isBasicAuth) {
            $auth = base64_decode(
                substr($server['HTTP_AUTHORIZATION'], 6),
                true
            );

            if ($auth) {
                [$user, $pass] = explode(':', $auth, 2) + [1 => null];
            }
        }

        if ($user !== null) {
            $user = rawurlencode($user);
        }

        if ($pass !== null) {
            $pass = rawurlencode($pass);
        }

        return ['user' => $user, 'pass' => $pass];
    }

    /**
     * @return non-empty-array{host:?string, port:?int}
     */
    public function urlArrayHost() : array
    {
        $server = $this->_server + [
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
            return [
                'host' => null,
                'port' => null,
            ];
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
     * @return non-empty-array{path:?string, query:?string}
     */
    public function urlArrayData() : array
    {
        $server = $this->_server + [
            'IIS_WasUrlRewritten' => null,
            'PHP_SELF' => null,
            'QUERY_STRING' => null,
            'UNENCODED_URL' => null,
        ];

        if (
            $server['IIS_WasUrlRewritten'] === '1'
            && $server['UNENCODED_URL'] !== null
        ) {
            [$path, $query] = explode('?', $server['UNENCODED_URL'], 2) + [1 => null];
            return ['path' => $path, 'query' => $query];
        }

        if (isset($server['REQUEST_URI'])) {
            [$path, $query] = explode('?', $server['REQUEST_URI'], 2) + [1 => null];
            $query = ($server['QUERY_STRING'] !== null) ? $server['QUERY_STRING'] : $query;
            return ['path' => $path, 'query' => $query];
        }

        return ['path' => $server['PHP_SELF'], 'query' => $server['QUERY_STRING']];
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
        ?Url $url = null,
        mixed $body = null,
    ) : Request;

    /**
     * @inheritdoc
     */
    abstract public function newUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
        mixed $body = null,
    ) : Upload;

    abstract public function newUrl(
        ?string $scheme = null,
        ?string $host = null,
        ?int $port = null,
        ?string $user = null,
        ?string $pass = null,
        ?string $path = null,
        ?string $query = null,
        ?string $fragment = null,
    ) : Url;

    /**
     * @inheritdoc
     */
    abstract public function newBody(mixed $body) : Body;
}
