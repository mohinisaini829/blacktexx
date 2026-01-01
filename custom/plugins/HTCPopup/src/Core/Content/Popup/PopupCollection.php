<?php declare(strict_types=1);

namespace HTC\Popup\Core\Content\Popup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                      add(PopupEntity $entity)
 * @method void                      set(string $key, PopupEntity $entity)
 * @method PopupEntity[]    getIterator()
 * @method PopupEntity[]    getElements()
 * @method PopupEntity|null get(string $key)
 * @method PopupEntity|null first()
 * @method PopupEntity|null last()
 */
class PopupCollection extends EntityCollection
{
    /**
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return PopupEntity::class;
    }
}
