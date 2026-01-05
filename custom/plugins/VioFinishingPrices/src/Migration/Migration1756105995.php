<?php declare(strict_types=1);

namespace Vio\FinishingPrices\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1756105995 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1756105995;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE finishing_price_table_translation (
    finishing_price_table_id BINARY(16) NOT NULL,
    language_id BINARY(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    text LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX IDX_562C31DD6965D91F (finishing_price_table_id),
    INDEX IDX_562C31DD82F1BAF4 (language_id),
    PRIMARY KEY(finishing_price_table_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $connection->executeStatement('ALTER TABLE finishing_price_table_translation
    ADD CONSTRAINT `fk.finishing_price_table_translation.finishing_price_table_id`
        FOREIGN KEY (finishing_price_table_id) REFERENCES finishing_price_table (id) ON UPDATE CASCADE ON DELETE CASCADE'
        );
        $connection->executeStatement('ALTER TABLE finishing_price_table_translation
    ADD CONSTRAINT `fk.finishing_price_table_translation.language_id`
        FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE CASCADE ON DELETE CASCADE'
        );

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
