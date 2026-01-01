<?php declare(strict_types=1);

namespace HTC\Popup\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1614075537popup_table_config
 * @package HTC\Popup\Migration
 */
class Migration1614075537popup_table_config extends MigrationStep
{
    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1614075537;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `htc_popup`
                ADD `width`  int(10) NULL AFTER `ctr`,
                ADD `height` int(10) NULL AFTER `width`,
                ADD `align_content` int(1) NULL AFTER `height`;
        ');
    }

    /**
     * @param Connection $connection
     */
    public function updateDestructive(Connection $connection): void
    {
        // Do nothing
    }
}
