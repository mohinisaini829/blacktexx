<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\DataResolver;

use Sas\BlogModule\Core\Content\Cms\BlogAssignment\AbstractProductBlogAssignmentRoute;
use Sas\BlogModule\Core\Content\Cms\SalesChannel\Struct\BlogAssignmentStruct;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;

#[Package('discovery')]
class BlogAssignmentCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractProductBlogAssignmentRoute $blogAssignmentRoute)
    {
    }

    public function getType(): string
    {
        return 'blog-assignment';
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $context = $resolverContext->getSalesChannelContext();
        $struct = new BlogAssignmentStruct();
        $slot->setData($struct);

        $productConfig = $config->get('product');

        if ($productConfig === null || $productConfig->getValue() === null) {
            return;
        }

        $product = null;

        if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $product = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getStringValue());
        }

        if ($productConfig->isStatic()) {
            $product = $this->getSlotProduct($slot, $result, $productConfig->getStringValue());
        }

        if ($product === null) {
            return;
        }

        $struct->setProduct($product);
        $blogs = $this->blogAssignmentRoute->load($product->getId(), new Request(), $context, new Criteria())->getResult();
        if ($blogs->count()) {
            $struct->setBlogEntries($blogs);
        }
    }
}
