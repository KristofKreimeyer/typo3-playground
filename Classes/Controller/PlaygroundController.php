<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class PlaygroundController extends ActionController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        // ModuleTemplate keeps the app inside TYPO3's backend document and security context.
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('extensionName', 'ComponentPlayground');

        return $moduleTemplate->renderResponse('Playground/Index');
    }
}
