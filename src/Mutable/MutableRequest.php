<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\Body;
use RequestInterop\Interface\Request;

/**
 * @phpstan-import-type BodyResource from Body
 * @phpstan-import-type CookiesArray from Request
 * @phpstan-import-type FilesArray from Request
 * @phpstan-import-type HeadersArray from Request
 * @phpstan-import-type InputArray from Request
 * @phpstan-import-type MethodString from Request
 * @phpstan-import-type QueryArray from Request
 * @phpstan-import-type ServerArray from Request
 * @phpstan-import-type UploadsArray from Request
 */
class MutableRequest implements Request, Body
{
    /**
     * @inheritdoc
     */
    public mixed $body {
        get {
            if ($this->body === null) {
                $this->body = fopen('php://input', 'rb');
            }

            return $this->body;
        }
    }

    /**
     * @param CookiesArray $cookies
     * @param FilesArray $files
     * @param HeadersArray $headers
     * @param InputArray $input
     * @param MethodString $method
     * @param QueryArray $query
     * @param ServerArray $server
     * @param UploadsArray $uploads
     * @param ?BodyResource $body
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
        public MutableUrl $url = new MutableUrl(),
        mixed $body = null,
    ) {
        $this->body = $body;
    }

    public function __toString() : string
    {
        /** @var BodyResource */
        $body = $this->body;
        rewind($body);
        return (string) stream_get_contents($body);
    }
}
