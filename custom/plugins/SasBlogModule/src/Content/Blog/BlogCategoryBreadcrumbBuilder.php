<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Sas\BlogModule\Content\BlogCategory\BlogCategoryEntity;

class BlogCategoryBreadcrumbBuilder
{
    /**
     * @return array<mixed>|null
     */
    public function build(BlogCategoryEntity $category): ?array
    {
        return $category->getPlainBreadcrumb();
    }
}
