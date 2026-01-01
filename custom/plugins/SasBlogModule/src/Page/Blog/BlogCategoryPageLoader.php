<?php
declare(strict_types=1);

namespace Sas\BlogModule\Page\Blog;

use Sas\BlogModule\Content\BlogCategory\BlogCategoryCollection;
use Sas\BlogModule\Content\BlogCategory\BlogCategoryEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Exception\PageNotFoundException;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class BlogCategoryPageLoader
{
    /**
     * @param EntityRepository<BlogCategoryCollection> $blogCategoryRepository
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $blogCategoryRepository,
        private readonly SalesChannelCmsPageLoaderInterface $cmsPageLoader,
    ) {
    }

    /**
     * Loads the blog page data
     * It gets article id from request
     * It get Storefront Page's instance for given request
     * It assigns metadata to page instance
     * It dispatches an event to allow other extensions to modify the page instance
     *
     * @throws PageNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $context): BlogCategoryPage
    {
        $blogListingCmsPage = $this->loadBlogListingPage($request, $context);
        $category = $this->loadCategory($request, $context);
        $page = $this->genericLoader->load($request, $context);
        $page = BlogCategoryPage::createFrom($page);

        $page->setCmsPage($blogListingCmsPage);
        $page->setBlogCategory($category);

        $this->eventDispatcher->dispatch(new BlogCategoryPageLoadedEvent($page, $context, $request));

        return $page;
    }

    private function loadCategory(Request $request, SalesChannelContext $context): ?BlogCategoryEntity
    {
        $categoryId = $request->attributes->get('categoryId');
        $criteria = new Criteria([$categoryId]);

        $this->eventDispatcher->dispatch(new BlogCategoryPageCriteriaEvent($categoryId, $criteria, $context));

        $category = $this->blogCategoryRepository->search($criteria, $context->getContext())->first();
        if (!$category instanceof BlogCategoryEntity) {
            return null;
        }

        return $category;
    }

    /**
     * Loads the CMS Page for the blog detail page
     * It gets the CMS Page's id from the plugin configuration
     * It gets and returns the CMS Page's instance for the given id
     *
     * @throws PageNotFoundException
     */
    private function loadBlogListingPage(Request $request, SalesChannelContext $context): CmsPageEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('categories');
        $criteria->getAssociation('categories')->setLimit(1);
        $criteria->addFilter(new EqualsFilter('type', 'page'));
        $criteria->addFilter(new EqualsFilter('name', 'Blog Listing'));
        $criteria->addFilter(new EqualsFilter('sections.blocks.type', 'blog-listing'));

        $blogListingCmsPage = $this->cmsPageLoader->load($request, $criteria, $context)->first();
        if (!$blogListingCmsPage instanceof CmsPageEntity) {
            throw new PageNotFoundException('Blog Listing');
        }

        return $blogListingCmsPage;
    }
}
