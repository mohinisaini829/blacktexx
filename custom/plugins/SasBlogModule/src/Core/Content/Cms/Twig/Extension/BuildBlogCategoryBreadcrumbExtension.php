<?php
declare(strict_types=1);

namespace Sas\BlogModule\Core\Content\Cms\Twig\Extension;

use Sas\BlogModule\Content\Blog\BlogCategoryBreadcrumbBuilder;
use Sas\BlogModule\Content\BlogCategory\BlogCategoryEntity;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BuildBlogCategoryBreadcrumbExtension extends AbstractExtension
{
    /**
     * @internal
     *
     * @param EntityRepository<CategoryCollection> $categoryRepository
     */
    public function __construct(
        private readonly BlogCategoryBreadcrumbBuilder $categoryBreadcrumbBuilder,
        private readonly EntityRepository $categoryRepository,
        private readonly RoutingExtension $routingExtension,
        private readonly SeoUrlPlaceholderHandlerInterface $seoUrlReplacer
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sas_blog_category_breadcrumb_full', $this->getFullBreadcrumb(...), ['needs_context' => true]),
            new TwigFunction('sas_blog_category_url', $this->getCategoryUrl(...), ['needs_context' => false, 'is_safe_callback' => $this->routingExtension->isUrlGenerationSafe(...)]),
            new TwigFunction('sas_blog_category_linknewtab', $this->isLinkNewTab(...)),
        ];
    }

    /**
     * @param array<string, mixed> $twigContext
     *
     * @return array<string, CategoryEntity>
     */
    public function getFullBreadcrumb(array $twigContext, BlogCategoryEntity $category, Context $context): array
    {
        $salesChannel = null;
        if (\array_key_exists('context', $twigContext) && $twigContext['context'] instanceof SalesChannelContext) {
            $salesChannel = $twigContext['context']->getSalesChannel();
        }

        $seoBreadcrumb = $this->categoryBreadcrumbBuilder->build($category);

        if ($seoBreadcrumb === null) {
            return [];
        }

        /** @var list<string> $categoryIds */
        $categoryIds = array_keys($seoBreadcrumb);
        if (empty($categoryIds)) {
            return [];
        }

        $criteria = new Criteria($categoryIds);
        $criteria->setTitle('blog-category-breadcrumb-extension');
        $categories = $this->categoryRepository->search($criteria, $context)->getEntities();

        $breadcrumb = [];
        foreach ($categoryIds as $categoryId) {
            if ($categories->get($categoryId) === null) {
                continue;
            }

            $breadcrumb[$categoryId] = $categories->get($categoryId);
        }

        return $breadcrumb;
    }

    public function getCategoryUrl(BlogCategoryEntity $category): ?string
    {
        return $this->seoUrlReplacer->generate('sas.frontend.blog.category.detail', ['categoryId' => $category->getId()]);
    }

    public function isLinkNewTab(BlogCategoryEntity $categoryEntity): bool
    {
        return false;
    }
}
