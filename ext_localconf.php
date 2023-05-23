<?php
defined('TYPO3') or die('Access denied.');

use H4ck3r31\CspDetails\PageRendererHook;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['csp-details'] = PageRendererHook::class . '->importModule';

// add default logger in case it was not defined yet individually
if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG']['H4ck3r31']['CspDetails'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['H4ck3r31']['CspDetails']['writerConfiguration'] = [
        LogLevel::DEBUG => [
            FileWriter::class => [
                'logFileInfix' => 'csp-details',
            ],
        ]
    ];
}
