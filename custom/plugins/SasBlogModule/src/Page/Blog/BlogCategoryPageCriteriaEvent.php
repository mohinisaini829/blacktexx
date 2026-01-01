<?php
declare(strict_types=1);

namespace Sas\BlogModule\Page\Blog;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BlogCategoryPageCriteriaEvent extends Event implements ShopwareSalesChannelEvent
{
    protected string $categoryId;

    protected Criteria $criteria;

    protected SalesChannelContext $salesChannelContext;

    public function __construct(string $categoryId, Criteria $criteria, SalesChannelContext $context)
    {
        $this->categoryId = $categoryId;
        $this->criteria = $criteria;
        $this->salesChannelContext = $context;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
