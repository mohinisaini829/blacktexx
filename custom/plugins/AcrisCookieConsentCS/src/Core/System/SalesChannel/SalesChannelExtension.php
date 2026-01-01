<?php declare(strict_types=1);

namespace Acris\CookieConsent\Core\System\SalesChannel;

use Acris\CookieConsent\Custom\CookieDefinition;
use Acris\CookieConsent\Custom\CookieSalesChannelDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class SalesChannelExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'cookies',
                CookieDefinition::class,
                CookieSalesChannelDefinition::class,
                'sales_channel_id',
                'cookie_id'
            ))->addFlags(new Inherited())
        );
    }

    public function getEntityName(): string{
        return SalesChannelDefinition::ENTITY_NAME;
    }
}
