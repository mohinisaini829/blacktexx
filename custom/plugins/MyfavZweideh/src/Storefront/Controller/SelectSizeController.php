<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Zweideh\Services\TmpCartService;
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
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class SelectSizeController extends StorefrontController {
    private $connection;
    private EntityRepositoryInterface $productRepository;
    private EventDispatcherInterface $eventDispatcher;
    private AbstractProductDetailRoute $productDetailRoute;
    private TmpCartService $tmpCartService;
    private RouterInterface $router;
    
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $productRepository,
        EventDispatcherInterface $eventDispatcher,
        AbstractProductDetailRoute $productDetailRoute,
        TmpCartService $tmpCartService,
        RouterInterface $router
    ) {
        $this->connection = $connection;
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->productDetailRoute = $productDetailRoute;
        $this->tmpCartService = $tmpCartService;
        $this->router = $router;
    }

    
    #[Route(
        path: '/myfavDesigner/select/size',
        name: 'frontend.myfav.zweideh.select.size',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
	public function selectSize(Request $request, SalesChannelContext $salesChannelContext): Response {
        $key = $request->query->get('key');
        $tmp_cart_id = $request->query->get('tmp_cart_id');
        $product = $request->query->get('product');
        
        // Verify customer login.
        $customer = $salesChannelContext->getCustomer();

        if($customer === null) {
            $redirectParameters = json_encode([
                'key' => $key,
                'tmp_cart_id' => $tmp_cart_id
            ]);

            $url = $this->router->generate('frontend.account.login.page', [
                'redirectTo' => 'frontend.myfav.zweideh.select.size',
                'redirectParameters' => $redirectParameters
            ]);

            return new RedirectResponse($url);
        }

        // Load lumis Data.
        $tmpCart = $this->tmpCartService->load($key, $tmp_cart_id);

        if (null === $tmpCart) {
            throw new \Exception('Tmp Designer cart not found');
        }

        $this->tmpCartService->verifyCustomer($tmpCart, $customer);

        $lumiseImage = $this->getLumiseImage($tmpCart);

        // Load Shopware Article by Custom Field?!?
        $criteria = new Criteria();
        $criteria->addAssociation('customFields')
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media');
        $criteria->addFilter(new EqualsFilter('customFields.lumis_designer_article_id', $tmpCart['product']));
        
        $products = $this->productRepository->search(
            $criteria,
            $salesChannelContext->getContext()
        );

        $tmpProduct = $products->first();
        $productId = $tmpProduct->getId();

        // Load product, like it is loaded on the details page.
        $criteria = (new Criteria())
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media');

        $this->eventDispatcher->dispatch(new ProductPageCriteriaEvent($productId, $criteria, $salesChannelContext));

        $result = $this->productDetailRoute->load($productId, $request, $salesChannelContext, $criteria);
        $product = $result->getProduct();

        if ($product->getMedia()) {
            $product->getMedia()->sort(function (ProductMediaEntity $a, ProductMediaEntity $b) {
                return $a->getPosition() <=> $b->getPosition();
            });
        }

        $page['product'] = $product;
        
        return $this->renderStorefront('@MyfavZweideh/storefront/page/select-size/index.html.twig', [
            'context' => $salesChannelContext,
            'page' => $page,
            'lumiseData' => $tmpCart,
            'lumiseImage' => $lumiseImage,
            'tmp_cart_id' => $tmp_cart_id,
            'key' => $key
        ]);
        
    }

    private function getLumiseImage($lumiseData) {
        $timestamp = strtotime($lumiseData['created']);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $path = 'data/tmpCartUploads/' . $year . '/' . $month . '/';
        $filename = $lumiseData['tmp_cart_id'] . '.jpg';
        $filepath = $path . $filename;

        return $filepath;
    }
}