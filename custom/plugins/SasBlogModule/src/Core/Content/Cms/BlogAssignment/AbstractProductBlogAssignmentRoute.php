<?php
declare(strict_types=1);

namespace Sas\BlogModule\Core\Content\Cms\BlogAssignment;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractProductBlogAssignmentRoute
{
    abstract public function getDecorated(): AbstractProductBlogAssignmentRoute;

    abstract public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductBlogAssignmentRouteResponse;
}
