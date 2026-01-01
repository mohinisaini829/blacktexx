<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryLineItem;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;

use Myfav\Inquiry\Entity\Inquiry\InquiryDefinition;

class InquiryLineItemDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'myfav_inquiry_line_item';

    public function getEntityName() : string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields() : FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new FkField('inquiry_id', 'inquiryId', InquiryDefinition::class, 'id'))
                ->addFlags(new ApiAware(), new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class, 'id'))
                ->addFlags(new ApiAware()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new ApiAware()),

            (new StringField('custom_identifier', 'customIdentifier')),

            (new LongTextField('extended_data', 'extendedData')),

            (new IntField('quantity', 'quantity', null, null))
                ->addFlags(new Required()),

            new FloatField('price', 'price', null, null),

            new ManyToOneAssociationField('inquiry', 'inquiry_id', InquiryDefinition::class, 'id', false),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false),

        ]);
    }
    public function getEntityClass() : string
    {
        return InquiryLineItemEntity::class;
    }
    public function getCollectionClass() : string
    {
        return InquiryLineItemCollection::class;
    }
}
