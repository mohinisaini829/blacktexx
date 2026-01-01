<?php
declare(strict_types=1);

namespace Sas\BlogModule\Page\Blog;

use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Sas\BlogModule\Content\Blog\BlogEntriesEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Exception\PageNotFoundException;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class BlogPageLoader
{
    /**
     * @param EntityRepository<BlogEntriesCollection> $blogRepository
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        private readonly EntityRepository $blogRepository,
        private readonly SeoUrlPlaceholderHandlerInterface $seoUrlReplacer
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
    public function load(Request $request, SalesChannelContext $context): BlogPage
    {
        $articleId = $request->attributes->get('articleId');
        if (!$articleId) {
            throw RoutingException::missingRequestParameter('articleId', '/articleId');
        }

        $blogEntry = $this->loadBlogEntry($articleId, $context);
        $blogEntry->setSeoCategory($blogEntry->getBlogCategories()?->first());

        $detailCmsPage = $this->loadBlogDetailCmsPage($request, $context);

        $page = $this->genericLoader->load($request, $context);
        $page = BlogPage::createFrom($page);

        $page->setBlogEntry($blogEntry);
        $page->setCmsPage($detailCmsPage);

        $metaInformation = $page->getMetaInformation();
        if ($metaInformation instanceof MetaInformation) {
            $metaTitle = $blogEntry->getTranslation('metaTitle') ?? $blogEntry->getTitle();
            $metaDescription = $blogEntry->getTranslation('metaDescription') ?? $blogEntry->getTeaser();
            $metaAuthor = $blogEntry->getBlogAuthor() ? ($blogEntry->getBlogAuthor()->getDisplayName() ?? $blogEntry->getBlogAuthor()->getFullName()) : '';
            $metaKeywords = $blogEntry->getTranslation('metaKeywords');
            $metaInformation->setMetaKeywords($metaKeywords ?? '');
            $metaInformation->setMetaTitle($metaTitle ?? '');
            $metaInformation->setMetaDescription($metaDescription ?? '');
            $metaInformation->setAuthor($metaAuthor ?? '');
            $metaInformation->setCanonical($this->seoUrlReplacer->generate('sas.frontend.blog.detail', ['articleId' => $articleId]));
            $page->setMetaInformation($metaInformation);
        }

        $this->eventDispatcher->dispatch(new BlogPageLoadedEvent($page, $context, $request));

        return $page;
    }

    /**
     * Loads the Blog Entry for the given article id
     * It creates a criteria with the given article id
     *   then associates the author's salutation and blog categories
     * It dispatches an event to allow other extensions to modify the criteria
     * It gets and returns the Blog Entry's instance for the given criteria
     *
     * @throws PageNotFoundException
     */
    private function loadBlogEntry(string $articleId, SalesChannelContext $context): BlogEntriesEntity
    {
        $criteria = (new Criteria([$articleId]))
            ->addAssociation('blogAuthor.salutation')
            ->addAssociation('media')
            ->addAssociation('blogCategories');

        $this->eventDispatcher->dispatch(new BlogPageCriteriaEvent($articleId, $criteria, $context));

        $blogEntry = $this->blogRepository
            ->search($criteria, $context->getContext())
            ->first();

        if (!$blogEntry instanceof BlogEntriesEntity) {
            throw new PageNotFoundException($articleId);
        }

        return $blogEntry;
    }

    /**
     * Loads the CMS Page for the blog detail page
     * It gets the CMS Page's id from the plugin configuration
     * It gets and returns the CMS Page's instance for the given id
     *
     * @throws PageNotFoundException
     */
    private function loadBlogDetailCmsPage(Request $request, SalesChannelContext $context): CmsPageEntity
    {
        $detailCmsPageId = $this->systemConfigService->getString('SasBlogModule.config.cmsBlogDetailPage');
        if (!$detailCmsPageId) {
            throw SystemConfigException::configurationNotFound('SasBlogModule');
        }

        $detailCmsPage = $this->cmsPageLoader->load($request, new Criteria([$detailCmsPageId]), $context)->first();
        if (!$detailCmsPage instanceof CmsPageEntity) {
            throw new PageNotFoundException($detailCmsPageId);
        }

        return $detailCmsPage;
    }
}
