<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<SasTagEntity>
 */
class SasTagCollection extends EntityCollection
{
    public function getBlogs(): BlogEntriesCollection
    {
        $blogEntries = [[]];
        foreach ($this->elements as $element) {
            $blogs = $element->getBlogEntries();
            if ($blogs) {
                $blogEntries[] = $blogs->getElements();
            }
        }

        $blogEntities = array_merge(...$blogEntries);

        return new BlogEntriesCollection($blogEntities);
    }

    protected function getExpectedClass(): string
    {
        return SasTagEntity::class;
    }
}
