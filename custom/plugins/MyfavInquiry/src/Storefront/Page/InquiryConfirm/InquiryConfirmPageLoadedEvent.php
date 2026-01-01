<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\InquiryConfirm;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class InquiryConfirmPageLoadedEvent extends PageLoadedEvent
{

    /**
     * @var InquiryConfirmPage
     */
    protected InquiryConfirmPage $page;

    public function __construct(InquiryConfirmPage $page, SalesChannelContext $salesChannelContext, Request $request)
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
