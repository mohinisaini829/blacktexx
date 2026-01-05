<?php declare(strict_types=1);

namespace Vio\FinishingPrices\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1756105992 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1756105992;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE finishing_price_table (
    id BINARY(16) NOT NULL,
    active TINYINT(1) DEFAULT 0 NOT NULL,
    position INT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
