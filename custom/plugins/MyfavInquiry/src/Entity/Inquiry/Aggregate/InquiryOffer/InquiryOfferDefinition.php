<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Myfav\Inquiry\Entity\Inquiry\InquiryDefinition;

class InquiryOfferDefinition extends EntityDefinition
{

    public const ENTITY_NAME = 'myfav_inquiry_offer';

    public function getEntityName() : string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class, 'id'))
                ->addFlags(new Required(), new ApiAware()),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))
                ->addFlags(new ApiAware()),

            (new FkField('inquiry_id', 'inquiryId', InquiryDefinition::class, 'id'))
                ->addFlags(new Required(),new ApiAware()),
            (new ManyToOneAssociationField('inquiry', 'inquiry_id', InquiryDefinition::class, 'id', false))
                ->addFlags(new ApiAware()),

            (new StringField('offer_number', 'offerNumber'))
                ->addFlags(new Required(), new ApiAware()),
            new BoolField('send', 'send'),

            (new CreatedAtField())
                ->addFlags(new ApiAware()),
            (new UpdatedAtField())
                ->addFlags(new ApiAware()),
            (new CreatedByField())
                ->addFlags(new ApiAware()),
            (new UpdatedByField())
                ->addFlags(new ApiAware()),
        ]);
    }

    public function getEntityClass() : string
    {
        return InquiryOfferEntity::class;
    }
    public function getCollectionClass() : string
    {
        return InquiryOfferCollection::class;
    }
}
