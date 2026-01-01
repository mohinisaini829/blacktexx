<?php
declare(strict_types=1);

namespace Sas\BlogModule\Page\Blog;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class BlogCategoryPageLoadedEvent extends PageLoadedEvent
{
    protected BlogCategoryPage $page;

    public function __construct(BlogCategoryPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): BlogCategoryPage
    {
        return $this->page;
    }
}
