<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use StreamInterop\Interface\StreamThrowable;

class RequestBodyStreamException extends RequestException implements StreamThrowable
{
}
