<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Service;

use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class FluidPreviewRenderer
{
    private const TEMPLATE_ROOT = 'EXT:component_playground/Resources/Private/Templates/Preview';

    public function __construct(
        private readonly PreviewTemplateNameResolver $templateNameResolver,
    ) {
    }

    /**
     * @param array{template?: mixed} $component
     * @param array<string, mixed> $props
     */
    public function render(array $component, array $props): string
    {
        $template = $this->templateNameResolver->resolve($component);

        $templatePath = self::TEMPLATE_ROOT . '/' . $template . '.html';
        $absoluteTemplatePath = GeneralUtility::getFileAbsFileName($templatePath);
        if ($absoluteTemplatePath === '' || !is_file($absoluteTemplatePath)) {
            throw new RuntimeException('Registered preview template does not exist.');
        }

        if (interface_exists(ViewFactoryInterface::class) && class_exists(ViewFactoryData::class)) {
            $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
            $view = $viewFactory->create(new ViewFactoryData(
                templateRootPaths: [self::TEMPLATE_ROOT],
            ));
            $view->assign('props', $props);
            return $view->render($template);
        }

        // StandaloneView remains the TYPO3 v12 compatibility path.
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($absoluteTemplatePath);
        $view->assign('props', $props);

        return $view->render();
    }
}
