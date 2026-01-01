<?php
declare(strict_types=1);

namespace Sas\BlogModule\Subscriber;

use Sas\BlogModule\Content\Blog\BlogCategorySeoUrlRoute;
use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Sas\BlogModule\Content\Blog\BlogSeoUrlRoute;
use Sas\BlogModule\Content\Blog\DataResolver\BlogCmsElementResolver;
use Sas\BlogModule\Content\BlogCategory\BlogCategoryCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\SalesChannel\CategoryRoute;
use Shopware\Core\Content\Cms\CmsPageEvents;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Shopware\Core\Content\Seo\SeoUrlUpdater;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * After you change the SEO Template within the SEO settings, we need to re-generate all existing URLs.
 * All old URL's should match the new saved SEO Template pattern.
 */
class BlogCacheInvalidSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<CategoryCollection>    $categoryRepository
     * @param EntityRepository<BlogEntriesCollection> $blogRepository
     * @param EntityRepository<ProductCollection>     $productRepository
     */
    public function __construct(
        private readonly SeoUrlUpdater $seoUrlUpdater,
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $blogRepository,
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $productRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CmsPageEvents::PAGE_WRITTEN_EVENT => [
                ['onUpdateSeoUrlCmsPage', 10],
                ['onUpdateInvalidateCacheCmsPage', 11],
                ['invalidBlogDetailCmsPage', 12],
            ],
            'sas_blog_entries.written' => [
                ['onUpdateSeoUrl', 10],
                ['onUpdateInvalidateCache', 11],
            ],
            'sas_blog_entries.deleted' => [
                ['onDeleteSeoUrl', 10],
                ['onDeleteInvalidateCache', 11],
            ],
            'sas_blog_category.written' => [
                ['onUpdateCategorySeoUrl', 10],
                ['onUpdateInvalidateCategoryCache', 11],
            ],
            'sas_blog_category.deleted' => [
                ['onDeleteCategorySeoUrl', 10],
                ['onDeleteCategoryInvalidateCache', 11],
            ],
            'sas_blog_author.written' => [
                ['onUpdateAuthor', 10],
            ],
            'sas_blog_author.deleted' => [
                ['onDeleteAuthor', 10],
            ],
        ];
    }

    public function onUpdateAuthor(EntityWrittenEvent $event): void
    {
        $blogIds = $this->blogRepository->searchIds(new Criteria(), $event->getContext())->getIds();
        $this->invalidateCache();
    }

    public function onDeleteAuthor(EntityDeletedEvent $event): void
    {
        $blogIds = $this->blogRepository->searchIds(new Criteria(), $event->getContext())->getIds();
        $this->invalidateCache();
    }

    public function invalidBlogDetailCmsPage(EntityWrittenEvent $event): void
    {
        $cmsPageIds = $event->getIds();
        $cmsBlogDetailPageId = $this->systemConfigService->get('SasBlogModule.config.cmsBlogDetailPage');
        if (!\is_string($cmsBlogDetailPageId) || !\in_array($cmsBlogDetailPageId, $cmsPageIds, true)) {
            return;
        }

        $this->cacheInvalidator->invalidate(
            array_values(array_map([EntityCacheKeyGenerator::class, 'buildCmsTag'], [$cmsBlogDetailPageId])),
            true
        );

        $blogIds = $this->blogRepository->searchIds(new Criteria(), $event->getContext())->getIds();
        $this->invalidateCache();
    }

    public function onUpdateSeoUrlCmsPage(EntityWrittenEvent $event): void
    {
        /* @var array<string> $blogIds */
        $blogIds = $this->getBlogIds($event);
        if (empty($blogIds)) {
            return;
        }

        // @phpstan-ignore-next-line
        $this->seoUrlUpdater->update(BlogSeoUrlRoute::ROUTE_NAME, $blogIds);
    }

    public function onUpdateInvalidateCacheCmsPage(EntityWrittenEvent $event): void
    {
        /** @var array<string> $blogIds */
        $blogIds = $this->getBlogIds($event);
        if (empty($blogIds)) {
            return;
        }

        $this->invalidateCache();

        $this->invalidateCacheCategory($event->getContext());

        $this->invalidateCacheProductBlogAssignment($blogIds, $event->getContext());
    }

    public function onInvalidateCategoryHasBlogs(EntityWrittenEvent $event): void
    {
        $categoryIds = $this->getCategoriesBelongToBlogs($event->getIds(), $event->getContext());
        if (empty($categoryIds)) {
            return;
        }
    }

    public function onInvalidateCategoryHasCmsPageBlogs(EntityWrittenEvent $event): void
    {
        /** @var array<string> $blogIds */
        $blogIds = $this->getBlogIds($event);
        $categoryIds = $this->getCategoriesBelongToBlogs($blogIds, $event->getContext());
        if (empty($categoryIds)) {
            return;
        }
    }

    /**
     * When a blog article created or updated we will generate the SeoUrl for it
     */
    public function onUpdateSeoUrl(EntityWrittenEvent $event): void
    {
        $this->seoUrlUpdater->update(BlogSeoUrlRoute::ROUTE_NAME, $event->getIds());
    }

    /**
     * When a blog article created or updated we will generate the SeoUrl for it
     */
    public function onUpdateCategorySeoUrl(EntityWrittenEvent $event): void
    {
        $this->seoUrlUpdater->update(BlogCategorySeoUrlRoute::ROUTE_NAME, $event->getIds());
    }

    /**
     * When a blog article deleted we will mark as deleted the SeoUrl
     */
    public function onDeleteSeoUrl(EntityDeletedEvent $event): void
    {
        $this->seoUrlUpdater->update(BlogSeoUrlRoute::ROUTE_NAME, $event->getIds());
    }

    public function onDeleteCategorySeoUrl(EntityDeletedEvent $event): void
    {
        $this->seoUrlUpdater->update(BlogCategorySeoUrlRoute::ROUTE_NAME, $event->getIds());
    }

    /**
     * Invalidate blog cms cache when create or update
     */
    public function onUpdateInvalidateCache(EntityWrittenEvent $event): void
    {
        $this->invalidateCache();

        $this->invalidateCacheCategory($event->getContext());

        $this->invalidateCacheProductBlogAssignment($event->getIds(), $event->getContext());
    }

    public function onUpdateInvalidateCategoryCache(EntityWrittenEvent $event): void
    {
        $blogIds = $this->getBlogBelongToCategoryIds($event);

        $this->invalidateCache();
    }

    /**
     * Invalidate blog cms cache when delete article
     */
    public function onDeleteInvalidateCache(EntityDeletedEvent $event): void
    {
        $this->invalidateCache();

        $this->invalidateCacheCategory($event->getContext());

        $this->invalidateCacheProductBlogAssignment($event->getIds(), $event->getContext());
    }

    /**
     * Invalidate blog cms cache when delete article
     */
    public function onDeleteCategoryInvalidateCache(EntityDeletedEvent $event): void
    {
        $blogIds = $this->getBlogBelongToCategoryIds($event);

        $this->invalidateCache();
    }

    /**
     * Invalidate product cache when blog article assigned to product
     *
     * @param array<string> $blogIds
     */
    private function invalidateCacheProductBlogAssignment(array $blogIds, Context $context): void
    {
        /** @var array<string> $productIds */
        $productIds = $this->getBlogProductIds($blogIds, $context);
        $this->cacheInvalidator->invalidate(
            array_map(ProductDetailRoute::buildName(...), $productIds)
        );
    }

    /**
     * Get all product ids that are assigned to the blogs
     *
     * @param array<string> $blogIds
     *
     * @return array<int, array<string, string>|string>
     */
    private function getBlogProductIds(array $blogIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('assignedBlogs.id', $blogIds));

        return $this->productRepository->searchIds($criteria, $context)->getIds();
    }

    /**
     * Invalidate blog category cache
     */
    private function invalidateCacheCategory(Context $context): void
    {
        $catIds = $this->getBlogCategoryIds($context);
        $catIds = array_map([CategoryRoute::class, 'buildName'], $catIds);
        $catIds = array_values($catIds);

        // invalidates the category route cache when a category changed
        $this->cacheInvalidator->invalidate($catIds);
    }

    /**
     * Get all blog category ids that have a cms page with blog listing
     *
     * @return array<string>
     */
    private function getBlogCategoryIds(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('cmsPage.sections.blocks.type', 'blog-listing'));
        $criteria->addAssociation('cmsPage.sections.blocks');

        return $this->categoryRepository->search($criteria, $context)->getIds();
    }

    /**
     * Invalidate cache
     * This will invalidate the blog cms cache, product suggest route and product search route
     */
    private function invalidateCache(): void
    {
        $this->cacheInvalidator->invalidate(
            [
                BlogCmsElementResolver::buildName(),
            ],
            true
        );

        $this->cacheInvalidator->invalidate([
            'product-suggest-route',
            'product-search-route',
        ], true);

        $cmsBlogDetailPageId = $this->systemConfigService->get('SasBlogModule.config.cmsBlogDetailPage');
        if (!\is_string($cmsBlogDetailPageId)) {
            return;
        }

        $this->cacheInvalidator->invalidate(
            array_values(array_map([EntityCacheKeyGenerator::class, 'buildCmsTag'], [$cmsBlogDetailPageId])),
            true
        );
    }

    /**
     * Get all blog ids that have a cms page with blog listing
     *
     * @return array<int, array<string, string>|string>
     */
    private function getBlogIds(EntityWrittenEvent $event): array
    {
        return $this->blogRepository->searchIds(
            (new Criteria())->addFilter(new EqualsAnyFilter('cmsPageId', $event->getIds())),
            $event->getContext()
        )->getIds();
    }

    /**
     * @return array<int, array<string, string>|string>
     */
    private function getBlogBelongToCategoryIds(EntityWrittenEvent $event): array
    {
        $ids = $event->getIds();

        return $this->blogRepository->searchIds(
            (new Criteria())
                ->addFilter(
                    new OrFilter(
                        [
                            ...array_map(fn ($id) => new ContainsFilter('blogCategories.path', $id), $ids),
                            new EqualsAnyFilter('blogCategories.id', $ids),
                        ]
                    )
                ),
            $event->getContext()
        )->getIds();
    }

    /**
     * Get all categories that belong to the blogs
     *
     * @param array<string> $blogIds
     *
     * @return array<string>
     */
    private function getCategoriesBelongToBlogs(array $blogIds, Context $context): array
    {
        $blogs = $this->blogRepository->search(
            (new Criteria())
                ->addAssociation('blogCategories')
                ->addFilter(new EqualsAnyFilter('id', $blogIds)),
            $context
        )->getEntities();

        if (!$blogs instanceof BlogEntriesCollection) {
            return [];
        }

        $categoryIds = [];
        foreach ($blogs as $blog) {
            $categories = $blog->getBlogCategories();
            if (!$categories instanceof BlogCategoryCollection) {
                continue;
            }

            foreach ($categories as $category) {
                $categoryIds = [
                    ...$categoryIds,
                    ...explode('|', $category->getPath() ?? ''),
                    $category->getId(),
                ];
            }
        }

        return $categoryIds;
    }
}
