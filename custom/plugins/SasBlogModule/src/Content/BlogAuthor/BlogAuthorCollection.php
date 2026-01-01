<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\BlogAuthor;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<BlogAuthorEntity>
 */
class BlogAuthorCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'sas_blog_author_collection';
    }

    protected function getExpectedClass(): string
    {
        return BlogAuthorEntity::class;
    }
}
