<?php
declare(strict_types=1);

namespace Sas\BlogModule\Core\Content\Cms\BlogAssignment;

use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Sas\BlogModule\Content\Blog\SasTagCollection;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class ProductBlogAssignmentRoute extends AbstractProductBlogAssignmentRoute
{
    /**
     * @internal
     *
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
    ) {
    }

    public function getDecorated(): AbstractProductBlogAssignmentRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/product/{productId}/blog-assignment', name: 'store-api.product.blog-assignment', defaults: ['_entity' => 'product'], methods: ['POST'])]
    public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductBlogAssignmentRouteResponse
    {
        $product = $this->loadProduct($productId, $context);
        if (!$product) {
            return new ProductBlogAssignmentRouteResponse(new BlogEntriesCollection());
        }

        $blogs = null;
        $isAssignedBlogByTag = $product?->customFields['isAssignBlogByTag'] ?? false;
        if ($isAssignedBlogByTag) {
            $tags = $product->getExtension('blogTags');
            if ($tags instanceof SasTagCollection) {
                $blogs = $tags->getBlogs();
            }
        } else {
            $blogs = $product->getExtension('assignedBlogs');
        }

        if (!$blogs instanceof BlogEntriesCollection) {
            return new ProductBlogAssignmentRouteResponse(new BlogEntriesCollection());
        }

        return new ProductBlogAssignmentRouteResponse($blogs);
    }

    private function loadProduct(string $productId, SalesChannelContext $context): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('assignedBlogs.media');
        $criteria->getAssociation('assignedBlogs')->addFilter(
            new EqualsFilter('active', true),
            new RangeFilter('publishedAt', [RangeFilter::LTE => (new \DateTime())->format(\DATE_ATOM)]),
        )->addSorting(new FieldSorting('publishedAt', FieldSorting::DESCENDING));
        $criteria->addAssociation('blogTags.blogs.media');
        $criteria->getAssociation('blogTags.blogs')
            ->addFilter(
                new EqualsFilter('active', true),
                new RangeFilter('publishedAt', [RangeFilter::LTE => (new \DateTime())->format(\DATE_ATOM)]),
            )
            ->addSorting(new FieldSorting('publishedAt', FieldSorting::DESCENDING));

        $product = $this->productRepository->search($criteria, $context->getContext())->first();

        if (!$product instanceof ProductEntity) {
            return null;
        }

        return $product;
    }
}
