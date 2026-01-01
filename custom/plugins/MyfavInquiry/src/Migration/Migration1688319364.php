<?php declare(strict_types=1);

namespace Myfav\Inquiry\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1688319364 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688319364;
    }

    /**
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE
        myfav_inquiry
        (
            id            BINARY(16) NOT NULL,
            customer_id   BINARY(16) DEFAULT NULL,
            salutation_id BINARY(16) DEFAULT NULL,
            first_name    VARCHAR(255) DEFAULT NULL,
            last_name     VARCHAR(255) DEFAULT NULL,
            email         VARCHAR(255) NOT NULL,
            company       VARCHAR(255) DEFAULT NULL,
            phone_number  VARCHAR(255) DEFAULT NULL,
            delivery_date DATE DEFAULT NULL,
            COMMENT LONGTEXT DEFAULT NULL,
            sales_channel_id BINARY(16) DEFAULT NULL,
            created_by_id BINARY(16) DEFAULT NULL,
            updated_by_id BINARY(16) DEFAULT NULL,
            created_at    DATETIME NOT NULL,
            updated_at    DATETIME DEFAULT NULL,
            INDEX IDX_MYFAV_INQUIRY_INDEX (salutation_id),
            PRIMARY KEY(id)
        )
        DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');

        $connection->executeStatement('ALTER TABLE
            myfav_inquiry ADD CONSTRAINT `fk.myfav_inquiry.customer_id` FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE CASCADE ON DELETE SET NULL'
        );
        
        $connection->executeStatement('ALTER TABLE
            myfav_inquiry ADD CONSTRAINT `fk.myfav_inquiry.salutation_id` FOREIGN KEY (salutation_id) REFERENCES salutation (id) ON UPDATE CASCADE ON DELETE SET NULL'
        );
        
        $connection->executeStatement('ALTER TABLE
            myfav_inquiry ADD CONSTRAINT `fk.myfav_inquiry.sales_channel_id` FOREIGN KEY (sales_channel_id) REFERENCES sales_channel (id)
            ON
            UPDATE
                CASCADE
            ON
            DELETE
                SET NULL'
        );
        
        $connection->executeStatement('CREATE INDEX IDX_INQUIRY_SALES_CHANNEL_ID ON myfav_inquiry (sales_channel_id)');
        $connection->executeStatement('ALTER TABLE myfav_inquiry ADD COLUMN status VARCHAR(255) DEFAULT NULL');
        $connection->executeStatement('ALTER TABLE myfav_inquiry ADD COLUMN admin_user VARCHAR(255) DEFAULT NULL');
    

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
