<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(CookieGroupEntity $entity)
 * @method void              set(string $key, CookieGroupEntity $entity)
 * @method CookieGroupEntity[]    getIterator()
 * @method CookieGroupEntity[]    getElements()
 * @method CookieGroupEntity|null get(string $key)
 * @method CookieGroupEntity|null first()
 * @method CookieGroupEntity|null last()
 */
class CookieGroupCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CookieGroupEntity::class;
    }
}
