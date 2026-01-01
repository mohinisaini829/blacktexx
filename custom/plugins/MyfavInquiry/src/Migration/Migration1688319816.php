<?php declare(strict_types=1);

namespace Myfav\Inquiry\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1688319816 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688319816;
    }

    /**
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE
        myfav_inquiry_offer
        (
            id            BINARY(16) NOT NULL,
            media_id      BINARY(16) NOT NULL,
            inquiry_id    BINARY(16) NOT NULL,
            offer_number  VARCHAR(255) NOT NULL,
            send          TINYINT(1) DEFAULT \'0\',
            created_by_id BINARY(16) DEFAULT NULL,
            updated_by_id BINARY(16) DEFAULT NULL,
            created_at    DATETIME NOT NULL,
            updated_at    DATETIME DEFAULT NULL,
            INDEX IDX_INQUIRY_OFFER_MEDIA_ID (media_id),
            INDEX IDX_INQUIRY_OFFER_INQUIRY_ID (inquiry_id),
            PRIMARY KEY(id)
        )
        DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $connection->executeStatement('ALTER TABLE
            myfav_inquiry_offer ADD CONSTRAINT `fk.myfav_inquiry_offer.media_id` FOREIGN KEY (media_id) REFERENCES media (id)
            ON
            UPDATE
                CASCADE
            ON
            DELETE
                CASCADE'
        );

        $connection->executeStatement('ALTER TABLE
            myfav_inquiry_offer ADD CONSTRAINT `fk.myfav_inquiry_offer.inquiry_id` FOREIGN KEY (inquiry_id) REFERENCES myfav_inquiry (id)
            ON
            UPDATE
                CASCADE
            ON
            DELETE
                CASCADE'
        );

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
