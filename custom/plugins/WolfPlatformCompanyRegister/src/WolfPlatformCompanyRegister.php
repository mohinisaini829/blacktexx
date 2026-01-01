<?php declare(strict_types=1);

namespace Wolf\WolfPlatformCompanyRegister;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class WolfPlatformCompanyRegister extends Plugin
{
    public function install(InstallContext $context): void
    {
        $this->addDefaultConfiguration();
    }

    public function activate(ActivateContext $context): void
    {
    }

    public function deactivate(DeactivateContext $context): void
    {
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }
    }

    private function addDefaultConfiguration(): void
    {
        $this->setValue('active', true);

    }

    /**
     * @param string $configName
     * @param null $default
     */
    public function setValue(string $configName, $default = null) : void
    {
        $systemConfigService = $this->container->get(SystemConfigService::class);
        $domain = $this->getName() . '.config.';

        if( $systemConfigService->get($domain . $configName) === null )
        {
            $systemConfigService->set($domain . $configName, $default);
        }
    }

}
