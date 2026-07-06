<?php

declare(strict_types=1);

use Vendor\ComponentPlayground\Controller\PreviewApiController;

return [
    'component_playground_components' => [
        'path' => '/component-playground/components',
        'target' => PreviewApiController::class . '::componentsAction',
        // The registry is available only to users who can open this backend module.
        'inheritAccessFromModule' => 'web_componentplayground',
    ],
    'component_playground_preview_render' => [
        'path' => '/component-playground/preview/render',
        'target' => PreviewApiController::class . '::renderAction',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'web_componentplayground',
    ],
];
