<?php declare(strict_types=1);

namespace HTC\Popup\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1640162435add_is_redirect_config
 * @package HTC\Popup\Migration
 */
class Migration1640162435add_is_redirect_config extends MigrationStep
{
    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1640162435;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement("ALTER TABLE `htc_popup`
            ADD `is_redirect` tinyint(1) NOT NULL DEFAULT 0 AFTER `ctr`,
            ADD `confirm_button_title` varchar(255) NULL AFTER `is_redirect`,
            ADD `deny_button_title` varchar(255) NULL AFTER `confirm_button_title`,
            ADD `deny_button_link` varchar(255) NULL AFTER `deny_button_title`,
            ADD `background_color_button` varchar(20) NULL AFTER `deny_button_link`");
    }

    /**
     * @param Connection $connection
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
