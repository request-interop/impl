<?php
declare(strict_types=1);

namespace RequestInterop\Impl;

use InvalidArgumentException;
use RequestInterop\Interface\RequestUrl;
use UriInterop\Interface\UriTypeAliases;

/**
 * @phpstan-import-type parse_url_array from UriTypeAliases
 */
abstract class RequestUrlTestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function newRequestUrl(
        string $scheme,
        string $host,
        ?int $port = null,
        ?string $path = null,
        ?string $query = null,
    ) : RequestUrl;

    #[\PHPUnit\Framework\Attributes\DataProvider('provide')]
    public function test(string $expect) : void
    {
        /** @var parse_url_array $parsed */
        $parsed = parse_url($expect);
        unset($parsed['user']);
        unset($parsed['pass']);
        unset($parsed['fragment']);
        assert(isset($parsed['scheme']));
        assert(isset($parsed['host']));
        $actual = $this->newRequestUrl(...$parsed);
        $this->assertSame($expect, (string) $actual);
    }


    public function testInvalidScheme() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheme component blank or not present.');
        $url = $this->newRequestUrl(scheme: '    ', host: 'example.net');
    }

    public function testInvalidHost() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Host component blank or not present.');
        $url = $this->newRequestUrl(scheme: 'https', host: '    ');
    }

    /**
     * @return array<int, array{string}>
     */
    public static function provide() : array
    {
        return [
            ['http://example.com'],
            ['http://example.com/'],
            ['http://example.com:8000'],
            ['http://example.com:8000/'],
            ['http://example.com:8000/foo'],
            ['http://example.com:8000/foo/'],
            ['http://example.com:8000?bar=baz'],
            ['http://example.com:8000/?bar=baz'],
            ['http://example.com:8000/foo?bar=baz'],
            ['http://example.com:8000/foo/?bar=baz'],
        ];
    }
}
