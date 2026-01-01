<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1712765078AddBlogMetaKeywords extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1712765078;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `sas_blog_entries_translation`
            ADD `meta_keywords` VARCHAR(255) NULL AFTER `meta_description`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
