<?php declare(strict_types=1);

namespace Biloba\ArticleVariantOrderMatrix\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoader;
use Shopware\Storefront\Page\Product\Configurator\ProductPageConfiguratorLoader;
use Biloba\ArticleVariantOrderMatrix\Structs\StoreFrontPageCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Response;
// use Shopware\Storefront\Framework\Routing\StorefrontResponse; deprecated in 6.6.0

#[Route(defaults: ['_routeScope' => ['storefront']])]
class ArticleVariantOrderMatrixController extends StorefrontController
{
    /**
     * @var ProductPageLoader
     */
    private $minimalQuickViewPageLoader;

    /**
     * @var ProductPageConfiguratorLoader
     */
    protected $productPageConfiguratorLoader;

    private $productRepository;
    private $productRepositorySalesChannel;
    private $systemConfigService;

    public function __construct(
            MinimalQuickViewPageLoader $minimalQuickViewPageLoader,
            ProductPageConfiguratorLoader $productPageConfiguratorLoader,
            $productRepository,
            $productRepositorySalesChannel,
            SystemConfigService $systemConfigService) {
        $this->minimalQuickViewPageLoader = $minimalQuickViewPageLoader;
        $this->productPageConfiguratorLoader = $productPageConfiguratorLoader;
        $this->productRepository = $productRepository;
        $this->productRepositorySalesChannel = $productRepositorySalesChannel;
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(path: '/biloba/article-variant-order-matrix/quickorder/{productId}', name: 'store-api.biloba.article-variant-order-matrix.quickorder', defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function getQuickorder(SalesChannelContext $context, Request $request): Response
    {
        if($request->get('optionChangeId')) {
            
            // first parameter group id, second parameter option id
            $optionIds = explode('-', $request->get('optionChangeId'));

            // get variant with option
            $criteria = new Criteria();
            $criteria->addFilter(
                new EqualsFilter('parentId', $request->attributes->get('productId'))
            );
            $criteria->addFilter(
                new ContainsFilter('optionIds', $optionIds[1])
            );

            $entities = $this->productRepositorySalesChannel->search(
                $criteria,
                $context->getSalesChannelContext()
            )->first();

            $request->attributes->set('productId', $entities->id);
        }

        $page = $this->minimalQuickViewPageLoader->load($request, $context);
        $configuratorSettings = $this->productPageConfiguratorLoader->load($page->getProduct(), $context);
        
        $extensionData = $page->getExtension('bilobaArticleVariantOrderMatrix');
        if(!$extensionData) {
            $extensionData = new StoreFrontPageCollection();
        }
        $extensionData->setValue('configuratorSettings', $configuratorSettings);
        $page->addExtension('bilobaArticleVariantOrderMatrix', $extensionData);

        return $this->renderStorefront('@Storefront/storefront/biloba/article-variant-order-matrix/quickview.html.twig', ['page' => $page]);
    }

    #[Route(path: '/biloba/article-variant-order-matrix/listingInline/{productId}', name: 'frontend.biloba.article-variant-order-matrix.listing-inline', defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function getVariantMatrix(SalesChannelContext $context, Request $request): Response
    {
        $extensionDataListing = $context->getExtension('bilobaArticleVariantOrderMatrix');
        if(!$extensionDataListing) {
            $extensionDataListing = new StoreFrontPageCollection();
        }
        $extensionDataListing->setValue('listingLayout', $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixListingEnabled', $context->getSalesChannel()->getId()));
        $context->addExtension('bilobaArticleVariantOrderMatrix', $extensionDataListing);

        $page = $this->minimalQuickViewPageLoader->load($request, $context);
        $configuratorSettings = $this->productPageConfiguratorLoader->load($page->getProduct(), $context);
        
        $extensionData = $page->getExtension('bilobaArticleVariantOrderMatrix');
        if(!$extensionData) {
            $extensionData = new StoreFrontPageCollection();
        }
        $extensionData->setValue('configuratorSettings', $configuratorSettings);
        $page->addExtension('bilobaArticleVariantOrderMatrix', $extensionData);

        $bilobaVariantMatrixDisableInline = false;
        if(count($extensionData->elements['configuratorSettings']->getElements()) < 1 || count($extensionData->elements['configuratorSettings']->getElements()) > 2) {
            $bilobaVariantMatrixDisableInline = true;
        }
        
        return $this->renderStorefront('@Storefront/storefront/biloba/article-variant-order-matrix/article-variant-order-matrix.html.twig', ['page' => $page, 'bilobaVariantMatrixListingInline' => true, 'bilobaVariantMatrixDisableInline' => $bilobaVariantMatrixDisableInline]);
    }

    #[Route(path: '/biloba/article-variant-order-matrix/loadImageGallery/{variantId}', name: 'frontend.biloba.article-variant-order-matrix.load-imagegallery', defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function loadImageGallery(SalesChannelContext $context, Request $request): Response
    {
        $variantId = $request->attributes->get('variantId');

        // load variant with images
        $criteria = new Criteria([
            $variantId
        ]);
        $criteria->addAssociation('media');
        $criteria->addAssociation('media.media');

        $entities = $this->productRepository->search(
            $criteria,
            $context->getContext()
        )->first();

        $variantMatrixProduct = $entities;
        //dd($variantMatrixProduct);
        return $this->renderStorefront('@Storefront/storefront/biloba/article-variant-order-matrix/image-gallery.html.twig', ['variantMatrixProduct' => $variantMatrixProduct]);
    }

    #[Route(path: '/biloba/article-variant-order-matrix/loadImageListing/{variantId}', name: 'frontend.biloba.article-variant-order-matrix.load-imagelisting', defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function loadImageListing(SalesChannelContext $context, Request $request): Response
    {
        $variantId = $request->attributes->get('variantId');

        // load variant with images
        $criteria = new Criteria([
            $variantId
        ]);
        $criteria->addAssociation('media');

        $entities = $this->productRepository->search(
            $criteria,
            $context->getContext()
        )->first();

        $variantMatrixProduct = $entities;
        
        $media = null;
        if($variantMatrixProduct->cover && $variantMatrixProduct->cover->media) {
            $media = $variantMatrixProduct->cover->media;
        }
        else {
            $media = $variantMatrixProduct->media->first()->media;
        }

        return $this->renderStorefront('@Storefront/storefront/biloba/article-variant-order-matrix/image-listing.html.twig', ['media' => $media]);
    }
}