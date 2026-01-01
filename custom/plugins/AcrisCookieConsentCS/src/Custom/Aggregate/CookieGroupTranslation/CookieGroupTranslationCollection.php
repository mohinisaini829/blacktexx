<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom\Aggregate\CookieGroupTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(CookieGroupTranslationEntity $entity)
 * @method void                         set(string $key, CookieGroupTranslationEntity $entity)
 * @method CookieGroupTranslationEntity[]    getIterator()
 * @method CookieGroupTranslationEntity[]    getElements()
 * @method CookieGroupTranslationEntity|null get(string $key)
 * @method CookieGroupTranslationEntity|null first()
 * @method CookieGroupTranslationEntity|null last()
 */
class CookieGroupTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CookieGroupTranslationEntity::class;
    }
}
