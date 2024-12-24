<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Readonly;

use RequestInterop\Interface\Request;
use RequestInterop\Interface\Url;
use UnexpectedValueException;

/**
 * @phpstan-import-type CookiesArray from Request
 * @phpstan-import-type FilesArray from Request
 * @phpstan-import-type HeadersArray from Request
 * @phpstan-import-type InputArray from Request
 * @phpstan-import-type MethodString from Request
 * @phpstan-import-type QueryArray from Request
 * @phpstan-import-type ServerArray from Request
 * @phpstan-import-type UploadsArray from Request
 */
readonly class ReadonlyRequest implements Request
{
    /**
     * @param CookiesArray $cookies
     * @param FilesArray $files
     * @param HeadersArray $headers
     * @param InputArray $input
     * @param MethodString $method
     * @param QueryArray $query
     * @param ServerArray $server
     * @param UploadsArray $uploads
     */
    public function __construct(
        public array $cookies = [],
        public array $files = [],
        public array $headers = [],
        public array $input = [],
        public string $method = '',
        public array $query = [],
        public array $server = [],
        public array $uploads = [],
        public ReadonlyUrl $url = new ReadonlyUrl(),
    ) {
    }
}
