<?php declare(strict_types=1);

namespace Santafatex\Brands\Core\Content\Brand;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class BrandCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'santafatex_brand_collection';
    }

    protected function getExpectedClass(): string
    {
        return BrandEntity::class;
    }
}
