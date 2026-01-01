<?php

declare(strict_types=1);

namespace Vio\FinishingPrices\Content\Aggregate\FinishingPriceTableTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                      add(FinishingPriceTableTranslationEntity $entity)
 * @method void                                      set(string $key, FinishingPriceTableTranslationEntity $entity)
 * @method FinishingPriceTableTranslationEntity[]    getIterator()
 * @method FinishingPriceTableTranslationEntity[]    getElements()
 * @method FinishingPriceTableTranslationEntity|null get(string $key)
 * @method FinishingPriceTableTranslationEntity|null first()
 * @method FinishingPriceTableTranslationEntity|null last()
 */
class FinishingPriceTableTranslationCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return FinishingPriceTableTranslationEntity::class;
    }
}
