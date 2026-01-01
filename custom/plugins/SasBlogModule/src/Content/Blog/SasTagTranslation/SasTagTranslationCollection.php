<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SasTagTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<SasTagTranslationEntity>
 */
class SasTagTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SasTagTranslationEntity::class;
    }
}
