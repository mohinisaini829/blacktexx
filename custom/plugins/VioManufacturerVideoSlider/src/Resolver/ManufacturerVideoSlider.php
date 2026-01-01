<?php
declare(strict_types=1);

namespace Vio\ManufacturerVideoSlider\Resolver;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Vio\ManufacturerVideoSlider\VioManufacturerVideoSlider;

class ManufacturerVideoSlider extends AbstractProductDetailCmsElementResolver
{
    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $product = $resolverContext->getEntity();
        if( $product instanceof SalesChannelProductEntity
            && $product->getManufacturer() !== null
            && $product->getManufacturer()->getCustomFields() !== null
            && array_key_exists(VioManufacturerVideoSlider::VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD, $product->getManufacturer()->getCustomFields())
        ) {
            $videoUrlText = $product->getManufacturer()->getCustomFields()[VioManufacturerVideoSlider::VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD];
            // split on new line
            if ($videoUrlText) {
                $videoUrls = preg_split("/\r\n|\n|\r/", $videoUrlText);
                $slot->setData(new ArrayStruct($videoUrls));
            }
        }
    }

    public function getType(): string
    {
        return 'manufacturer-video-slider';
    }
}
