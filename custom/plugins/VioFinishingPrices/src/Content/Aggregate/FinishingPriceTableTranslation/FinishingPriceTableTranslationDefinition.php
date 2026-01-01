<?php

declare(strict_types=1);

namespace Vio\FinishingPrices\Content\Aggregate\FinishingPriceTableTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Vio\FinishingPrices\Content\FinishingPriceTableDefinition;

class FinishingPriceTableTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'finishing_price_table_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return FinishingPriceTableTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return FinishingPriceTableTranslationCollection::class;
    }

    public function getParentDefinitionClass(): string
    {
        return FinishingPriceTableDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name', 255))
                ->addFlags(new Required(), new AllowHtml()),
            (new LongTextField('text', 'text'))
                ->addFlags(new AllowHtml())
        ]);
    }
}
