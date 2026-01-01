<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1702396437AddAssignmentProductsToBlog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1702396437;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sas_blog_product` (
                `sas_blog_entries_id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,

                PRIMARY KEY (`sas_blog_entries_id`,`product_id`, `product_version_id`),
                KEY `fk.sas_blog_product.sas_blog_entries_id` (`sas_blog_entries_id`),
                CONSTRAINT `fk.sas_blog_product.sas_blog_entries_id` FOREIGN KEY (`sas_blog_entries_id`) REFERENCES `sas_blog_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.sas_blog_product.product_id` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
