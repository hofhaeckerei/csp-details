<?php

use OliverHader\CspTest\Controller\Module;

return [
    'tools_csp_test' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-extensionmanager',
        'labels' => 'LLL:EXT:csp_test/Resources/Private/Language/Modules/csp-test.xlf',
        'routes' => [
            '_default' => [
                'target' => Module::class . '::mainAction',
            ],
        ],
    ],
];
