<?php

declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Myfav\Inquiry\Services\InquiryCartService;
use Myfav\Inquiry\Services\InquiryService;
use Myfav\Inquiry\Storefront\Page\InquiryConfirm\InquiryConfirmPageLoader;
use Myfav\Inquiry\Storefront\Page\InquiryFinish\InquiryFinishPageLoader;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]

class AccountInquiryController extends StorefrontController
{

    private InquiryCartService $inquiryCartService;
    private InquiryService $inquiryService;

    public function __construct(
        InquiryCartService $inquiryCartService,
        InquiryService $inquiryService
    )
    {
        $this->inquiryCartService = $inquiryCartService;
        $this->inquiryService = $inquiryService;
    }
    #[Route('/my-inquiry/list', name: 'frontend.myfav.account.inquiry.list', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    #[StorefrontRoute]
    
    public function list(RequestDataBag $requestDataBag, SalesChannelContext $salesChannelContext): Response
    {
        // Verify customer login.
        $customer = $salesChannelContext->getCustomer();

        if($customer === null) {
            return $this->forwardToRoute('frontend.account.profile.page');
        }

        // Fetch designs from database;
        $index = 0;
        $limit = 10;
        $inquiries = $this->inquiryService->loadInquiriesByCustomerId($index, $limit, $customer->getId(), $salesChannelContext);

        // Render page.
        return $this->renderStorefront('@AccountInquiries/storefront/page/account-inquiries/index.html.twig', [
            'inquiries' => $inquiries
        ]);
    }
}
