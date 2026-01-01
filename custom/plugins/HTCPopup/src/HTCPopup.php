<?php declare(strict_types=1);

namespace HTC\Popup;

use Shopware\Core\Framework\Plugin;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * Class HTCPopup
 * @package HTC\Popup
 */
class HTCPopup extends Plugin
{
    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `htc_popup_translation`');
        $connection->executeStatement('DROP TABLE IF EXISTS `htc_popup`');
    }
}
