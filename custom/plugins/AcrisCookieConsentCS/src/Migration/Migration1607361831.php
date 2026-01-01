<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1607361831 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1607361831;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            ALTER TABLE `acris_cookie` MODIFY COLUMN `cookie_id` LONGTEXT NULL;
SQL;
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
