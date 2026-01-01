<?php

declare(strict_types=1);

namespace Vio\FinishingPrices\Content;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Vio\FinishingPrices\Content\Aggregate\FinishingPriceTableTranslation\FinishingPriceTableTranslationDefinition;

class FinishingPriceTableDefinition extends EntityDefinition
{

    public const ENTITY_NAME = 'finishing_price_table';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return FinishingPriceTableEntity::class;
    }

    public function getCollectionClass(): string
    {
        return FinishingPriceTableCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),
            new TranslatedField('name'),
            new TranslatedField('text'),
            (new BoolField('active', 'active'))
                ->addFlags(new Required()),
            new IntField('position', 'position', 0, null),
            new TranslationsAssociationField(FinishingPriceTableTranslationDefinition::class, 'finishing_price_table_id')
        ]);
    }
}
