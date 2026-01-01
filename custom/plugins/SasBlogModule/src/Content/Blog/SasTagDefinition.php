<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Sas\BlogModule\Content\Blog\Aggregate\BlogTagDefinition;
use Sas\BlogModule\Content\Blog\SasTagTranslation\SasTagTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SasTagDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'sas_tag';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SasTagCollection::class;
    }

    public function getEntityClass(): string
    {
        return SasTagEntity::class;
    }

    public function since(): ?string
    {
        return '6.6.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        $collection = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new TranslatedField('name'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING), new ApiAware()),

            (new TranslationsAssociationField(SasTagTranslationDefinition::class, 'sas_tag_id'))->addFlags(new Required()),

            (new ManyToManyAssociationField('blogs', BlogEntriesDefinition::class, BlogTagDefinition::class, 'sas_tag_id', 'sas_blog_id'))->addFlags(new CascadeDelete()),
        ]);

        return $collection;
    }
}
