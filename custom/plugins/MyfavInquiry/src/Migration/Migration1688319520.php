<?php declare(strict_types=1);

namespace Myfav\Inquiry\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1688319520 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688319520;
    }

    /**
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE
        myfav_inquiry_line_item
        (
            id                 BINARY(16) NOT NULL,
            inquiry_id         BINARY(16) NOT NULL,
            product_id         BINARY(16) DEFAULT NULL,
            product_version_id BINARY(16) DEFAULT NULL,
            custom_identifier  VARCHAR(128) DEFAULT NULL,
            extended_data      LONGTEXT DEFAULT NULL,
            quantity           INT NOT NULL,
            price              DOUBLE PRECISION DEFAULT NULL,
            created_at         DATETIME NOT NULL,
            updated_at         DATETIME DEFAULT NULL,
            INDEX IDX_INQUIRY_LINE_ITEM_ID (inquiry_id),
            INDEX IDX_INQUIRY_LINE_ITEM_IDENTIFIER (product_id, product_version_id),
            PRIMARY KEY(id)
        )
        DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        
        $connection->executeStatement('ALTER TABLE myfav_inquiry_line_item
            ADD CONSTRAINT `fk.myfav_inquiry_line_item.inquiry_id` FOREIGN KEY (inquiry_id) REFERENCES myfav_inquiry (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $connection->executeStatement('ALTER TABLE myfav_inquiry_line_item
            ADD CONSTRAINT `fk.myfav_inquiry_line_item.product_id` FOREIGN KEY (product_id, product_version_id) REFERENCES product (id, version_id) ON UPDATE CASCADE ON DELETE CASCADE');

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
