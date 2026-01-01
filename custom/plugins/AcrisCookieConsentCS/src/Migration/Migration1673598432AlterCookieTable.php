<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1673598432AlterCookieTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1673598432;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_cookie_translation`
                ADD COLUMN `script` LONGTEXT NULL;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_cookie`
                ADD COLUMN `script_position` VARCHAR(255) NULL;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
