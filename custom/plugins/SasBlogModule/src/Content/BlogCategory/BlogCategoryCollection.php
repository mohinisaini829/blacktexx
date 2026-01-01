<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\BlogCategory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<BlogCategoryEntity>
 */
class BlogCategoryCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'sas_blog_category_collection';
    }

    protected function getExpectedClass(): string
    {
        return BlogCategoryEntity::class;
    }
}
