<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SasTagTranslation;

use Sas\BlogModule\Content\Blog\SasTagDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SasTagTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'sas_tag_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SasTagTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return SasTagTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return SasTagDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
        ]);
    }
}
