<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom\Aggregate\CookieTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(CookieTranslationEntity $entity)
 * @method void                         set(string $key, CookieTranslationEntity $entity)
 * @method CookieTranslationEntity[]    getIterator()
 * @method CookieTranslationEntity[]    getElements()
 * @method CookieTranslationEntity|null get(string $key)
 * @method CookieTranslationEntity|null first()
 * @method CookieTranslationEntity|null last()
 */
class CookieTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CookieTranslationEntity::class;
    }
}
