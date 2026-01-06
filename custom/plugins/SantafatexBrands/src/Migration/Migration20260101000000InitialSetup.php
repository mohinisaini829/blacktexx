<?php declare(strict_types=1);

namespace Santafatex\Brands\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration20260101000000InitialSetup extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1704067200;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `santafatex_brand` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `size_chart_path` VARCHAR(500) NULL,
                `video_slider_html` LONGTEXT NULL,
                `catalog_pdf_path` VARCHAR(500) NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `display_order` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                `updated_at` DATETIME(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `santafatex_brand`');
    }
}
