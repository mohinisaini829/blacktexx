<?php
declare(strict_types=1);

namespace Sas\BlogModule\Subscriber;

use Sas\BlogModule\Core\Content\Cms\BlogAssignment\AbstractProductBlogAssignmentRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductPageLoadedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AbstractProductBlogAssignmentRoute $productBlogAssignmentRoute,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $product = $page->getProduct();

        $blogs = $this->productBlogAssignmentRoute->load($product->getId(), new Request(), $event->getSalesChannelContext(), new Criteria())->getResult();

        $page->addExtension('sas_blogs', $blogs);
    }
}
