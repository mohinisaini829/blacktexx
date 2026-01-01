<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom;

use Acris\CookieConsent\Custom\Aggregate\CookieTranslation\CookieTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class CookieDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_cookie';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CookieCollection::class;
    }

    public function getEntityClass(): string
    {
        return CookieEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('cookie_group_id', 'cookieGroupId', CookieGroupDefinition::class),
            new ManyToOneAssociationField('cookieGroup', 'cookie_group_id', CookieGroupDefinition::class, 'id', true),
            new ManyToManyAssociationField('salesChannels', SalesChannelDefinition::class, CookieSalesChannelDefinition::class, 'cookie_id', 'sales_channel_id'),
            (new LongTextField('cookie_id', 'cookieId'))->addFlags(new AllowHtml()),
            (new JsonField("google_cookie_consent_mode", "googleCookieConsentMode")),
            new StringField('default_value', 'defaultValue'),
            new TranslatedField('title'),
            new TranslatedField('description'),
            new TranslatedField('script'),
            new StringField('script_position', 'scriptPosition'),
            new StringField('provider', 'provider'),
            new BoolField('active', 'active'),
            new BoolField('unknown', 'unknown'),
            new BoolField('is_default', 'isDefault'),
            new BoolField('from_extension', 'fromExtension'),
            new TranslationsAssociationField(CookieTranslationDefinition::class, 'acris_cookie_id'),
        ]);
    }
}
