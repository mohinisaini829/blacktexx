<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Extension;

use Sas\BlogModule\Content\Blog\Aggregate\BlogProductMappingDefinition;
use Sas\BlogModule\Content\Blog\Aggregate\SasTagProductMappingDefinition;
use Sas\BlogModule\Content\Blog\BlogEntriesDefinition;
use Sas\BlogModule\Content\Blog\SasTagDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'assignedBlogs',
                BlogEntriesDefinition::class,
                BlogProductMappingDefinition::class,
                'product_id',
                'sas_blog_entries_id'
            ))->addFlags(new ApiAware(), new CascadeDelete())
        );

        $collection->add(
            (new ManyToManyAssociationField(
                'blogTags',
                SasTagDefinition::class,
                SasTagProductMappingDefinition::class,
                'product_id',
                'sas_tag_id'
            ))->addFlags(new ApiAware(), new CascadeDelete())
        );
    }

    public function getEntityName(): string
    {
        return ProductDefinition::ENTITY_NAME;
    }
}
