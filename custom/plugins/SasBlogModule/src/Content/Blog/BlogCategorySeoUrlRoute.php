<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Sas\BlogModule\Content\BlogCategory\BlogCategoryDefinition;
use Sas\BlogModule\Content\BlogCategory\BlogCategoryEntity;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Shopware\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class BlogCategorySeoUrlRoute implements SeoUrlRouteInterface
{
    final public const ROUTE_NAME = 'sas.frontend.blog.category.detail';
    final public const DEFAULT_TEMPLATE = 'blog-category/{% for part in category.seoBreadcrumb %}{{ part }}/{% endfor %}';

    /**
     * @internal
     */
    public function __construct(
        private readonly BlogCategoryDefinition $categoryDefinition,
        private readonly BlogCategoryBreadcrumbBuilder $breadcrumbBuilder
    ) {
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return new SeoUrlRouteConfig(
            $this->categoryDefinition,
            self::ROUTE_NAME,
            self::DEFAULT_TEMPLATE,
            true
        );
    }

    public function prepareCriteria(Criteria $criteria, SalesChannelEntity $salesChannel): void
    {
    }

    public function getMapping(Entity $category, ?SalesChannelEntity $salesChannel): SeoUrlMapping
    {
        if (!$category instanceof BlogCategoryEntity) {
            throw new \InvalidArgumentException('Expected CategoryEntity');
        }

        $breadcrumbs = $this->breadcrumbBuilder->build($category);
        $categoryJson = $category->jsonSerialize();
        $categoryJson['seoBreadcrumb'] = $breadcrumbs;

        return new SeoUrlMapping(
            $category,
            ['categoryId' => $category->getId()],
            [
                'category' => $categoryJson,
            ],
            null
        );
    }
}
