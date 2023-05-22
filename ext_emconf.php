<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Content-Security-Policy Violation Details',
    'description' => 'Provides details for CSP violations',
    'category' => 'misc',
    'state' => 'alpha',
    'clearCacheOnLoad' => true,
    'author' => 'Oliver Hader',
    'author_email' => 'oliver.hader@typo3.org',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
