<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(CookieEntity $entity)
 * @method void              set(string $key, CookieEntity $entity)
 * @method CookieEntity[]    getIterator()
 * @method CookieEntity[]    getElements()
 * @method CookieEntity|null get(string $key)
 * @method CookieEntity|null first()
 * @method CookieEntity|null last()
 */
class CookieCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CookieEntity::class;
    }
}
