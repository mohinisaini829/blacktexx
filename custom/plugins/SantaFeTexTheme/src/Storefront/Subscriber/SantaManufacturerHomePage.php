<?php declare(strict_types=1);

namespace SantaFeTexTheme\Storefront\Subscriber;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductBoxStruct;

class SantaManufacturerHomePage implements EventSubscriberInterface
{
    protected EntityRepository $productManufacturerRepository;

    public function __construct(EntityRepository $productManufacturerRepository)
    {
        $this->productManufacturerRepository = $productManufacturerRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CmsPageLoadedEvent::class => 'onCmsPageLoadedEvent',
        ];
    }

    public function onCmsPageLoadedEvent(CmsPageLoadedEvent $event): void
    {
        $result = $event->getResult()->first();

        if (!$result) {
            return;
        }

        foreach ($result->getSections() as $section) {
            // Handle product-slider blocks
            $productBlocks = $section->getBlocks()->filter(function (CmsBlockEntity $blockEntity) {
                return $blockEntity->getType() === 'product-slider';
            });

            if ($productBlocks->count() > 0) {
                $productSlots = $productBlocks->getSlots()->filter(function (CmsSlotEntity $slot) {
                    return $slot->getType() === 'product-slider';
                });

                foreach ($productSlots as $slot) {
                    $slotData = $slot->getData();

                    if (method_exists($slotData, 'getProducts')) {
                        $products = $slotData->getProducts();

                        foreach ($products as $product) {
                            if (!$product) {
                                continue;
                            }

                            $manufacturerId = $product->getManufacturerId();
                            if ($manufacturerId) {
                                $manufacturer = $this->getManufacturer($manufacturerId);
                                if ($manufacturer) {
                                    $product->addExtension('shop_manufacturer', $manufacturer);
                                }
                            }
                        }
                    }
                }
            }

            // Handle product-three-column blocks
            $productColumnBlocks = $section->getBlocks()->filter(function (CmsBlockEntity $blockEntity) {
                return $blockEntity->getType() === 'product-three-column';
            });

            if ($productColumnBlocks->count() > 0) {
                $productColumnSlots = $productColumnBlocks->getSlots()->filter(function (CmsSlotEntity $slot) {
                    return $slot->getType() === 'product-box';
                });

                foreach ($productColumnSlots as $slot) {
                    $slotData = $slot->getData();

                    if ($slotData instanceof ProductBoxStruct && $slotData->getProduct()) {
                        $product = $slotData->getProduct();
                        $manufacturerId = $product->getManufacturerId();

                        if ($manufacturerId) {
                            $manufacturer = $this->getManufacturer($manufacturerId);
                            if ($manufacturer) {
                                $product->addExtension('shop_manufacturer', $manufacturer);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $manufacturerId
     * @return ProductManufacturerEntity|null
     */
    private function getManufacturer(string $manufacturerId): ?ProductManufacturerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $manufacturerId));

        return $this->productManufacturerRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
