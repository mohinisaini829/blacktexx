<?php declare(strict_types=1);

namespace HTC\Popup\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class Migration1603946981popup_table
 * @package HTC\Popup\Migration
 */
class Migration1603946981popup_table extends MigrationStep
{   
    use InheritanceUpdaterTrait;

    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1603946981;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {

        /**
         * Create table htc_popup
         */
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `htc_popup` (
                `id` BINARY(16) NOT NULL,
                `active` tinyint(1) NOT NULL DEFAULT 0,
                `title` varchar(255) NOT NULL,
                `visible_on` varchar(255) NULL,
                `show_guest` tinyint(1) NOT NULL DEFAULT 1,
                `customer_group_ids` varchar(255) NULL,
                `priority` INT(11) NOT NULL DEFAULT 1,
                `frequency` tinyint(1) NOT NULL DEFAULT 1,
                `background_media_id` BINARY(16) NULL,
                `content` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL,
                `css` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL,
                `class_name` varchar(64) NULL,
                `text_color` varchar(255) NULL,
                `view` INT(11) NOT NULL DEFAULT 0,
                `click` INT(11) NOT NULL DEFAULT 0,
                `ctr` FLOAT(11) NOT NULL DEFAULT 0,
                `created_at` datetime(3) NOT NULL,
                `updated_at` datetime(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.popup.background_media_id` FOREIGN KEY (`background_media_id`)
                    REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        

        /**
         * Create table htc_popup_translation
         */
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `htc_popup_translation` (
                `htc_popup_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `content` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`htc_popup_id`, `language_id`),
                CONSTRAINT `fk.popup_translation.popup_id` FOREIGN KEY (`htc_popup_id`)
                    REFERENCES `htc_popup` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.popup_translation.language_id` FOREIGN KEY (`language_id`)
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    /**
     * @param Connection $connection
     */
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
