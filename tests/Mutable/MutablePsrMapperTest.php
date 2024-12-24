<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Impl\PsrMapperTestCase;
use RequestInterop\Impl\PsrMapper;

class MutablePsrMapperTest extends PsrMapperTestCase
{
    protected function newPsrMapper() : PsrMapper
    {
        return new MutablePsrMapper($this->psrFactory);
    }
}
