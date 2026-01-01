<?php declare(strict_types=1);


namespace HTC\Popup\Core\Content\Popup\Aggregate\PopupTranslation;

use HTC\Popup\Core\Content\Popup\PopupDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class PopupTranslationDefinition
 * @package HTC\Popup\Core\Content\Popup\Aggregate\PopupTranslation
 */
class PopupTranslationDefinition extends EntityTranslationDefinition
{
    /**
     * Const
     */
    public const ENTITY_NAME = 'htc_popup_translation';

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
    public function getCollectionClass(): string
    {
        return PopupTranslationCollection::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return PopupTranslationEntity::class;
    }

    /**
     * @return string
     */
    public function getParentDefinitionClass(): string
    {
        return PopupDefinition::class;
    }

    /**
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new LongTextField('content', 'content'))->addFlags(new AllowHtml()),
            (new LongTextField('stores', 'stores'))->addFlags(new AllowHtml())
        ]);
    }
}
