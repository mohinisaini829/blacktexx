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
class Migration1723642533 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1723642532;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sas_tag_product` (
                `sas_tag_id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,

                PRIMARY KEY (`sas_tag_id`,`product_id`, `product_version_id`),
                KEY `fk.sas_tag_product.sas_tag_id` (`sas_tag_id`),
                CONSTRAINT `fk.sas_tag_product.sas_tag_id` FOREIGN KEY (`sas_tag_id`) REFERENCES `sas_tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.sas_tag_product.product_id` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
