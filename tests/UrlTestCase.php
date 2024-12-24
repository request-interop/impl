<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use RequestInterop\Interface\Url;

/**
 * @phpstan-import-type UrlArray from RequestFactory
 */
abstract class UrlTestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function newUrl(
        ?string $scheme = null,
        ?string $host = null,
        ?int $port = null,
        ?string $user = null,
        ?string $pass = null,
        ?string $path = null,
        ?string $query = null,
        ?string $fragment = null,
    ) : Url;

    #[\PHPUnit\Framework\Attributes\DataProvider('provide')]
    public function test(string $expect) : void
    {
        /** @var UrlArray */
        $parsed = parse_url($expect);
        $actual = $this->newUrl(...$parsed);
        $this->assertSame($expect, (string) $actual);
    }

    /**
     * @return array<int, array{string}>
     */
    public static function provide() : array
    {
        return [
            ['http://user:pass@example.com:8000/foo?bar=baz#dib'],
            ['http://user:pass@example.com:8000/foo?bar=baz'],
            ['http://user:pass@example.com:8000/foo#dib'],
            ['http://user:pass@example.com:8000/?bar=baz'],
            ['http://user:pass@example.com:8000/foo'],
            ['http://user:pass@example.com:8000/'],
            ['http://user:pass@example.com:8000'],
            ['http://user:pass@example.com'],
            ['http://user@example.com'],
            ['http://example.com'],
        ];
    }
}
