<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\RequestFactory;
use RequestInterop\Interface\RequestStruct;
use UriInterop\Impl\ImmutableUri;
use UriInterop\Interface\UriStruct;

/**
 * @property ImmutableUri $uri
 */
class ReadonlyRequestFactory extends RequestFactory
{
    /**
     * @inheritdoc
     * @return ReadonlyRequest
     */
    public function newRequest() : RequestStruct
    {
        return new ReadonlyRequest(
            body: $this->body,
            bodyStream: $this->bodyStream,
            cookies: $this->globals->_COOKIE,
            headers: $this->headers,
            method: $this->method,
            query: $this->globals->_GET,
            server: $this->globals->_SERVER,
            uploads: $this->uploads,
            uri: $this->uri,
        );
    }

    /**
     * @return ImmutableUri
     */
    protected function getUri() : UriStruct
    {
        $args = $this->getUriScheme()
            + $this->getUriHostAndPort()
            + $this->getUriPathAndQuery();

        return new ImmutableUri(...$args);
    }
}
