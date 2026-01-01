<?php
declare(strict_types=1);

namespace SantaFeTexTheme\Storefront\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ManufacturerMedia implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onProductListing',
            ProductSearchCriteriaEvent::class => 'onProductListing',
            ProductSuggestCriteriaEvent::class => 'onProductListing'
        ];
    }

    public function onProductListing($event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('manufacturer.media');
    }
}
