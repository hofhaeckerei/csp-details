<?php
use H4ck3r31\CspDetails\ContentSecurityPolicyDetailsReporter;

return [
    'backend' => [
        'h4ck3r31/csp-details/csp-report-details' => [
            'target' => ContentSecurityPolicyDetailsReporter::class,
            'before' => [
                'typo3/cms-backend/csp-report',
            ],
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-backend/https-redirector',
            ],
        ],
    ],
    'frontend' => [
        'h4ck3r31/csp-details/csp-report-details' => [
            'target' => ContentSecurityPolicyDetailsReporter::class,
            'before' => [
                'typo3/cms-frontend/csp-report',
            ],
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
