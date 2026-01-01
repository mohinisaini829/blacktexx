<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryMedia;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Myfav\Inquiry\Entity\Inquiry\InquiryDefinition;

class InquiryMediaDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'myfav_inquiry_media';

    public function getEntityName() : string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields() : FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class, 'id'))
                ->addFlags(new Required()),

            (new FkField('inquiry_id', 'inquiryId', InquiryDefinition::class, 'id'))
                ->addFlags(new Required()),

            new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', true),
            
            new ManyToOneAssociationField('inquiry', 'inquiry_id', InquiryDefinition::class, 'id', false)
        ]);
    }

    public function getEntityClass() : string
    {
        return InquiryMediaEntity::class;
    }

    public function getCollectionClass() : string
    {
        return InquiryMediaCollection::class;
    }
}
