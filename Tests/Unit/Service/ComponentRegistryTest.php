<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Vendor\ComponentPlayground\Service\ComponentRegistry;

final class ComponentRegistryTest extends TestCase
{
    private ComponentRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ComponentRegistry();
    }

    public function testRegistryStructureAndUniqueness(): void
    {
        $components = $this->registry->getComponents();
        self::assertNotEmpty($components);
        $keys = [];

        foreach ($components as $component) {
            foreach (['key', 'label', 'description', 'template', 'schema', 'defaultProps', 'variants'] as $requiredKey) {
                self::assertArrayHasKey($requiredKey, $component);
            }
            self::assertIsArray($component['defaultProps']);
            self::assertNotEmpty($component['variants']);
            $keys[] = $component['key'];

            foreach ($component['variants'] as $variant) {
                self::assertArrayHasKey('key', $variant);
                self::assertArrayHasKey('label', $variant);
                self::assertArrayHasKey('props', $variant);
                self::assertIsArray($variant['props']);
            }
        }

        self::assertSame($keys, array_values(array_unique($keys)));
    }

    public function testFindByKeyReturnsExpectedComponentAndUnknownReturnsNull(): void
    {
        self::assertSame('Hero', $this->registry->findByKey('hero')['label'] ?? null);
        self::assertNull($this->registry->findByKey('doesNotExist'));
    }

    public function testExpectedEdgeCaseVariantsExist(): void
    {
        $expectedVariants = [
            'hero' => ['default', 'longText', 'withoutImage', 'withoutButton', 'shortContent'],
            'teaserGrid' => ['default', 'oneItem', 'twoItems', 'longTexts', 'missingImages', 'emptyItems'],
            'quote' => ['default', 'longQuote', 'withoutAuthor', 'shortQuote', 'longAuthorRole'],
            'cta' => ['default', 'longText', 'withoutButton', 'shortContent', 'longButtonLabel'],
        ];

        foreach ($expectedVariants as $componentKey => $variantKeys) {
            $component = $this->registry->findByKey($componentKey);
            self::assertNotNull($component);
            self::assertSame($variantKeys, array_column($component['variants'], 'key'));
        }
    }
}
