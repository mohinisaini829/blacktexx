<?php declare(strict_types=1);

namespace CustomBrand\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration20251231BrandData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 20251231000000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `branddata` (
                `id` BINARY(16) NOT NULL,
                `dropdown_value` VARCHAR(255) NOT NULL,
                `text1` VARCHAR(255) NOT NULL,
                `text2` VARCHAR(255) NOT NULL,
                `media_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}