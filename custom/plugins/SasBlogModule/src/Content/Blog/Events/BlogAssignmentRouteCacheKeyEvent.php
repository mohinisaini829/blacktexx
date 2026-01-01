<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\Events;

use Shopware\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class BlogAssignmentRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
    public function __construct(
        protected string $productId,
        array $parts,
        Request $request,
        SalesChannelContext $context,
        ?Criteria $criteria
    ) {
        parent::__construct($parts, $request, $context, $criteria);
    }

    public function getProductId(): string
    {
        return $this->productId;
    }
}
