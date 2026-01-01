<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
 
/**
 * 
 * This controller gets additional information about an article an article.
 * It is meant, to be used by lumise in ajax requests.
 * 
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class ArticleDataController extends StorefrontController {
    private EntityRepositoryInterface $productRepository;
    
    public function __construct(
        EntityRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    #[Route(
        path: '/lumiseArticleData/fetch',
        name: 'frontend.zweideh.lumise.article.data.fetch',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
	public function fetch(Request $request, SalesChannelContext $salesChannelContext): JsonResponse {
        $lumiseArticleId = $request->query->get('lumise_article_id');

        // Load Shopware Article by Custom Field?!?
        $criteria = new Criteria();
        $criteria->addAssociation('customFields')
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media');
        $criteria->addFilter(new EqualsFilter('customFields.lumis_designer_article_id', $lumiseArticleId));
        
        $products = $this->productRepository->search(
            $criteria,
            $salesChannelContext->getContext()
        );

        $tmpProduct = $products->first();

        if(null === $tmpProduct) {
            throw new \Exception('Product with custom field value lumis_designer_article_id = ' . htmlspecialchars($lumiseArticleId) . 'not found');
        }

        $customFields = $tmpProduct->getCustomFields();

        if(!isset($customFields['lumis_designer_available_request_sizes'])) {
            throw new \Exception('Custom field lumis_designer_available_request_sizes not found');
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => $customFields['lumis_designer_available_request_sizes']
        ]);
    }
}