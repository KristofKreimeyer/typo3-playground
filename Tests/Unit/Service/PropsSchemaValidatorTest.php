<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Vendor\ComponentPlayground\Service\ComponentRegistry;
use Vendor\ComponentPlayground\Service\PropsSchemaValidator;

final class PropsSchemaValidatorTest extends TestCase
{
    private ComponentRegistry $registry;
    private PropsSchemaValidator $validator;

    protected function setUp(): void
    {
        $this->registry = new ComponentRegistry();
        $this->validator = new PropsSchemaValidator();
    }

    public function testValidHeroPropsAndOmittedOptionalFieldsPass(): void
    {
        self::assertTrue($this->validate('hero', ['headline' => 'Example'])['valid']);
        self::assertTrue($this->validate('hero', [
            'headline' => 'Example', 'text' => 'Copy', 'image' => '/image.jpg',
            'imageAlt' => 'Alt', 'buttonLabel' => 'Read more', 'buttonUrl' => '/more',
        ])['valid']);
    }

    public function testMissingHeroHeadlineFailsWithStablePath(): void
    {
        $result = $this->validate('hero', []);
        self::assertFalse($result['valid']);
        self::assertSame(['path' => 'headline', 'code' => 'required', 'message' => 'headline is required.'], $result['errors'][0]);
    }

    public function testHeroHeadlineMustBeAString(): void
    {
        $result = $this->validate('hero', ['headline' => 42]);
        self::assertFalse($result['valid']);
        self::assertSame('invalid_type', $result['errors'][0]['code']);
        self::assertSame('headline', $result['errors'][0]['path']);
    }

    public function testValidTeaserGridPasses(): void
    {
        $result = $this->validate('teaserGrid', [
            'headline' => 'Projects',
            'items' => [['title' => 'One'], ['title' => 'Two', 'text' => 'Description']],
        ]);
        self::assertTrue($result['valid']);
    }

    public function testTeaserGridRequiresItems(): void
    {
        $result = $this->validate('teaserGrid', []);
        self::assertSame('items', $result['errors'][0]['path']);
        self::assertSame('required', $result['errors'][0]['code']);
    }

    public function testTeaserGridItemsMustBeAnArray(): void
    {
        $result = $this->validate('teaserGrid', ['items' => 'not-an-array']);
        self::assertSame('items', $result['errors'][0]['path']);
        self::assertSame('invalid_type', $result['errors'][0]['code']);
    }

    public function testTeaserItemRequiresStringTitle(): void
    {
        $missing = $this->validate('teaserGrid', ['items' => [['text' => 'No title']]]);
        self::assertSame('items[0].title', $missing['errors'][0]['path']);
        self::assertSame('required', $missing['errors'][0]['code']);

        $invalid = $this->validate('teaserGrid', ['items' => [['title' => false]]]);
        self::assertSame('items[0].title', $invalid['errors'][0]['path']);
        self::assertSame('invalid_type', $invalid['errors'][0]['code']);
    }

    public function testQuoteRequiresQuoteAndAcceptsValidProps(): void
    {
        self::assertTrue($this->validate('quote', ['quote' => 'A useful quote.'])['valid']);
        $invalid = $this->validate('quote', ['author' => 'Alex']);
        self::assertSame('quote', $invalid['errors'][0]['path']);
        self::assertSame('required', $invalid['errors'][0]['code']);
    }

    public function testCtaRequiresHeadlineAndAcceptsValidProps(): void
    {
        self::assertTrue($this->validate('cta', ['headline' => 'Get in touch'])['valid']);
        $invalid = $this->validate('cta', ['text' => 'Supporting copy']);
        self::assertSame('headline', $invalid['errors'][0]['path']);
        self::assertSame('required', $invalid['errors'][0]['code']);
    }

    public function testAllRegisteredDefaultsAndVariantsMatchTheirSchemas(): void
    {
        foreach ($this->registry->getComponents() as $component) {
            self::assertTrue(
                $this->validator->validate($component['defaultProps'], $component['schema'])['valid'],
                $component['key'] . ' default props should be schema-valid.',
            );
            foreach ($component['variants'] as $variant) {
                self::assertTrue(
                    $this->validator->validate($variant['props'], $component['schema'])['valid'],
                    $component['key'] . ':' . $variant['key'] . ' should be schema-valid.',
                );
            }
        }
    }

    /** @param array<string, mixed> $props */
    private function validate(string $componentKey, array $props): array
    {
        $component = $this->registry->findByKey($componentKey);
        self::assertNotNull($component);
        self::assertIsArray($component['schema']);
        return $this->validator->validate($props, $component['schema']);
    }
}
