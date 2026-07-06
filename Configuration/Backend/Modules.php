<?php

declare(strict_types=1);

use Vendor\ComponentPlayground\Controller\PlaygroundController;

return [
    'web_componentplayground' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/component-playground',
        'iconIdentifier' => 'module-component-playground',
        'labels' => 'LLL:EXT:component_playground/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'ComponentPlayground',
        'controllerActions' => [
            PlaygroundController::class => ['index'],
        ],
    ],
];
