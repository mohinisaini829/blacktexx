<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1712765126AddSlugContraintUnique extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1712765126;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `sas_blog_entries_translation`
            ADD CONSTRAINT `uniq.slug` UNIQUE (slug, language_id);
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
