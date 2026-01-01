<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1586943446 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1586943446;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_cookie`
                ADD `default_value` VARCHAR(255) NULL;
SQL;
        try {
            $connection->executeStatement($query);
        } catch (\Exception $e) { }

        $query = <<<SQL
            ALTER TABLE `acris_cookie_group_translation`
                MODIFY title VARCHAR(255) NULL;
SQL;
        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_cookie_translation`
                MODIFY title VARCHAR(255) NULL;
SQL;
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
