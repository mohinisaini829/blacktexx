<?php declare(strict_types=1);

namespace Santafatex\Brands\Core\Content\Brand;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class BrandDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'santafatex_brand';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return BrandEntity::class;
    }

    public function getCollectionClass(): string
    {
        return BrandCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            new LongTextField('description', 'description'),
            new StringField('size_chart_path', 'sizeChartPath'),
            new LongTextField('video_slider_html', 'videoSliderHtml'),
            new StringField('catalog_pdf_path', 'catalogPdfPath'),
            (new BoolField('active', 'active'))->setDefault(true),
            (new IntField('display_order', 'displayOrder'))->setDefault(0),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
