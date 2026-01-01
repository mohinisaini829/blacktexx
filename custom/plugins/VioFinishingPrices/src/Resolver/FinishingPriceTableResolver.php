<?php
declare(strict_types=1);

namespace Vio\FinishingPrices\Resolver;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use  Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class FinishingPriceTableResolver extends AbstractProductDetailCmsElementResolver
{
    protected EntityRepository $finisingPriceTableRepository;

    public function __construct(
        EntityRepository $finisingPriceTableRepository
    )
    {
        $this->finisingPriceTableRepository = $finisingPriceTableRepository;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $criteria = (new Criteria())
            ->addAssociation('translations')
            ->addSorting(new FieldSorting('position'))
        ;
        $finishingPriceTables = $this->finisingPriceTableRepository
            ->search($criteria, $resolverContext->getSalesChannelContext()->getContext())
            ->getEntities();
        $slot->setData($finishingPriceTables);
    }

    public function getType(): string
    {
        return 'finishing-price-table';
    }
}
