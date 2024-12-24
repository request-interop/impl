<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\PsrMapper;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\Upload;
use RequestInterop\Interface\Url;

class MutablePsrMapper extends PsrMapper
{
    /**
     * @inheritdoc
     * @param MutableUrl $url
     * @return MutableRequest
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
        return new MutableRequest(
            cookies: $cookies ?? [],
            files: $files ?? [],
            headers: $headers ?? [],
            input: $input ?? [],
            method: $method ?? '',
            query: $query ?? [],
            server: $server ?? [],
            uploads: $uploads ?? [],
            url: $url ?? $this->newUrl(),
            body: $body,
        );
    }

    /**
     * @inheritdoc
     * @return MutableUpload
     */
    public function newUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
        mixed $body = null,
    ) : Upload
    {
        return new MutableUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
            body: $body,
        );
    }

    /**
     * @return MutableUrl
     */
    public function newUrl(
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
        return new MutableUrl(
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
