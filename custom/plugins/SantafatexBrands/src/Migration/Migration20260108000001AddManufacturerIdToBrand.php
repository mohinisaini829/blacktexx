<?php declare(strict_types=1);

namespace Santafatex\Brands\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration20260108000001AddManufacturerIdToBrand extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1736352000;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            ALTER TABLE `santafatex_brand`
            ADD COLUMN `manufacturer_id` BINARY(16) NULL AFTER `name`,
            ADD KEY `fk.santafatex_brand.manufacturer_id` (`manufacturer_id`),
            ADD CONSTRAINT `fk.santafatex_brand.manufacturer_id` 
                FOREIGN KEY (`manufacturer_id`) 
                REFERENCES `product_manufacturer` (`id`) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
