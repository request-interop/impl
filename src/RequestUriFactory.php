<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestTypeAliases;

/**
 * @phpstan-import-type request_server_array from RequestTypeAliases
 */
class RequestUriFactory
{
    /**
     * @param request_server_array $server
     */
    public function newRequestUri(array $server) : RequestUri
    {
        $args = $this->getScheme($server)
            + $this->getHostAndPort($server)
            + $this->getPathAndQuery($server);

        return new RequestUri(...$args);
    }

    /**
     * @param request_server_array $server
     * @return array{scheme:non-empty-string}
     */
    public function getScheme(array $server) : array
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
     * @param request_server_array $server
     * @return array{host:string, port:?int}
     */
    public function getHostAndPort(array $server) : array
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
     * @param request_server_array $server
     * @return array{path:string, query:?string}
     */
    public function getPathAndQuery(array $server) : array
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
            $query = $server['QUERY_STRING'] ?? $parts[1] ?? null;
            return ['path' => $path, 'query' => $query];
        }

        return [
            'path' => (string) $server['PHP_SELF'],
            'query' => $server['QUERY_STRING']
        ];
    }
}
