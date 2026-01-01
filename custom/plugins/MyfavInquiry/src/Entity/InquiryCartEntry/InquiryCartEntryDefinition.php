<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\InquiryCartEntry;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;

class InquiryCartEntryDefinition extends EntityDefinition
{

    public const ENTITY_NAME = 'myfav_inquiry_cart_entry';

    public function getEntityName() : string
    {
        return self::ENTITY_NAME;
    }
    protected function defineFields() : FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new StringField('token', 'token'))
                ->addFlags(new Required()),

            (new IntField('quantity', 'quantity'))
                ->addFlags(new Required()),

            (new FkField('product_id', 'productId', ProductDefinition::class)),

            (new ReferenceVersionField(ProductDefinition::class)),

            (new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false))
                ->addFlags(new CascadeDelete()),

            (new StringField('custom_identifier', 'customIdentifier')),

            (new LongTextField('extended_data', 'extendedData')),
        ]);
    }

    public function getEntityClass() : string
    {
        return InquiryCartEntryEntity::class;
    }
    public function getCollectionClass() : string
    {
        return InquiryCartEntryCollection::class;
    }
}
