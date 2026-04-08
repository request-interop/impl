<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\RequestBodyStream;
use RequestInterop\Impl\RequestFactoryTestCase;
use RequestInterop\Interface\RequestStruct;

#[\PHPUnit\Framework\Attributes\BackupGlobals(true)]
class ReadonlyRequestFactoryTest extends RequestFactoryTestCase
{
    protected function newRequest(
        ?RequestBodyStream $bodyStream = null,
    ) : RequestStruct
    {
        $factory = $bodyStream
            ? new ReadonlyRequestFactory(bodyStream: $bodyStream)
            : new ReadonlyRequestFactory();

        return $factory->newRequest();
    }
}
