<?php
declare(strict_types=1);


namespace Myfav\Inquiry\Storefront\Page\InquiryConfirm;

use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryEntity;
use Myfav\Inquiry\Services\InquiryCartService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
//use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Storefront\Event\RouteRequest\SalutationRouteRequestEvent;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * InquiryConfirmPageLoader
 */
class InquiryConfirmPageLoader
{

    private InquiryCartService $inquiryCartService;
    private GenericPageLoaderInterface $genericPageLoader;
    private EventDispatcherInterface $eventDispatcher;
    private SalesChannelRepository $productRepository;
    private AbstractSalutationRoute $salutationRoute;
    private $container;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        InquiryCartService $inquiryCartService,
        GenericPageLoaderInterface $genericPageLoader,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelRepository $productRepository,
        AbstractSalutationRoute $salutationRoute,
        $container
    )
    {
        $this->inquiryCartService = $inquiryCartService;
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->productRepository = $productRepository;
        $this->salutationRoute = $salutationRoute;
        $this->container = $container;
    }

    /**
     * load
     *
     * @param  mixed $request
     * @param  mixed $salesChannelContext
     * @return InquiryConfirmPage
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): InquiryConfirmPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        /** @var InquiryConfirmPage $page */
        $page = InquiryConfirmPage::createFrom($page);

        $entries = $this->inquiryCartService->getList($salesChannelContext);
        
        if(!empty($entries->getElements())) {
            $tmpProductIds = $entries->map(static function (InquiryCartEntryEntity $entity) {
                return $entity->getProductId();
            });

            // Load only those products here, that have "real" product ids.
            // The other custom products/request-items are handled separately.
            $productIds = [];

            foreach($tmpProductIds as $index => $productId) {
                if($productId !== null) {
                    $productIds[$index] = $productId;
                }
            }

            if(count($productIds) > 0) {
                $productCriteria = new Criteria($productIds);
                $productCriteria->addAssociation('options.group');
                $productCriteria->addAssociation('cover.media.thumbnails');
                $productCriteria->addAssociation('media');
                $products = $this->productRepository->search(
                    $productCriteria,
                    $salesChannelContext
                )->getEntities();

                foreach ($entries as $entry) {
                    // Do not remove custom designed products.
                    if($entry->getProductId() === null) {
                        continue;
                    }

                    // Remove products that do not longer exist.
                    $product = $products->get($entry->getProductId());
                    if ($product) {
                        $entry->setProduct($product);
                    } else {
                        // don't display products with no product
                        $entries->remove($entry->getId());
                    }
                }
            }
        }

        // Enhance custom product data
        foreach($entries as $entry) {
            $productId = $entry->getProductId();

            if($productId === null) {
                $extendedData = $entry->getExtendedData();

                $myfavZweidehDataLoaderService = $this->container->get('Myfav\Zweideh\Services\MyfavZweidehDataLoaderService', ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
                $myfavZweidehProductData = [];

                if(null !== $myfavZweidehDataLoaderService) {
                    $myfavZweidehProductData = $myfavZweidehDataLoaderService->loadByExtendedData($extendedData, $salesChannelContext);
                }

                $entry->addExtension(
                    'myfavZweidehProductData', new ArrayEntity($myfavZweidehProductData)
                );
            }
        }
        
        $page->setInquiryCartEntryCollection($entries);

        $salutations = $this->getSalutations($salesChannelContext, $request);
        $page->setSalutations($salutations);

        $this->eventDispatcher->dispatch(
            new InquiryConfirmPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }
    
    /**
     * getSalutations
     *
     * @param  mixed $context
     * @param  mixed $request
     * @return SalutationCollection
     */
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
