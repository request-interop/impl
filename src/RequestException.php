<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\RequestThrowable;
use RuntimeException;

class RequestException extends RuntimeException implements RequestThrowable
{
}
