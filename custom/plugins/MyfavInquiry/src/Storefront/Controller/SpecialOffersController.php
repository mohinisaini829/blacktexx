<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Myfav\Inquiry\Storefront\Page\SpecialOffers\SpecialOffersPageLoader;

use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Twig\Environment;
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class SpecialOffersController extends StorefrontController
{
    #[Route('/myfav/special-offers/confirm', name: 'frontend.myfav.special-offers.confirm', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    #[StorefrontRoute]
    public function confirm(SpecialOffersPageLoader $pageLoader, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $page = $pageLoader->load($request, $salesChannelContext);
        return $this->renderStorefront('@MyfavInquiry/storefront/myfav-inquiry/page/special-offers/index.html.twig', [
            'page' => $page
        ]);
    }
}
