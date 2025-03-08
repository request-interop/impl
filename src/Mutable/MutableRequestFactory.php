<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestFactoryImpl;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\RequestBody;
use RequestInterop\Interface\RequestUpload;
use RequestInterop\Interface\RequestUrl;

class MutableRequestFactory extends RequestFactoryImpl
{
    /**
     * @inheritdoc
     * @param ?MutableRequestUrl $url
     * @param ?MutableRequestBody $body
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
        ?RequestUrl $url = null,
        ?RequestBody $body = null,
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
            url: $url ?? $this->newRequestUrl(),
            body: $body ?? $this->newRequestBody('php://input'),
        );
    }

    /**
     * @param ?MutableRequestBody $body
     * @inheritdoc
     */
    public function newRequestUpload(
        string $tmpName,
        int $error,
        ?string $name = null,
        ?string $fullPath = null,
        ?string $type = null,
        ?int $size = null,
        ?RequestBody $body = null,
    ) : RequestUpload
    {
        $body ??= $this->newRequestBody($tmpName);

        return new MutableRequestUpload(
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
     * @inheritdoc
     * @return MutableRequestUrl
     */
    public function newRequestUrl(?array $server = null) : RequestUrl
    {
        $server ??= $this->serverArray();
        $args = $this->urlProperties($server);
        return new MutableRequestUrl(...$args);
    }

    /**
     * @inheritdoc
     * @return MutableRequestBody
     */
    public function newRequestBody(mixed $spec) : RequestBody
    {
        if (is_string($spec)) {
            $spec = fopen($spec, 'rb');
        }

        assert(is_resource($spec));
        return new MutableRequestBody($spec);
    }
}
