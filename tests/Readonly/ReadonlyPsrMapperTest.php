<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Impl\PsrMapperTestCase;
use RequestInterop\Impl\PsrMapper;

class ReadonlyPsrMapperTest extends PsrMapperTestCase
{
    protected function newPsrMapper() : PsrMapper
    {
        return new ReadonlyPsrMapper($this->psrFactory);
    }
}
