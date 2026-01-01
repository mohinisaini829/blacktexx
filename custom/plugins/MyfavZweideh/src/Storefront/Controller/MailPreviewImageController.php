<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Zweideh\MyfavZweideh;
use Myfav\Zweideh\Services\TmpCartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Symfony\Component\Routing\RouterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class MailPreviewImageController extends StorefrontController {
    private TmpCartService $tmpCartService;
    private SystemConfigService $systemConfigService;
        
    /**
     * __construct
     */
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $productRepository,
        EventDispatcherInterface $eventDispatcher,
        AbstractProductDetailRoute $productDetailRoute,
        TmpCartService $tmpCartService,
        RouterInterface $router,
        SystemConfigService $systemConfigService
    ) {
        $this->connection = $connection;
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->productDetailRoute = $productDetailRoute;
        $this->tmpCartService = $tmpCartService;
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/myfavDesigner/mail/preview/image',
        name: 'frontend.myfav.zweideh.mail.preview.image',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function getTmpCartImge(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $key = $request->query->get('key');
        $tmp_cart_id = $request->query->get('tmp_cart_id');

        // Load lumis Data.
        $tmpCart = $this->tmpCartService->load($key, $tmp_cart_id);

        if (null === $tmpCart) {
            throw new \Exception('Tmp Designer cart not found');
        }

        $lumiseImage = $this->getLumiseImage($salesChannelContext, $tmpCart);
        $image = imagecreatefrompng($lumiseImage);
        $imgResized = imagescale($image , 75, -1); // Second parameter on -1 will set height automatically

        ob_start();
        imagejpeg($imgResized, null, 95);
        $data = ob_get_clean();

        header("Content-Type: image/png");
        echo $data;
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

        $path = $this->getLumisePath($salesChannelContext) . 'data/tmpCartUploads/' . $year . '/' . $month . '/';
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