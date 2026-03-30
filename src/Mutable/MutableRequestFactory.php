<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\RequestFactory;
use RequestInterop\Interface\RequestStruct;
use UriInterop\Impl\MutableUri;
use UriInterop\Interface\UriStruct;

/**
 * @property MutableUri $uri
 */
class MutableRequestFactory extends RequestFactory
{
    /**
     * @inheritdoc
     * @return MutableRequest
     */
    public function newRequest() : RequestStruct
    {
        return new MutableRequest(
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
     * @return MutableUri
     */
    protected function getUri() : UriStruct
    {
        $args = $this->getUriScheme()
            + $this->getUriHostAndPort()
            + $this->getUriPathAndQuery();

        return new MutableUri(...$args);
    }
}
