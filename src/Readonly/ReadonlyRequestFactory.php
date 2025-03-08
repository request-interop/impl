<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use InvalidArgumentException;
use RequestInterop\Impl\RequestFactoryImpl;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\RequestBody;
use RequestInterop\Interface\RequestUpload;
use RequestInterop\Interface\RequestUrl;

class ReadonlyRequestFactory extends RequestFactoryImpl
{
    /**
     * @inheritdoc
     */
    public function cookiesArray() : array
    {
        return $this->readonly(parent::cookiesArray());
    }

    /**
     * @inheritdoc
     */
    public function filesArray() : array
    {
        return $this->readonly(parent::filesArray());
    }

    /**
     * @inheritdoc
     */
    public function headersArray(?array $server = null) : array
    {
        return $this->readonly(parent::headersArray($server));
    }

    /**
     * @inheritdoc
     */
    public function inputArray(?array $headers = null, ?RequestBody $body = null) : array
    {
        return $this->readonly(parent::inputArray($headers, $body));
    }

    /**
     * @inheritdoc
     */
    public function queryArray() : array
    {
        return $this->readonly(parent::queryArray());
    }

    /**
     * @template T of array
     * @param T $orig
     * @return T
     */
    public function readonly(array $orig) : mixed
    {
        $copy = [];

        foreach ($orig as $key => $value) {
            if (is_null($value) || is_scalar($value)) {
                $copy[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $copy[$key] = $this->readonly($value);
                continue;
            }

            throw new InvalidArgumentException(
                "Readonly values must be null, scalar, or array."
            );
        }

        /** @var T */
        return $copy;
    }

    /**
     * @inheritdoc
     */
    public function serverArray() : array
    {
        return $this->readonly(parent::serverArray());
    }

    /**
     * @inheritdoc
     * @param ?ReadonlyRequestUrl $url
     * @param ?ReadonlyRequestBody $body
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
        ?RequestUrl $url = null,
        ?RequestBody $body = null,
    ) : Request
    {
        $body ??= $this->newRequestBody('php://input');
        $cookies ??= $this->cookiesArray();
        $files ??= $this->filesArray();
        $query ??= $this->queryArray();
        $server ??= $this->serverArray();
        $url ??= $this->newRequestUrl($server);
        $headers ??= $this->headersArray($server);
        $method ??= $this->methodString($server, $headers);
        $input ??= $this->inputArray($headers, $body);
        $uploads ??= $this->uploadsArray($files);

        return new ReadonlyRequest(
            cookies: $cookies,
            files: $files,
            headers: $headers,
            input: $input,
            method: $method,
            query: $query,
            server: $server,
            uploads: $uploads,
            url: $url,
            body: $body,
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
        ?RequestBody $body = null,
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
     * @inheritdoc
     * @return ReadonlyRequestUrl
     */
    public function newRequestUrl(?array $server = null) : RequestUrl
    {
        $server ??= $this->serverArray();
        $args = $this->urlProperties($server);
        return new ReadonlyRequestUrl(...$args);
    }

    /**
     * @inheritdoc
     * @return ReadonlyRequestBody
     */
    public function newRequestBody(mixed $resource = null) : RequestBody
    {
        return new ReadonlyRequestBody($resource ?? $this->phpInput);
    }
}
