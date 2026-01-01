<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\InquiryFinish;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class InquiryFinishPageLoadedEvent extends PageLoadedEvent
{

    /**
     * @var InquiryFinishPage
     */
    protected InquiryFinishPage $page;

    public function __construct(InquiryFinishPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    /**
     * @inheritDoc
     */
    public function getPage()
    {
        return $this->page;
    }
}
