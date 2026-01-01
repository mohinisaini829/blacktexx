<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use zenit\PlatformNotificationBar\Util\Lifecycle\Installer;
use zenit\PlatformNotificationBar\Util\Lifecycle\Uninstaller;

class zenitPlatformNotificationBar extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $this->addDefaultConfiguration();
        $this->addInstallDefaultConfiguration();

        /** @var EntityRepository $mediaDefaultFolderRepository */
        $mediaDefaultFolderRepository = $this->container->get('media_default_folder.repository');
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $installer = new Installer(
            $mediaDefaultFolderRepository,
            $connection
        );
        $installer->install($installContext->getContext());

        parent::install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->addDefaultConfiguration();
        $this->addUpdateDefaultConfiguration();

        if (version_compare($updateContext->getCurrentPluginVersion(), '2.5.0', '<')) {
            /** @var EntityRepository $mediaDefaultFolderRepository */
            $mediaDefaultFolderRepository = $this->container->get('media_default_folder.repository');
            /** @var Connection $connection */
            $connection = $this->container->get(Connection::class);

            $installer = new Installer(
                $mediaDefaultFolderRepository,
                $connection
            );
            $installer->install($updateContext->getContext());
        }

        parent::update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var EntityRepository $mediaFolderRepository */
        $mediaFolderRepository = $this->container->get('media_folder.repository');
        /** @var EntityRepository $mediaRepository */
        $mediaRepository = $this->container->get('media.repository');
        /** @var EntityRepository $mediaDefaultFolderRepository */
        $mediaDefaultFolderRepository = $this->container->get('media_default_folder.repository');
        /** @var EntityRepository $mediaFolderConfigRepository */
        $mediaFolderConfigRepository = $this->container->get('media_folder_configuration.repository');

        $uninstaller = new Uninstaller(
            $mediaFolderRepository,
            $mediaRepository,
            $mediaDefaultFolderRepository,
            $mediaFolderConfigRepository
        );
        $uninstaller->uninstall($uninstallContext->getContext());
    }

    /**
     * @param null $default
     */
    public function setValue(string $configName, $default = null): void
    {
        $systemConfigService = $this->container->get(SystemConfigService::class);
        $domain = $this->getName() . '.config.';

        if ($systemConfigService->get($domain . $configName) === null) {
            $systemConfigService->set($domain . $configName, $default);
        }
    }

    private function addInstallDefaultConfiguration(): void
    {
        $this->setValue('font', 'base');
    }

    private function addUpdateDefaultConfiguration(): void
    {
        $this->setValue('font', 'custom');
    }

    private function addDefaultConfiguration(): void
    {
        $this->setValue('statemanager', ['d-block d-sm-none', 'd-sm-block d-md-none', 'd-md-block d-lg-none', 'd-lg-block d-xl-none', 'd-xl-block']);
        $this->setValue('text', "Lorem ipsum dolor sit amet\r\nconsetetur sadipscing elitr");
        $this->setValue('backgroundImageOpacity', '.40');
        $this->setValue('fontWeight', '600');
        $this->setValue('expiration', '365');
    }
}
