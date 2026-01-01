<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom;

use Acris\CookieConsent\Custom\Aggregate\CookieGroupTranslation\CookieGroupTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CookieGroupDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_cookie_group';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CookieGroupCollection::class;
    }

    public function getEntityClass(): string
    {
        return CookieGroupEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new OneToManyAssociationField('cookies', CookieDefinition::class, 'cookie_group_id'),
            new TranslatedField('title'),
            new TranslatedField('description'),
            new BoolField('is_default', 'isDefault'),
            new StringField('identification', 'identification'),
            new TranslationsAssociationField(CookieGroupTranslationDefinition::class, 'acris_cookie_group_id'),
        ]);
    }
}
