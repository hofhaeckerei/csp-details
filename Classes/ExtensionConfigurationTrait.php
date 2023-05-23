<?php
namespace H4ck3r31\CspDetails;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

trait ExtensionConfigurationTrait
{
    public function isEnabled(ExtensionConfiguration $extensionConfiguration): bool
    {
        return $extensionConfiguration->get('csp_details', 'enabled');
    }
}
