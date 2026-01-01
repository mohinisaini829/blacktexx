<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\BlogEntriesTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<BlogEntriesTranslationEntity>
 */
class BlogEntriesTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return BlogEntriesTranslationEntity::class;
    }
}
