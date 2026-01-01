<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\SpecialOffers;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Storefront\Event\RouteRequest\SalutationRouteRequestEvent;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryCollection;
use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryEntity;
use Myfav\Inquiry\Storefront\Page\InquiryConfirm\InquiryConfirmPageLoadedEvent;

class SpecialOffersPageLoader
{
    private GenericPageLoaderInterface $genericPageLoader;
    private EventDispatcherInterface $eventDispatcher;
    private SalesChannelRepositoryInterface $productRepository;
    private AbstractSalutationRoute $salutationRoute;

    public function __construct(
        GenericPageLoaderInterface $genericPageLoader,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelRepositoryInterface $productRepository,
        AbstractSalutationRoute $salutationRoute
    )
    {
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->productRepository = $productRepository;
        $this->salutationRoute = $salutationRoute;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext): SpecialOffersPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        /** @var SpecialOffersPage $page */
        $page = SpecialOffersPage::createFrom($page);

        $entries = new InquiryCartEntryCollection();
        $productIds = $request->query->all('productIds') ?? [];

        if(!empty($productIds)) {
            $productCriteria = new Criteria($productIds);
            
            $productCriteria
                ->addAssociation('options.group')
                ->addAssociation('manufacturer')
            ;

            $products = $this->productRepository->search(
                $productCriteria,
                $salesChannelContext
            )->getEntities();

            foreach ($products as $product) {
                $entry = (new InquiryCartEntryEntity())
                    ->setProductId($product->getId())
                    ->setProduct($product)
                    ->setQuantity(1);
                $entry->setId($product->getId());
                $entries->add($entry);
            }
        }
        $page->setInquiryCartEntryCollection($entries);

        $salutations = $this->getSalutations($salesChannelContext, $request);
        $page->setSalutations($salutations);

        $this->eventDispatcher->dispatch(
            new InquiryConfirmPageLoadedEvent($page, $salesChannelContext, $request)
        );

        $this->eventDispatcher->dispatch(
            new SpecialOffersPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }


    private function getSalutations(SalesChannelContext $context, Request $request): SalutationCollection
    {
        $event = new SalutationRouteRequestEvent($request, new Request(), $context, new Criteria());
        /** @noinspection PhpExpressionResultUnusedInspection */
        $this->eventDispatcher->dispatch($event);

        $salutations = $this->salutationRoute
            ->load($event->getStoreApiRequest(), $context, $event->getCriteria())
            ->getSalutations();

        $salutations->sort(static function (SalutationEntity $a, SalutationEntity $b) {
            return $b->getSalutationKey() <=> $a->getSalutationKey();
        });

        return $salutations;
    }
}
