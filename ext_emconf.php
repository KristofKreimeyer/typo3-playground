<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Component Playground',
    'description' => 'Backend workspace for developing and previewing TYPO3 content components.',
    'category' => 'be',
    'author' => 'Extension Team',
    'author_email' => '',
    'state' => 'alpha',
    'clearCacheOnLoad' => true,
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.4.99',
            'typo3' => '12.4.37-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
