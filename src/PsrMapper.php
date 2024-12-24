<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use BadMethodCallException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RequestInterop\Interface\Body;
use RequestInterop\Interface\Factory;
use RequestInterop\Interface\Request;
use RequestInterop\Interface\Upload;
use RequestInterop\Interface\Url;
use UnexpectedValueException;

/**
 * @phpstan-import-type BodyResource from Body
 * @phpstan-import-type CookiesArray from Request
 * @phpstan-import-type HeadersArray from Request
 * @phpstan-import-type InputArray from Request
 * @phpstan-import-type QueryArray from Request
 * @phpstan-import-type ServerArray from Request
 * @phpstan-import-type UploadsArray from Request
 * @phpstan-import-type UrlArray from Url
 */
abstract class PsrMapper implements Factory
{
    /**
     * @param ServerRequestFactoryInterface&StreamFactoryInterface&UploadedFileFactoryInterface&UriFactoryInterface $psrFactory
     */
    public function __construct(
        protected ServerRequestFactoryInterface&StreamFactoryInterface&UploadedFileFactoryInterface&UriFactoryInterface $psrFactory,
    ) {
    }

    public function toPsr(Request $request) : ServerRequestInterface
    {
        $psr = $this->psrFactory
            ->createServerRequest($request->method, (string) $request->url, $request->server)
            ->withCookieParams($request->cookies)
            ->withQueryParams($request->query)
            ->withParsedBody($request->input)
            ->withUploadedFiles($this->toPsrUploadedFiles($request->uploads));

        foreach ($request->headers as $field => $value) {
            $psr = $psr->withHeader($field, $value);
        }

        if ($request instanceof Body && $request->body !== null) {
            $psr = $psr->withBody($this->psrFactory->createStreamFromResource($request->body));
        }

        return $psr;
    }

    /**
     * @param mixed[] $uploads
     * @return mixed[]
     */
    public function toPsrUploadedFiles(array $uploads) : array
    {
        $psrUploadedFiles = [];

        foreach ($uploads as $key => $upload) {
            if (is_array($upload)) {
                /** @var UploadsArray $upload */
                $psrUploadedFiles[$key] = $this->toPsrUploadedFiles($upload);
                continue;
            }

            /** @var Upload $upload */
            $stream = ($upload instanceof Body && $upload->body !== null)
                ? $this->psrFactory->createStreamFromResource($upload->body)
                : $this->psrFactory->createStreamFromFile($upload->tmpName);

            $psrUploadedFiles[$key] = $this->psrFactory->createUploadedFile(
                stream: $stream,
                size: $upload->size,
                error: $upload->error,
                clientFilename: $upload->name,
                clientMediaType: $upload->type,
            );
        }

        return $psrUploadedFiles;
    }

    public function fromPsr(ServerRequestInterface $psr) : Request
    {
        /** @var CookiesArray $cookies */
        $cookies = $psr->getCookieParams();

        $psrParsedBody = $psr->getParsedBody();
        $input = $psrParsedBody === null ? [] : $this->fromPsrParsedBody($psrParsedBody);

        return $this->newRequest(
            cookies: $cookies,
            files: [],
            headers: $this->fromPsrHeaders($psr->getHeaders()),
            input: $input,
            method: strtoupper($psr->getMethod()),
            query: $this->fromPsrQueryParams($psr->getQueryParams()),
            server: $this->fromPsrServerParams($psr->getServerParams()),
            uploads: $this->fromPsrUploadedFiles($psr->getUploadedFiles()),
            url: $this->fromPsrUri($psr->getUri()),
            body: $this->fromPsrStream($psr->getBody()),
        );
    }

    /**
     * @param object|mixed[] $psrParsedBody
     * @return InputArray
     */
    public function fromPsrParsedBody(object|array $psrParsedBody) : array
    {
        $input = [];

        foreach ((array) $psrParsedBody as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $input[$key] = $this->fromPsrParsedBody($val);
                continue;
            }

            if ($val === null || is_scalar($val)) {
                $input[$key] = $val;
                continue;
            }

            throw new UnexpectedValueException("cant xfer type");
        }

        return $input;
    }

    /**
     * @param mixed[] $psrQueryParams
     * @return QueryArray
     */
    public function fromPsrQueryParams(array $psrQueryParams) : array
    {
        $query = [];

        foreach ($psrQueryParams as $key => $val) {
            if (is_array($val)) {
                $query[$key] = $this->fromPsrQueryParams($val);
                continue;
            }

            if ($val === null || is_scalar($val)) {
                $query[$key] = (string) $val;
                continue;
            }

            throw new UnexpectedValueException("cant xfer type");
        }

        return $query;
    }

    /**
     * @param mixed[] $psrServerParams
     * @return ServerArray
     */
    public function fromPsrServerParams(array $psrServerParams) : array
    {
        $server = [];

        foreach ($psrServerParams as $key => $val) {
            if ($val === null || is_scalar($val)) {
                $server[$key] = (string) $val;
                continue;
            }

            throw new UnexpectedValueException("cant xfer type");
        }

        /** @var ServerArray */
        return $server;
    }

    /**
     * @return BodyResource
     */
    public function fromPsrStream(StreamInterface $psrBody) : mixed
    {
        $psrBody->rewind();

        /** @var BodyResource */
        $body = fopen('php://temp', 'wb+');
        fwrite($body, $psrBody->getContents());
        rewind($body);
        return $body;
    }

    /**
     * @param array<array-key, mixed[]> $psrHeaders
     * @return HeadersArray
     */
    public function fromPsrHeaders(array $psrHeaders) : array
    {
        $headers = [];

        foreach ($psrHeaders as $field => $values) {
            $headers[strtolower($field)] = implode(', ', $values);
        }

        return $headers;
    }

    /**
     * @param mixed[] $psrUploadedFiles
     * @return UploadsArray
     */
    public function fromPsrUploadedFiles(array $psrUploadedFiles) : array
    {
        $uploads = [];

        foreach ($psrUploadedFiles as $key => $psrUploadedFile) {
            if (is_array($psrUploadedFile)) {
                $uploads[$key] = $this->fromPsrUploadedFiles($psrUploadedFile);
                continue;
            }

            /** @var UploadedFileInterface $psrUploadedFile */
            $tmpName = $psrUploadedFile->getStream()->getMetadata('uri');
            assert(is_string($tmpName));

            $uploads[$key] = $this->newUpload(
                tmpName: $tmpName,
                error: $psrUploadedFile->getError(),
                fullPath: null,
                name: $psrUploadedFile->getClientFilename(),
                size: $psrUploadedFile->getSize(),
                type: $psrUploadedFile->getClientMediaType(),
                body: $this->fromPsrStream($psrUploadedFile->getStream()),
            );
        }

        return $uploads;
    }

    public function fromPsrUri(UriInterface $psrUri) : Url
    {
        $user = null;
        $pass = null;
        $userinfo = $psrUri->getUserinfo();

        if ($userinfo) {
            list($user, $pass) = explode(':', $userinfo, 2) + [1 => null];
        }

        $fragment = $psrUri->getFragment();

        return $this->newUrl(
            scheme: $psrUri->getScheme(),
            host: $psrUri->getHost(),
            port: $psrUri->getPort(),
            user: $user,
            pass: $pass,
            path: $psrUri->getPath(),
            query: $psrUri->getQuery(),
            fragment: $fragment === '' ? null : $fragment,
        );
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
    public function newBody(mixed $body) : Body
    {
        throw new BadMethodCallException(
            'No direct mapping to or from RequestInterop\\Body.'
        );
    }
}
