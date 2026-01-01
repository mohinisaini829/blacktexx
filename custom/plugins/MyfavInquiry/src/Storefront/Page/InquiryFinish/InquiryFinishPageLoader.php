<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\InquiryFinish;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryLineItem\InquiryLineItemEntity;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;
use Myfav\Inquiry\Exception\InquiryNotFoundException;

class InquiryFinishPageLoader
{

    private EntityRepository $inquiryRepository;
    private GenericPageLoaderInterface $genericPageLoader;
    private EventDispatcherInterface $eventDispatcher;
    private SalesChannelRepository $productRepository;

    public function __construct(
        EntityRepository $inquiryRepository,
        GenericPageLoaderInterface $genericPageLoader,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelRepository $productRepository
    )
    {
        $this->inquiryRepository = $inquiryRepository;
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->productRepository = $productRepository;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext, $returnNullIfNotFound = false): InquiryFinishPage
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);
        /** @var InquiryFinishPage $page */
        $page = InquiryFinishPage::createFrom($page);

        $inquiryId = $request->get('inquiryId');
        if(!$inquiryId) {
            throw new MissingRequestParameterException('inquiryId', '/inquiryId');
        }

        $criteria = (new Criteria([$inquiryId]))
            ->addAssociation('lineItems')
            ->addAssociation('salutation')
            ->addAssociation('medias.media')
        ;

        /** @var InquiryEntity $inquiry */
        $inquiry = $this->inquiryRepository->search($criteria, $salesChannelContext->getContext())->first();

        if (!$inquiry) {
            if($returnNullIfNotFound) {
                $page->setStatus(false);
                return $page;
            }

            throw new InquiryNotFoundException($inquiryId);
        }

        if($inquiry->getLineItems()) {
            // Load additional product data, if it is available.
            $products = [];
            $productIds = [];
            
            foreach($inquiry->getLineItems() as $lineItem) {
                if($lineItem->getProductId() !== null) {
                    $productIds[] = $lineItem->getProductId();
                }
            }

            $productIds = array_unique($productIds);

            if(count($productIds) > 0) {
                $productCriteria = new Criteria($productIds);
                $productCriteria
                    ->addAssociation('options.group');
                $products = $this->productRepository->search(
                    $productCriteria,
                    $salesChannelContext
                )->getEntities();
            }

            // Add product data to the list, if it is available.
            foreach($inquiry->getLineItems() as $lineItem) {
                $tmpProduct = $this->getProductFromArrayById($products, $lineItem->getProductId());

                if($tmpProduct !== null) {
                    $lineItem->setProduct($tmpProduct);
                }
            }
        }

        $page->setInquiry($inquiry);

        $this->eventDispatcher->dispatch(
            new InquiryFinishPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    /**
     * getProductFromArrayById
     *
     * @param  mixed $products
     * @param  mixed $productId
     * @return void
     */
    private function getProductFromArrayById($products, $productId)
    {
        foreach($products as $product) {
            if($product->getId() == $productId) {
                return $product;
            }
        }

        return null;
    }
}
