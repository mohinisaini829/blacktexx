<?php

declare(strict_types=1);

namespace salty\ColorVariants\Services;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ColorVariantsServiceInterface
{
    /**
     * @phpstan-return array<mixed>
     */
    public function getColorVariants(?Criteria $criteria, SalesChannelContext $context): array;

    /**
     * @param EntityCollection<ProductEntity> $result
     */
    public function buildCriteria(EntityCollection $result, SalesChannelContext $salesChannelContext): ?Criteria;
}
