<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\RequestBody;
use StreamInterop\Impl\ReadableFileStream;

class MutableRequestBody extends ReadableFileStream implements RequestBody
{
}
