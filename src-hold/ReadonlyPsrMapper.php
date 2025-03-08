<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\PsrMapper;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\RequestUpload;
use RequestInterop\Interface\Url;

class ReadonlyPsrMapper extends PsrMapper
{
    /**
     * @inheritdoc
     * @param ?ReadonlyRequestUrl $url
     * @return ReadonlyRequest
     */
    public function newRequest(
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
    ) : Request
    {
        return new ReadonlyRequest(
            cookies: $cookies ?? [],
            files: $files ?? [],
            headers: $headers ?? [],
            input: $input ?? [],
            method: $method ?? '',
            query: $query ?? [],
            server: $server ?? [],
            uploads: $uploads ?? [],
            url: $url ?? $this->newRequestUrl(),
        );
    }

    /**
     * @inheritdoc
     * @return ReadonlyRequestUpload
     */
    public function newRequestUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
        mixed $body = null,
    ) : RequestUpload
    {
        return new ReadonlyRequestUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
        );
    }

    /**
     * @return ReadonlyRequestUrl
     */
    public function newRequestUrl(
        ?string $scheme = null,
        ?string $host = null,
        ?int $port = null,
        ?string $user = null,
        ?string $pass = null,
        ?string $path = null,
        ?string $query = null,
        ?string $fragment = null,
    ) : Url
    {
        return new ReadonlyRequestUrl(
            scheme: $scheme,
            host: $host,
            port: $port,
            user: $user,
            pass: $pass,
            path: $path,
            query: $query,
            fragment: $fragment,
        );
    }
}
