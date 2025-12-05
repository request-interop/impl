<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\RequestStruct;
use RequestInterop\Impl\RequestFactoryTestCase;
use RequestInterop\Impl\RequestBodyStream;

#[\PHPUnit\Framework\Attributes\BackupGlobals(true)]
class MutableRequestFactoryTest extends RequestFactoryTestCase
{
    protected function newRequest(?RequestBodyStream $bodyStream = null) : RequestStruct
    {
        $factory = $bodyStream
            ? new MutableRequestFactory(bodyStream: $bodyStream)
            : new MutableRequestFactory();

        return $factory->newRequest();
    }
}
