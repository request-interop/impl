<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestFactory;
use RequestInterop\Interface\Body;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\Upload;
use RequestInterop\Interface\Url;

class MutableFactory extends RequestFactory
{
    /**
     * @inheritdoc
     * @param ?MutableUrl $url
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
            cookies: $cookies ?? $this->cookiesArray(),
            files: $files ?? $this->filesArray(),
            headers: $headers ?? $this->headersArray(),
            input: $input ?? $this->inputArray(),
            method: $method ?? $this->methodString(),
            query: $query ?? $this->queryArray(),
            server: $server ?? $this->serverArray(),
            uploads: $uploads ?? $this->uploadsArray(),
            url: $url ?? $this->newUrl(),
            body: $body ?? $this->bodyResource(),
        );
    }

    /**
     * @inheritdoc
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
        $default = $this->urlArray();

        return new MutableUrl(
            scheme: $scheme ?? $default['scheme'],
            host: $host ?? $default['host'],
            port: $port ?? $default['port'],
            user: $user ?? $default['user'],
            pass: $pass ?? $default['pass'],
            path: $path ?? $default['path'],
            query: $query ?? $default['query'],
            fragment: $fragment ?? $default['fragment'],
        );
    }

    /**
     * @inheritdoc
     * @return MutableBody
     */
    public function newBody(mixed $body) : Body
    {
        return new MutableBody($body);
    }
}
