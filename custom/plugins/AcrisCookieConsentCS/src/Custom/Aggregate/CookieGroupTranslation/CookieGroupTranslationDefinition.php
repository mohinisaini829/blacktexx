<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom\Aggregate\CookieGroupTranslation;

use Acris\CookieConsent\Custom\CookieGroupDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CookieGroupTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'acris_cookie_group_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CookieGroupTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return CookieGroupTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return CookieGroupDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('title', 'title')),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml()),
        ]);
    }
}
