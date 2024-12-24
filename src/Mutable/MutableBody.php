<?php
declare(strict_types=1);

namespace RequestInterop\Impl\Mutable;

use RequestInterop\Interface\Body;

/**
 * @phpstan-import-type BodyResource from Body
 */
class MutableBody implements Body
{
    /**
     * @inheritdoc
     */
    public mixed $body;

    /**
     * @param BodyResource $body
     */
    public function __construct(mixed $body)
    {
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
