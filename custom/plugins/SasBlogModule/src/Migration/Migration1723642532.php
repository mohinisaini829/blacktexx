<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('discovery')]
class Migration1723642532 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1723642532;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `sas_tag` (
              `id` BINARY(16) NOT NULL,
              `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sas_blog_tag` (
              `sas_blog_id` BINARY(16) NOT NULL,
              `sas_tag_id` BINARY(16) NOT NULL,
              PRIMARY KEY (`sas_blog_id`, `sas_tag_id`),
              CONSTRAINT `fk.sas_blog_tag.sas_blog_id` FOREIGN KEY (`sas_blog_id`) REFERENCES `sas_blog_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.sas_blog_tag.sas_tag_id` FOREIGN KEY (`sas_tag_id`) REFERENCES `sas_tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
