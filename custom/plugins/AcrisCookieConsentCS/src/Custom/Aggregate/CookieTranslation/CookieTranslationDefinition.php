<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom\Aggregate\CookieTranslation;

use Acris\CookieConsent\Custom\CookieDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;


class CookieTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'acris_cookie_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CookieTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return CookieTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return CookieDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('title', 'title')),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml()),
            (new LongTextField('script', 'script'))->addFlags(new AllowHtml(false)),
        ]);
    }
}
