<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Inquiry\Services\InquiryCartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class AddArticleToVioInquiryController extends StorefrontController
{
    private Connection $connection;
    private InquiryCartService $inquiryCartService;
    private SalesChannelRepository $salesChannelProductRepository;

    public function __construct(
        Connection $connection,
        SalesChannelRepository $salesChannelProductRepository,
        InquiryCartService $inquiryCartService
    ) {
        $this->connection = $connection;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->inquiryCartService = $inquiryCartService;
    }

   #[Route(
        path: '/myfavDesigner/addArticleToVioInquiry',
        name: 'frontend.myfav.designer.add.article.to.vio.inquiry',
        methods: ['GET','POST'],
        defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false]
    )]
    public function add(Request $request, SalesChannelContext $salesChannelContext): JsonResponse {
        $lumiseShopwareDesignsId = $request->request->get('lumiseShopwareDesignsId');
        $lumiseTmpCartId = $request->request->get('lumiseTmpCartId');
        $lumiseArticleId = (int)$request->request->get('lumiseArticleId');
        $selectedSizes = $request->request->all('selectedSizes');
        //$selectedSizes = $request->request->get('selectedSizes');
        $comment = $request->request->get('comment');

        if(0 === $lumiseArticleId) {
            throw new \Exception('Article not found');
        }

        $product = $this->loadShopwareArticleByLumiseId($lumiseArticleId, $salesChannelContext);
        foreach($selectedSizes as $size) {
            $sizeName = $size['name'];
            $sizeQuantity = $size['qty'];

            $this->addVioB2bLineItem(
                $product,
                $sizeName,
                $sizeQuantity,
                $lumiseShopwareDesignsId,
                $lumiseTmpCartId,
                $lumiseArticleId,
                $comment,
                $salesChannelContext
            );
            
        }
        
        
        return new JsonResponse([
            'status' => 'success'
        ]);
    }
    
    /**
     * loadShopwareArticleByLumiseId
     *
     * @param  mixed $lumiseId
     * @param  mixed $salesChannelContext
     * @return mixed
     */
    private function loadShopwareArticleByLumiseId($lumiseArticleId, $salesChannelContext)
    {
        // Load shopware product id by lumise product id.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products_hashes', 'lph');
        $queryBuilder->where('lph.lumise_product_id = :lumise_product_id');
        $queryBuilder->setParameter('lumise_product_id', $lumiseArticleId);
        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (!is_array($results) || count($results) == 0) {
            throw new \Exception('Product with custom field value lumis_designer_article_id = ' . htmlspecialchars("" . $lumiseArticleId) . 'not found');
        }

        $shopwareProductId = $results[0]['shopware_product_id'];
        
        // Load Shopware Article by Custom Field?!?
        $criteria = new Criteria([$shopwareProductId]);
        $criteria->addAssociation('customFields')
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media')
            ->addAssociation('configuratorSettings.option')
            ->addAssociation('configuratorSettings.option.media')
            ->addAssociation('manufacturer')
            ->addAssociation('configuratorSettings.option.group');
        //$criteria->addFilter(new EqualsFilter('customFields.lumis_designer_article_id', $lumiseArticleId));
        
        $products = $this->salesChannelProductRepository->search(
            $criteria,
            $salesChannelContext
        );

        $product = $products->first();

        if(null === $product) {
            throw new \Exception('Product with custom field value lumis_designer_article_id = ' . htmlspecialchars((string)$lumiseArticleId) . 'not found');
        }

        return $product;
    }
    
    /**
     * addVioB2bLineItem
     *
     * @return void
     */
    private function addVioB2bLineItem(
        $product,
        $sizeName,
        $sizeQuantity, 
        $lumiseShopwareDesignsId,
        $lumiseTmpCartId,
        $lumiseArticleId,
        $comment,
        $salesChannelContext)
    {
        $tmpCartId = $lumiseTmpCartId;

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_shopware_designs', 'l');
        $queryBuilder->where('l.tmp_cart_id = ?');
        //$queryBuilder->setParameter(0, $key);
        $queryBuilder->setParameter(1, $tmpCartId);
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (!is_array($result) || count($result) == 0) {
            return null;
        }

        $result = $result[0];
        $images = [];
        $imageFile = '';

        $timestamp = strtotime($result['created']);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $path = '/lumise/data/swCustomerDesigns/' . $year . '/' . $month . '/' . $result['tmp_cart_id'] . '/';

        $jsonFilePath = $path;
        //$previewImageData = file_get_contents($jsonFilePath);

        //print_r($previewImageData);
       // die;
            //$previewImageData = json_decode($previewImageData, true);
        // Check if product is valid
        if ($product) {
            $color = null;

            // Loop through the variations to get color information
            foreach ($product->getVariation() as $variation) {
                if ($variation['group'] === 'Color') {
                    $color = $variation['option'];
                    break; // Stop once the color is found
                }
            }

            /*if ($color) {
                echo "Product Color: " . $color;  // Output the color (e.g., "kiwi")
            } else {
                echo "Color not found for this product.";
            }*/
        } else {
            echo "Product not found!";
        }
        $manufacturerName = $product->getManufacturer() ? $product->getManufacturer()->getName() : '';

        $extendedData = [
            'originalProductId' => $product->getId(),
            'sizeName' => $sizeName,
            'lumiseShopwareDesignsId' => $lumiseShopwareDesignsId,
            'lumiseTmpCartId' => $lumiseTmpCartId,
            'lumiseArticleId' => $lumiseArticleId,
            'comment' => $comment,
            'modifiedProductImage' => $path,
            'color' => $color,
            'model' => $product->getProductNumber(),
            'brand' => $manufacturerName,
        ];
        //print_r($extendedData);die;
        $addParam = [
            'productId' => null,
            'customIdentifier' => $lumiseShopwareDesignsId . '-' . $sizeName,
            'extendedData' => $extendedData,
            'quantity' => $sizeQuantity
        ];
        $this->inquiryCartService->addCustomProduct($addParam, $salesChannelContext);
    }
}