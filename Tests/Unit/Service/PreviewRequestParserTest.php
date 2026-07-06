<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Vendor\ComponentPlayground\Exception\PreviewRequestException;
use Vendor\ComponentPlayground\Service\ComponentRegistry;
use Vendor\ComponentPlayground\Service\PreviewRequestParser;

final class PreviewRequestParserTest extends TestCase
{
    private PreviewRequestParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PreviewRequestParser(new ComponentRegistry());
    }

    public function testValidRequestReturnsRegisteredComponentAndNormalizedProps(): void
    {
        $result = $this->parser->parse('{"component":"hero","props":{"headline":"Example","items":[{"title":"One"}]}}');

        self::assertSame('hero', $result['component']['key']);
        self::assertSame('Example', $result['props']['headline']);
        self::assertSame([['title' => 'One']], $result['props']['items']);
    }

    public function testInvalidJsonIsRejected(): void
    {
        $this->assertRequestError(
            fn () => $this->parser->parse('{invalid'),
            'invalid_json',
            400,
        );
    }

    public function testMissingComponentIsRejected(): void
    {
        $this->assertRequestError(
            fn () => $this->parser->parse('{"props":{}}'),
            'invalid_request',
            400,
        );
    }

    public function testUnknownComponentIsRejected(): void
    {
        $this->assertRequestError(
            fn () => $this->parser->parse('{"component":"unknown","props":{}}'),
            'unknown_component',
            404,
        );
    }

    /** @param callable(): mixed $callback */
    private function assertRequestError(callable $callback, string $errorCode, int $statusCode): void
    {
        try {
            $callback();
            self::fail('Expected PreviewRequestException was not thrown.');
        } catch (PreviewRequestException $exception) {
            self::assertSame($errorCode, $exception->errorCode);
            self::assertSame($statusCode, $exception->statusCode);
        }
    }
}
