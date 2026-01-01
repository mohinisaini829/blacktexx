<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\EmailField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryLineItem\InquiryLineItemDefinition;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryMedia\InquiryMediaDefinition;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer\InquiryOfferDefinition;

class InquiryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'myfav_inquiry';

    public function getEntityName() : string
    {
        return self::ENTITY_NAME;
    }
    protected function defineFields() : FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

            (new FkField('salutation_id', 'salutationId', SalutationDefinition::class, 'id'))
                ->addFlags(new ApiAware()),

            (new FkField('customer_id', 'customerId', CustomerDefinition::class, 'id'))
                ->addFlags(new ApiAware()),

            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class, 'id'))
                ->addFlags(new ApiAware()),

            new StringField('first_name', 'firstName', 255),
            new StringField('last_name', 'lastName', 255),
            (new EmailField('email', 'email', 255))
                ->addFlags(new Required()), new StringField('company', 'company', 255),
            new StringField('phone_number', 'phoneNumber', 255),
            new DateField('delivery_date', 'deliveryDate'),
            new LongTextField('comment', 'comment'),
            new StringField('status', 'status', 255), // Field added here
            new StringField('admin_user', 'admin_user', 255),
            (new CreatedByField())
                ->addFlags(new ApiAware()),
            (new UpdatedByField())
                ->addFlags(new ApiAware()),

            (new ManyToOneAssociationField('salutation', 'salutation_id', SalutationDefinition::class, 'id', true))
                ->addFlags(new ApiAware()),

            (new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', true))
                ->addFlags(new ApiAware()),

            (new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false))
                ->addFlags(new ApiAware()),

            (new OneToManyAssociationField('lineItems', InquiryLineItemDefinition::class, 'inquiry_id'))
                ->addFlags(new ApiAware(), new CascadeDelete()),

            (new OneToManyAssociationField('offers', InquiryOfferDefinition::class, 'inquiry_id'))
                ->addFlags(new ApiAware(), new CascadeDelete()),

            (new OneToManyAssociationField('medias', InquiryMediaDefinition::class, 'inquiry_id'))
                ->addFlags(new ApiAware(), new CascadeDelete()),
        ]);
    }
    public function getEntityClass() : string
    {
        return InquiryEntity::class;
    }
    public function getCollectionClass() : string
    {
        return InquiryCollection::class;
    }
}
