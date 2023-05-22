<?php
namespace H4ck3r31\CspDetails;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
        $pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create(
                '@h4ck3r31/csp-details/violation-handler.js',
                'CspDetailsViolationHandler'
            )->instance()
        );
    }
}
