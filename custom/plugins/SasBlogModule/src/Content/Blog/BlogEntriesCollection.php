<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<BlogEntriesEntity>
 */
class BlogEntriesCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return BlogEntriesEntity::class;
    }
}
