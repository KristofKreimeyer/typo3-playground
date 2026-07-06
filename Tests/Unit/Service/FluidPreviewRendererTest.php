<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vendor\ComponentPlayground\Service\FluidPreviewRenderer;
use Vendor\ComponentPlayground\Service\PreviewTemplateNameResolver;

final class FluidPreviewRendererTest extends TestCase
{
    public function testRegisteredTemplateNameIsAccepted(): void
    {
        $resolver = new PreviewTemplateNameResolver();
        self::assertSame('Hero', $resolver->resolve(['template' => 'Hero']));
    }

    public function testMissingTemplateNameFailsBeforeFluidBootstrap(): void
    {
        $renderer = new FluidPreviewRenderer(new PreviewTemplateNameResolver());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid registered preview template.');
        $renderer->render([], ['headline' => 'Example']);
    }

    public function testArbitraryTemplatePathIsRejected(): void
    {
        $resolver = new PreviewTemplateNameResolver();
        $this->expectException(RuntimeException::class);
        $resolver->resolve(['template' => '../../PrivateData']);
    }

    public function testRegisteredTemplateFilesExistAndHeroContainsExpectedBindings(): void
    {
        $templateRoot = dirname(__DIR__, 3) . '/Resources/Private/Templates/Preview';
        foreach (['Hero', 'TeaserGrid', 'Quote', 'Cta'] as $template) {
            self::assertFileExists($templateRoot . '/' . $template . '.html');
        }
        $heroTemplate = file_get_contents($templateRoot . '/Hero.html');
        self::assertIsString($heroTemplate);
        self::assertStringContainsString('{props.headline}', $heroTemplate);
        self::assertStringContainsString('{props.text}', $heroTemplate);
    }
}
