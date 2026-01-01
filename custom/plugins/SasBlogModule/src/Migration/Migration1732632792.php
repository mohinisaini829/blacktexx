<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('discovery')]
class Migration1732632792 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1732632792;
    }

    public function update(Connection $connection): void
    {
        // create sas_tag_translation table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sas_tag_translation` (
                `sas_tag_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                PRIMARY KEY (`sas_tag_id`, `language_id`),
                UNIQUE `uniq.sas_tag_translation.name` (`name`),
                CONSTRAINT `fk.sas_tag_translation.sas_tag_id` FOREIGN KEY (`sas_tag_id`) REFERENCES `sas_tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.sas_tag_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // migrate value from column name of sas_tag to sas_tag_translation, with language default is english
        $connection->executeStatement('
            INSERT INTO `sas_tag_translation` (`sas_tag_id`, `language_id`, `name`, `created_at`)
            SELECT `id`, :languageId, `name`, `created_at`
            FROM `sas_tag`
        ', ['languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]);

        // drop column name of sas_tag
        $connection->executeStatement('
            ALTER TABLE `sas_tag`
            DROP COLUMN `name`
        ');
    }
}
