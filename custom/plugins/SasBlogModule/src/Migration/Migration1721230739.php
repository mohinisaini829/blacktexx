<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('discovery')]
class Migration1721230739 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1721230739;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `sas_blog_category_translation` MODIFY COLUMN `name` VARCHAR(255) NULL;');
    }
}
