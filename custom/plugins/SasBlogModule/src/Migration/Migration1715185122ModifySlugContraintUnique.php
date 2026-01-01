<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1715185122ModifySlugContraintUnique extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1715185122;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `sas_blog_entries_translation` DROP INDEX `uniq.slug`;
        ');

        $connection->executeStatement('
            ALTER TABLE `sas_blog_entries_translation` ADD CONSTRAINT `uniq.slug_language` UNIQUE (slug, language_id);
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
