<?php
declare(strict_types=1);

namespace Sas\BlogModule\Page\Blog;

use Sas\BlogModule\Content\BlogCategory\BlogCategoryEntity;
use Shopware\Storefront\Page\Navigation\NavigationPage;

class BlogCategoryPage extends NavigationPage
{
    protected ?string $navigationId = null;

    protected ?BlogCategoryEntity $blogCategory;

    public function getBlogCategory(): ?BlogCategoryEntity
    {
        return $this->blogCategory;
    }

    public function setBlogCategory(?BlogCategoryEntity $blogCategory): void
    {
        $this->blogCategory = $blogCategory;
    }

    public function getNavigationId(): ?string
    {
        return $this->navigationId;
    }

    public function setNavigationId(?string $navigationId): void
    {
        $this->navigationId = $navigationId;
    }
}
