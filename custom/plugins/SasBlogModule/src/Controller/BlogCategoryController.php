<?php
declare(strict_types=1);

namespace Sas\BlogModule\Controller;

use Sas\BlogModule\Page\Blog\BlogCategoryPageLoader;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Blog Listing page controller
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class BlogCategoryController extends StorefrontController
{
    public function __construct(private readonly BlogCategoryPageLoader $blogPageLoader)
    {
    }

    #[Route('/sas_blog_category/{categoryId}', name: 'sas.frontend.blog.category.detail', methods: ['GET'])]
    public function category(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->blogPageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/storefront/page/blog-category/index.html.twig', ['page' => $page]);
    }
}
