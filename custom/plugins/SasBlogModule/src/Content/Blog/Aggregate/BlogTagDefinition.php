<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\Aggregate;

use Sas\BlogModule\Content\Blog\BlogEntriesDefinition;
use Sas\BlogModule\Content\Blog\SasTagDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class BlogTagDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'sas_blog_tag';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('sas_blog_id', 'blogId', BlogEntriesDefinition::class))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new FkField('sas_tag_id', 'tagId', SasTagDefinition::class))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('blog', 'sas_blog_id', BlogEntriesDefinition::class, 'id', false),
            (new ManyToOneAssociationField('tag', 'sas_tag_id', SasTagDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }
}
