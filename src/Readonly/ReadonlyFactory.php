<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use BadMethodCallException;
use RequestInterop\Impl\RequestFactory;
use RequestInterop\Interface\Body;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\Upload;
use RequestInterop\Interface\Url;
use UnexpectedValueException;

class ReadonlyFactory extends RequestFactory
{
    /**
     * @inheritdoc
     */
    public function cookiesArray() : array
    {
        return $this->readonly($this->_cookie);
    }

    /**
     * @inheritdoc
     */
    public function filesArray() : array
    {
        return $this->readonly($this->_files);
    }

    /**
     * @inheritdoc
     */
    public function headersArray() : array
    {
        return $this->readonly(parent::headersArray());
    }

    /**
     * @inheritdoc
     */
    public function inputArray() : array
    {
        return $this->readonly(parent::inputArray());
    }

    /**
     * @inheritdoc
     */
    public function queryArray() : array
    {
        return $this->readonly($this->_get);
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

            throw new UnexpectedValueException(
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
        return $this->readonly($this->_server);
    }

    /**
     * @inheritdoc
     * @param ?ReadonlyUrl $url
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
            cookies: $cookies ?? $this->cookiesArray(),
            files: $files ?? $this->filesArray(),
            headers: $headers ?? $this->headersArray(),
            input: $input ?? $this->inputArray(),
            method: $method ?? $this->methodString(),
            query: $query ?? $this->queryArray(),
            server: $server ?? $this->serverArray(),
            uploads: $uploads ?? $this->uploadsArray(),
            url: $url ?? $this->newUrl()
        );
    }

    /**
     * @inheritdoc
     * @return ReadonlyUpload
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
        return new ReadonlyUpload(
            tmpName: $tmpName,
            error: $error,
            name: $name,
            fullPath: $fullPath,
            type: $type,
            size: $size,
        );
    }

    /**
     * @return ReadonlyUrl
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

        return new ReadonlyUrl(
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
     */
    public function newBody(mixed $body) : Body
    {
        throw new BadMethodCallException(
            'Cannot provide readonly implementation of RequestInterop\\Body.'
        );
    }
}
