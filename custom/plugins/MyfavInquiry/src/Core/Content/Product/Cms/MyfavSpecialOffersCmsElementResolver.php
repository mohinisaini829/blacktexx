<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Core\Content\Product\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Cms\ProductSliderCmsElementResolver;
use Shopware\Core\Content\Product\Cms\SalesChannel\Listing\ProcessorInterface;

class MyfavSpecialOffersCmsElementResolver extends ProductSliderCmsElementResolver
{
    public function __construct(iterable $processors)
    {
        // Pass the list of processors to the parent constructor
        parent::__construct($processors);
    }

    public function getType(): string
    {
        return 'myfav-special-offers';
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        parent::enrich($slot, $resolverContext, $result);

        $config = $slot->getFieldConfig();
        $config->set('boxLayout', new FieldConfig(
            'boxLayout',
            'static',
            'myfav-special-offers'
        ));
    }
}
