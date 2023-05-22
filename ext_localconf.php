<?php
defined('TYPO3') or die('Access denied.');

use H4ck3r31\CspDetails\PageRendererHook;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['csp-details'] = PageRendererHook::class . '->importModule';
