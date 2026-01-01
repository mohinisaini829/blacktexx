<?php declare(strict_types=1);


namespace HTC\Popup\Core\Content\Popup\Aggregate\PopupTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(PopupTranslationEntity $entity)
 * @method void                         set(string $key, PopupTranslationEntity $entity)
 * @method PopupTranslationEntity[]     getIterator()
 * @method PopupTranslationEntity[]     getElements()
 * @method PopupTranslationEntity|null  get(string $key)
 * @method PopupTranslationEntity|null  first()
 * @method PopupTranslationEntity|null  last()
 */
class PopupTranslationCollection extends EntityCollection
{
    /**
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return PopupTranslationEntity::class;
    }
}
