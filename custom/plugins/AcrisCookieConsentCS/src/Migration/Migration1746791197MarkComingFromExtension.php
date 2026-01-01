<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1746791197MarkComingFromExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1746791197;
    }

    public function update(Connection $connection): void
    {
        $query = <<< SQL
             ALTER TABLE `acris_cookie`
                ADD `from_extension` TINYINT(1) DEFAULT '0';
        SQL;

        $connection->executeStatement($query);

        $query = <<< SQL
             UPDATE `acris_cookie` SET `from_extension` = 1 WHERE `provider` = 'Shopware Plugin';
        SQL;

        $connection->executeStatement($query);
    }
}
