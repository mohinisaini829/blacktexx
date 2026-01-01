<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Zweideh\MyfavZweideh;
use Myfav\Zweideh\Services\ShopwareDesignsService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Shopware\Storefront\Controller\StorefrontController;

use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Symfony\Component\Routing\RouterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class LumiseDesignImageController extends StorefrontController {
    private $connection;
    private EntityRepositoryInterface $productRepository;
    private EventDispatcherInterface $eventDispatcher;
    private AbstractProductDetailRoute $productDetailRoute;
    private ShopwareDesignsService $shopwareDesignsService;
    private RouterInterface $router;
    private SystemConfigService $systemConfigService;
        
    /**
     * __construct
     */
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $productRepository,
        EventDispatcherInterface $eventDispatcher,
        AbstractProductDetailRoute $productDetailRoute,
        ShopwareDesignsService $shopwareDesignsService,
        RouterInterface $router,
        SystemConfigService $systemConfigService
    ) {
        $this->connection = $connection;
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->productDetailRoute = $productDetailRoute;
        $this->shopwareDesignsService = $shopwareDesignsService;
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/myfavDesigner/lumise/design/image',
        name: 'frontend.myfav.zweideh.lumise.design.image',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function lumiseDesignImage(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        //die('fsfsff');
        $key = $request->query->get('key');
        $lumise_design_id = $request->query->get('lumise_design_id');
        
        // Verify customer login.
        $customer = $salesChannelContext->getCustomer();

        if($customer === null) {
            //die('Customer not logged in');
        }

        // Load lumis Data.
        $tmpCart = $this->shopwareDesignsService->load($key, $lumise_design_id);

        if (null === $tmpCart) {
            throw new \Exception('Tmp Designer cart not found');
        }

        $lumiseImage = $this->getLumiseImage($salesChannelContext, $tmpCart);
        $fp = fopen($lumiseImage, 'rb');

        header("Content-Type: image/png");
        header("Content-Length: " . filesize($lumiseImage));

        fpassthru($fp);
        exit;
    }
    
    /**
     * getLumiseImage
     *
     * @param  SalesChannelContext $salesChannelContext
     * @param  array $lumiseData
     * @return string
     */
    private function getLumiseImage(SalesChannelContext $salesChannelContext, array $lumiseData)
    {
        $timestamp = strtotime($lumiseData['created']);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $path = $this->getLumisePath($salesChannelContext) . 'data/swCustomerDesigns/' . $year . '/' . $month . '/';
        $filename = $lumiseData['tmp_cart_id'] . '.jpg';
        $filepath = $path . $filename;

        return $filepath;
    }
    
    /**
     * getLumisePath
     *
     * @param  SalesChannelContext $salesChannelContext
     * @return string
     */
    private function getLumisePath(SalesChannelContext $salesChannelContext)
    {
        $lumisePath = $this->systemConfigService->get(
            MyfavZweideh::PLUGIN_CONFIG . 'lumisInstallPath',
            $salesChannelContext->getSalesChannelId()
        );

        return $lumisePath;
    }
}