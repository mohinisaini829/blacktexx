<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Sas\BlogModule\Content\Blog\BlogCategorySeoUrlRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('discovery')]
class Migration1721142248 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1721142248;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `sas_blog_category_translation` ADD `breadcrumb` json NULL AFTER `name`;');
        $connection->executeStatement('UPDATE `sas_blog_category_translation` SET `breadcrumb` = NULL');

        $connection->insert('seo_url_template', [
            'id' => Uuid::randomBytes(),
            'sales_channel_id' => null,
            'route_name' => BlogCategorySeoUrlRoute::ROUTE_NAME,
            'entity_name' => 'sas_blog_category',
            'template' => BlogCategorySeoUrlRoute::DEFAULT_TEMPLATE,
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
}
