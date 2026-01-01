<?php declare(strict_types=1);

namespace HTC\Popup\Core\Content\Popup;

use HTC\Popup\Core\Content\Popup\Aggregate\PopupTranslation\PopupTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;

/**
 * Class PopupDefinition
 * @package HTC\Popup\Core\Content\Popup
 */
class PopupDefinition extends EntityDefinition
{
    /**
     * Const
     */
    public const ENTITY_NAME = 'htc_popup';

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return PopupEntity::class;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        return PopupCollection::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
        [
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new BoolField('active', 'active'),
            (new StringField('title', 'title')),
            (new StringField('visible_on', 'visibleOn')),
            new BoolField('show_guest', 'showGuest'),
            (new StringField('customer_group_ids', 'customerGroupIds')),
            new TranslatedField('content'),
            new TranslatedField('stores'),
            new LongTextField('css', 'css'),
            (new StringField('class_name', 'className')),
            (new StringField('text_color', 'textColor')),
            (new IntField('priority', 'priority'))->addFlags(new Required()),
            (new IntField('frequency', 'frequency')),
            new FkField('background_media_id', 'backgroundMediaId', MediaDefinition::class),
            new IntField('view', 'view'),
            new IntField('click', 'click'),
            new FloatField('ctr', 'ctr'),
            (new IntField('width', 'width')),
            new IntField('height', 'height'),
            (new IntField('align_content', 'alignContent')),
            new BoolField('is_redirect', 'isRedirect'),
            new StringField('confirm_button_title', 'confirmButtonTitle'),
            new StringField('deny_button_title', 'denyButtonTitle'),
            new StringField('deny_button_link', 'denyButtonLink'),
            new StringField('background_color_button', 'backgroundColorButton'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new TranslationsAssociationField(PopupTranslationDefinition::class, 'htc_popup_id'),
            new ManyToOneAssociationField('backgroundMedia', 'background_media_id', MediaDefinition::class, 'id', true),
        ]);
    
    }
}
