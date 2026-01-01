<?php declare(strict_types=1);

namespace CustomBrand;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class CustomBrand extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        // Plugin installation logic (optional)
    }

    public function activate(ActivateContext $activateContext): void
    {
        // No need to register plugin manually in Shopware 6
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        // Cleanup on deactivation (if needed)
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        // Cleanup when the plugin is uninstalled
    }
}