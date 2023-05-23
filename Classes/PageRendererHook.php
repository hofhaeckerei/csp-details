<?php
namespace H4ck3r31\CspDetails;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRendererHook
{
    use ExtensionConfigurationTrait;

    public function importModule(): void
    {
        // @todo figure out, why this cannot be injects
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        if (!$this->isEnabled($extensionConfiguration)) {
            return;
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsLibrary(
            name: '@h4ck3r31/csp-details/violation-handler.js',
            file: 'EXT:csp_details/Resources/Public/JavaScript/violation-handler.js',
            type: 'module',
            forceOnTop: true,
        );
    }
}
