<?php declare(strict_types=1);

namespace HTC\Popup\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1691652069HTCUpdatePopup extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1691652069;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `htc_popup` ADD `stores` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL AFTER `content`;'
        );

        $connection->executeStatement(
            'ALTER TABLE `htc_popup_translation` ADD `stores` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL AFTER `content`;'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
