<?php

declare(strict_types=1);

namespace Vio\FinishingPrices\Content;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                           add(FinishingPriceTableEntity $entity)
 * @method void                           set(string $key, FinishingPriceTableEntity $entity)
 * @method FinishingPriceTableEntity[]    getIterator()
 * @method FinishingPriceTableEntity[]    getElements()
 * @method FinishingPriceTableEntity|null get(string $key)
 * @method FinishingPriceTableEntity|null first()
 * @method FinishingPriceTableEntity|null last()
 */
class FinishingPriceTableCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return FinishingPriceTableEntity::class;
    }
}
