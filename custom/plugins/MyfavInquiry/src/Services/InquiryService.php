<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Services;

use DateTime;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryMedia\InquiryMediaDefinition;
use Myfav\Inquiry\Entity\Inquiry\InquiryDefinition;
use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryCollection;
use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryEntity;
use Myfav\Inquiry\Event\InquirySendEvent;

class InquiryService
{

    private EntityRepository $inquiryRepository;
    private SalesChannelRepository $productRepository;
    private InquiryCartService $inquiryCartService;
    private EventDispatcherInterface $eventDispatcher;
    private MediaService $mediaService;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $inquiryRepository,
        SalesChannelRepository $productRepository,
        InquiryCartService $inquiryCartService,
        EventDispatcherInterface $eventDispatcher,
        MediaService $mediaService,
        LoggerInterface $logger
    )
    {
        $this->inquiryRepository = $inquiryRepository;
        $this->productRepository = $productRepository;
        $this->inquiryCartService = $inquiryCartService;
        $this->eventDispatcher = $eventDispatcher;
        $this->mediaService = $mediaService;
        $this->logger = $logger;
    }

    /**
     * @param RequestDataBag $data
     * @param SalesChannelContext $context
     * @param UploadedFile[] $logos
     * @return string
     */
    public function createInquiry(
        RequestDataBag $data,
        SalesChannelContext $salesChannelContext,
        array $logos): string
    {
        //die('ggggggggggg');
        // basic data
        $inquiryData = [
            'salutationId' => $data->getAlnum('salutationId'),
            'firstName' => $data->get('firstName'),
            'lastName' => $data->get('lastName'),
            'email' => $data->filter('email', null,FILTER_SANITIZE_EMAIL),
            'company' => $data->get('company'),
            'phoneNumber' => $data->get('phone'),
            'comment' => $data->get('comment'),
            'salesChannelId' => $salesChannelContext->getSalesChannelId()
        ];

        // prepare delivery date
        if($data->has('date')) {
            $deliveryDate = DateTime::createFromFormat('Y-m-d', (string)$data->get('date'));
            if($deliveryDate instanceof DateTime) {
                $inquiryData['deliveryDate'] = $deliveryDate;
            }
        }

        $inquiryData['lineItems'] = $this->setLineItems(
            $data,
            $salesChannelContext
        );

        // set medias
        if(!empty($logos)) {
            $randomUniqueKey = Uuid::randomHex();

            foreach ($logos as $logo) {
                $mediaId = null;
                $salesChannelContext->getContext()
                    ->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($randomUniqueKey, $logo, &$mediaId) {
                        $mediaId = $this->mediaService->saveFile(
                            file_get_contents($logo->getPathname()),
                            $logo->getClientOriginalExtension(),
                            $logo->getMimeType(),
                            $randomUniqueKey . '_' . $logo->getClientOriginalName(),
                            $context,
                            InquiryMediaDefinition::ENTITY_NAME
                        );
                    });
                if ($mediaId) {
                    $inquiryData['medias'][] = [
                        'mediaId' => $mediaId
                    ];
                }
                if (!unlink($logo->getPathname())) {
                    $this->logger->error(sprintf('Couldn\'t delete upload file %s', $logo->getPathname()));
                }
            }
        }

        // create inquiry via dal
        $keys = $this->inquiryRepository->create([$inquiryData], $salesChannelContext->getContext())->getPrimaryKeys(InquiryDefinition::ENTITY_NAME);
        $inquiryId = array_shift($keys);

        // delete all cart entries
        $this->inquiryCartService->clear($salesChannelContext);

        // send business event
        $inquiry = $this->inquiryRepository->search(
            (new Criteria([$inquiryId]))
                ->addAssociation('medias')
                ->addAssociation('lineItems.product')
                ->addAssociation('lineItems.product.media')
            , $salesChannelContext->getContext()
        )->first();
        $this->eventDispatcher->dispatch(
            new InquirySendEvent(
                $salesChannelContext,
                $inquiry,
                $salesChannelContext->getSalesChannelId()
            )
        );

        return $inquiryId;
    }
    
    /**
     * setLineItems
     *
     * @return void
     */
    private function setLineItems(
        RequestDataBag $data,
        SalesChannelContext $salesChannelContext
        
    ) {
        $cartEntries = [];
        $lineItems = [];
        $productIds = $data->get('productIds');
        $products = [];
        
        if($productIds instanceof RequestDataBag) {
            die('I do not know when this is called in FILE ' . __FILE__);

            $cartEntries = new InquiryCartEntryCollection();

            foreach ($productIds as $productId) {
                $entry = (new InquiryCartEntryEntity())
                    ->setProductId($productId)
                    ->setQuantity(1)
                ;
                $entry->setId($productId);
                $cartEntries->add($entry);
            }

            $products = $this->productRepository->search(new Criteria($productIds->all()), $salesChannelContext)->getEntities();
        } else {
            $cartEntries = $this->inquiryCartService->getList($salesChannelContext);
            
            $productIds = [];

            foreach($cartEntries as $cartEntry) {
                if($cartEntry->getProductId() !== null) {
                    $productIds[] = $cartEntry->getProductId();
                }
            }

            if(count($productIds) > 0) {
                $products = $this->productRepository->search(new Criteria($productIds), $salesChannelContext)->getEntities();
            }
        }

        // Aggregate lineItems.
        $lineItems = [];
        
        foreach($cartEntries as $cartEntry) {
            $product = $this->getProductFromArrayById($products, $cartEntry->getProductId());

            $lineItems[] = [
                'productId' => $cartEntry->getProductId(),
                'productVersionId' => $cartEntry->getVersionId(),
                'quantity' => $cartEntry->getQuantity(),
                'price' => $product ? $product->getCalculatedPrice()->getUnitPrice() : null,
                'customIdentifier' => $cartEntry->getCustomIdentifier(),
                'extendedData' => $cartEntry->getExtendedData()
            ];
        }

        return $lineItems;
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

    /**
     * @param $id
     * @param SalesChannelContext $salesChannelContext
     * @return mixed
     */
    public function loadInquiryById(Context $context, $inquiryId): mixed
    {
        // send business event
        $inquiry = $this->inquiryRepository->search(
            new Criteria([$inquiryId])
            , $context
        )->first();

        return $inquiry;
    }

    /**
     * Ein spezielles Inquiry Item abrufen.
     */
    public function getInquiryItems(Context $context, $inquiryId): mixed
    {
        $criteria = new Criteria([$inquiryId]);
        $criteria->addAssociation('lineItems');

        $inquiry = $this->inquiryRepository->search(
            $criteria,
            $context
        )->first();

        return $inquiry;
    }

    /**
     * loadInquiriesByCustomerId
     */
    public function loadInquiriesByCustomerId($index, $limit, $shopwareUserId, $salesChannelContext): mixed
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $shopwareUserId));

        $criteria->addAssociation('lineItems');

        $inquiries = $this->inquiryRepository->search(
            $criteria,
            $salesChannelContext->getContext()
        );

        return $inquiries;


        /*
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_shopware_designs', 'l');
        $queryBuilder->where('shopware_user_id = :shopwareUserId');
        $queryBuilder->setParameter('shopwareUserId', hex2bin($shopwareUserId));
        $queryBuilder->setFirstResult($index);
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->orderBy('l.created', 'DESC');
        $result = $queryBuilder->execute()->fetchAll();

        return $result;
        */
    }
}
