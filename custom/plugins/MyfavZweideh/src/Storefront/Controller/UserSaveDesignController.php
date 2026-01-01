<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Zweideh\Services\ShopwareDesignsService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class UserSaveDesignController extends StorefrontController
{
    private Connection $connection;
    private ShopwareDesignsService $shopwareDesignsService;
    private UrlGeneratorInterface $router;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Connection $connection,
        ShopwareDesignsService $shopwareDesignsService,
        UrlGeneratorInterface $router
    ) {
        $this->connection = $connection;
        $this->shopwareDesignsService = $shopwareDesignsService;
        $this->router = $router;
    }

    
    #[Route(
        path: '/myfavDesigner/designSave',
        name: 'frontend.myfav.zweideh.design.save',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false]
    )]
    public function userSaveDesign(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse {
        //die('hhhhhhhh');
        $designKey = $request->query->get('designKey');
        $tmpCartId = $request->query->get('tmpCartId');

        // Verify customer login.
        $customer = $salesChannelContext->getCustomer();

        if($customer === null) {
            die('not logged in');
        }

        // Load lumise Data.
        $lumiseShopwareDesign = $this->shopwareDesignsService->load($designKey, $tmpCartId);
        $designShopwareUserId = $lumiseShopwareDesign['shopware_user_id'];

        if($designShopwareUserId !== null) {
            $designShopwareUserId = bin2hex($designShopwareUserId);

            if($designShopwareUserId !== $customer->getId()) {
                // Verhindern, dass User dieses Design für sich übernehmen.
                die('wrong user data');
            }
        } else {
            $this->shopwareDesignsService->saveCustomerIdOnTmpCart(
                $lumiseShopwareDesign,
                $customer->getId()
            );
        }

        return new RedirectResponse($this->router->generate('frontend.myfav.zweideh.account.designs'), 302);
    }
    
    /**
     * loadShopwareArticleByLumiseId
     *
     * @param  mixed $lumiseId
     * @param  mixed $salesChannelContext
     * @return mixed
     */
    /*
    private function loadShopwareArticleByLumiseId($lumiseArticleId, $salesChannelContext)
    {
        // Load shopware product id by lumise product id.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products_hashes', 'lph');
        $queryBuilder->where('lph.lumise_product_id = :lumise_product_id');
        $queryBuilder->setParameter('lumise_product_id', $lumiseArticleId);
        $results = $queryBuilder->execute()->fetchAll();

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
    */
    
    /**
     * addVioB2bLineItem
     *
     * @return void
     */
    /*
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
        $extendedData = [
            'originalProductId' => $product->getId(),
            'sizeName' => $sizeName,
            'lumiseShopwareDesignsId' => $lumiseShopwareDesignsId,
            'lumiseTmpCartId' => $lumiseTmpCartId,
            'lumiseArticleId' => $lumiseArticleId,
            'comment' => $comment
        ];

        $addParam = [
            'productId' => null,
            'customIdentifier' => $lumiseShopwareDesignsId . '-' . $sizeName,
            'extendedData' => $extendedData,
            'quantity' => $sizeQuantity
        ];
        $this->inquiryCartService->addCustomProduct($addParam, $salesChannelContext);
    }
    */
}