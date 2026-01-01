<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Zweideh\Services\ShopwareDesignsService;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class AccountDesignsController extends StorefrontController
{
    private Connection $connection;
    private ShopwareDesignsService $shopwareDesignsService;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Connection $connection,
        ShopwareDesignsService $shopwareDesignsService
    ) {
        $this->connection = $connection;
        $this->shopwareDesignsService = $shopwareDesignsService;
    }
    #[Route(path: '/myfavDesigner/account/designs', name: 'frontend.myfav.zweideh.account.designs',defaults: ['loginRequired' => true], methods: ['GET'])]
    public function listing(Request $request, SalesChannelContext $salesChannelContext): Response {
        // Verify customer login.
        $customer = $salesChannelContext->getCustomer();

        if($customer === null) {
            return $this->forwardToRoute('frontend.account.profile.page');
        }

        // Fetch designs from database;
        $index = 0;
        $limit = 10;
        $designs = $this->shopwareDesignsService->loadDesignsByCustomerId($index, $limit, $customer->getId());

        // Render page.
        return $this->renderStorefront('@AccountDesigns/storefront/page/account-designs/index.html.twig', [
            'designs' => $designs
        ]);
    }
}