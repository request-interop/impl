<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

class FakeRequestBodyStream extends RequestBodyStream
{
    /** @param resource $resource */
    public function setResource(mixed $resource) : void
    {
        $this->resource = $resource;
    }
}
