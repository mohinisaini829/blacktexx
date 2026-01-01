<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1578654861 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1578654861;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_cookie_group` (
                `id` BINARY(16) NOT NULL,
                `is_default` TINYINT(1) NULL DEFAULT '0',
                `identification` VARCHAR(255) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_cookie` (
                `id` BINARY(16) NOT NULL,
                `cookie_group_id` BINARY(16) NULL,
                `cookie_id` VARCHAR(255) NULL,
                `provider` VARCHAR(255) NULL,
                `active` TINYINT(1) NULL DEFAULT '0',
                `unknown` TINYINT(1) NULL DEFAULT '0',
                `is_default` TINYINT(1) NULL DEFAULT '0',
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                PRIMARY KEY (`id`),
                KEY `fk.acris_cookie.cookie_group_id` (`cookie_group_id`),
                CONSTRAINT `fk.acris_cookie.cookie_group_id` FOREIGN KEY (`cookie_group_id`) REFERENCES `acris_cookie_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_cookie_sales_channel` (
                `cookie_id` BINARY(16) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`cookie_id`,`sales_channel_id`),
                KEY `fk.acris_cookie_sales_channel.cookie_id` (`cookie_id`),
                KEY `fk.acris_cookie_sales_channel.sales_channel_id` (`sales_channel_id`),
                CONSTRAINT `fk.acris_cookie_sales_channel.cookie_id` FOREIGN KEY (`cookie_id`) REFERENCES `acris_cookie` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_cookie_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_cookie_translation` (
                `title` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                `acris_cookie_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`acris_cookie_id`,`language_id`),
                KEY `fk.acris_cookie_translation.acris_cookie_id` (`acris_cookie_id`),
                KEY `fk.acris_cookie_translation.language_id` (`language_id`),
                CONSTRAINT `fk.acris_cookie_translation.acris_cookie_id` FOREIGN KEY (`acris_cookie_id`) REFERENCES `acris_cookie` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_cookie_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_cookie_group_translation` (
                `title` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                `acris_cookie_group_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`acris_cookie_group_id`,`language_id`),
                KEY `fk.acris_cookie_group_translation.acris_cookie_group_id` (`acris_cookie_group_id`),
                KEY `fk.acris_cookie_group_translation.language_id` (`language_id`),
                CONSTRAINT `fk.acris_cookie_group_translation.acris_cookie_group_id` FOREIGN KEY (`acris_cookie_group_id`) REFERENCES `acris_cookie_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_cookie_group_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'sales_channel', 'cookies');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
