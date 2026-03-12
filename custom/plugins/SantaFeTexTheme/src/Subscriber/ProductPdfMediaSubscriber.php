<?php
namespace SantaFeTexTheme\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProductPdfMediaSubscriber implements EventSubscriberInterface
{
    private $mediaRepository;

    public function __construct(EntityRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event)
    {
        $product = $event->getPage()->getProduct();
        $customFields = $product->getTranslated()['customFields'] ?? [];
        $mediaId = $customFields['products_additional_data_prosheet'] ?? null;
        if ($mediaId) {
            $criteria = new Criteria([$mediaId]);
            $media = $this->mediaRepository->search($criteria, $event->getContext())->first();
            if ($media) {
                $event->getPage()->addExtension('productPdfMedia', $media);
            }
        }
    }
}
