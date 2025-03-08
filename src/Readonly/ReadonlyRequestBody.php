<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\RequestBody;
use StreamInterop\Impl\ReadonlyFileStream;

class ReadonlyRequestBody extends ReadonlyFileStream implements RequestBody
{
}
