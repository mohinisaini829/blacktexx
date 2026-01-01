<?php
declare(strict_types=1);

namespace Sas\BlogModule\Core\Content\Cms\SalesChannel\Struct;

use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\Struct;

class BlogAssignmentStruct extends Struct
{
    protected ?BlogEntriesCollection $blogEntries = null;

    protected ?ProductEntity $product = null;

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getBlogEntries(): ?BlogEntriesCollection
    {
        return $this->blogEntries;
    }

    public function setBlogEntries(BlogEntriesCollection $blogEntries): void
    {
        $this->blogEntries = $blogEntries;
    }

    public function getApiAlias(): string
    {
        return 'cms_blog_assignment';
    }
}
