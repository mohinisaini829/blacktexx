<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\SpecialOffers;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent as PageLoadedEventAlias;
use Symfony\Component\HttpFoundation\Request;

class SpecialOffersPageLoadedEvent extends PageLoadedEventAlias
{

    /**
     * @var SpecialOffersPage
     */
    protected SpecialOffersPage $page;

    public function __construct(SpecialOffersPage $page, SalesChannelContext $salesChannelContext, Request $request)
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
