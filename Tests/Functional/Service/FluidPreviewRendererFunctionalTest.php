<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Functional\Service;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Vendor\ComponentPlayground\Service\ComponentRegistry;
use Vendor\ComponentPlayground\Service\FluidPreviewRenderer;

final class FluidPreviewRendererFunctionalTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'vendor/typo3-component-playground',
    ];

    private ComponentRegistry $registry;
    private FluidPreviewRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->get(ComponentRegistry::class);
        $this->renderer = $this->get(FluidPreviewRenderer::class);
    }

    public function testHeroRendersCompleteProps(): void
    {
        $html = $this->render('hero', [
            'eyebrow' => 'Featured capability',
            'headline' => 'A useful hero headline',
            'text' => 'Supporting copy for the component.',
            'image' => '/assets/hero.jpg',
            'imageAlt' => 'A project workshop',
            'buttonLabel' => 'View our work',
            'buttonUrl' => '/work',
        ]);

        self::assertStringContainsString('A useful hero headline', $html);
        self::assertStringContainsString('View our work', $html);
        self::assertStringContainsString('class="tcp-button"', $html);
        $this->assertFluidWasRendered($html);
    }

    public function testHeroRendersWithoutOptionalPropsOrButton(): void
    {
        $html = $this->render('hero', ['headline' => 'Headline only']);

        self::assertStringContainsString('Headline only', $html);
        self::assertStringNotContainsString('class="tcp-button"', $html);
        self::assertStringNotContainsString('<img', $html);
        $this->assertFluidWasRendered($html);
    }

    public function testTeaserGridRendersMultipleItems(): void
    {
        $html = $this->render('teaserGrid', [
            'headline' => 'Selected projects',
            'items' => [
                ['title' => 'Project Alpha', 'text' => 'First project.', 'url' => '/alpha'],
                ['title' => 'Project Beta', 'text' => 'Second project.', 'url' => '/beta'],
            ],
        ]);

        self::assertStringContainsString('Project Alpha', $html);
        self::assertStringContainsString('Project Beta', $html);
        self::assertSame(2, substr_count($html, 'class="tcp-teaser"'));
        $this->assertFluidWasRendered($html);
    }

    public function testTeaserGridRendersFallbackForEmptyItems(): void
    {
        $html = $this->render('teaserGrid', ['headline' => 'Selected projects', 'items' => []]);

        self::assertStringContainsString('No teaser items provided.', $html);
        self::assertStringNotContainsString('class="tcp-teaser"', $html);
        $this->assertFluidWasRendered($html);
    }

    public function testQuoteRendersWithoutAuthor(): void
    {
        $html = $this->render('quote', ['quote' => 'A strong result.', 'author' => '', 'role' => '']);

        self::assertStringContainsString('A strong result.', $html);
        self::assertStringNotContainsString('<figcaption>', $html);
        $this->assertFluidWasRendered($html);
    }

    public function testCtaRendersWithoutButtonData(): void
    {
        $html = $this->render('cta', ['headline' => 'Start a conversation', 'text' => 'Tell us what you need.']);

        self::assertStringContainsString('Start a conversation', $html);
        self::assertStringContainsString('Tell us what you need.', $html);
        self::assertStringNotContainsString('<a', $html);
        self::assertStringNotContainsString('class="tcp-button"', $html);
        $this->assertFluidWasRendered($html);
    }

    /** @param array<string, mixed> $props */
    private function render(string $componentKey, array $props): string
    {
        $component = $this->registry->findByKey($componentKey);
        self::assertNotNull($component);
        return $this->renderer->render($component, $props);
    }

    private function assertFluidWasRendered(string $html): void
    {
        self::assertNotSame('', trim($html));
        self::assertStringNotContainsString('<f:', $html);
        self::assertStringNotContainsString('</f:', $html);
    }
}
