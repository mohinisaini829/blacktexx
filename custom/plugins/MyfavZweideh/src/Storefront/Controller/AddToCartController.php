<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class AddToCartController extends StorefrontController {
    private SalesChannelContext $salesChannelContext;
    
    public function __construct(
    ) {
    }

    #[Route(
        path: '/myfavDesigner/add',
        name: 'frontend.zweideh.myfav.designer.add',
        methods: ['GET']
    )]
	public function add(Request $request, SalesChannelContext $context): RedirectResponse {
        echo '{ "status": "success" }';
        die;
    }

    
    #[Route(
        path: '/myfavDesigner/login-status',
        name: 'frontend.zweideh.myfav.designer.login.status',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
    public function loginStatus(SalesChannelContext $context): JsonResponse
    {
        // Check if the user is logged in.
		$customer = $context->getCustomer();
		
		if(NULL === $customer) {
			// Return: "not logged in" status.
			$result = [
				'loginStatus' => 'not-logged-in',
			];
			return new JsonResponse($result);
		}

        // Return: "logged in" status.
        $result = [
            'loginStatus' => 'logged-in',
        ];
        return new JsonResponse($result);
    }
}