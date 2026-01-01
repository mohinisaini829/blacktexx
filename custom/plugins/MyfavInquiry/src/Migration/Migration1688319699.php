<?php declare(strict_types=1);

namespace Myfav\Inquiry\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1688319699 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688319699;
    }

    /**
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE
        myfav_inquiry_media
        (
            id         BINARY(16) NOT NULL,
            media_id   BINARY(16) NOT NULL,
            inquiry_id BINARY(16) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_INQUIRY_MEDIA_MEDIA_ID (media_id),
            INDEX IDX_INQUIRY_MEDIA_INQUIRY_ID (inquiry_id),
            PRIMARY KEY(id)
        )
        DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        
        $connection->executeStatement('ALTER TABLE
            myfav_inquiry_media ADD CONSTRAINT `fk.myfav_inquiry_media.media_id` FOREIGN KEY (media_id) REFERENCES media (id)
            ON
            UPDATE
                    CASCADE
            ON
            DELETE
                    CASCADE'
        );
        $connection->executeStatement('ALTER TABLE
        myfav_inquiry_media ADD CONSTRAINT `fk.myfav_inquiry_media.inquiry_id` FOREIGN KEY (inquiry_id) REFERENCES myfav_inquiry (id)
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
