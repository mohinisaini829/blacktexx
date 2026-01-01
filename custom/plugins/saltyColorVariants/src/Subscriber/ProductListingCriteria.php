<?php

declare(strict_types=1);

namespace salty\ColorVariants\Subscriber;

use salty\ColorVariants\Services\ColorVariantsServiceInterface;
use salty\ColorVariants\Structs\StorefrontListingData;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductListingCriteria implements EventSubscriberInterface
{
    protected SystemConfigService $configService;

    /**
     * @phpstan-var array<string, mixed>
     */
    protected array $config = [];

    protected ColorVariantsServiceInterface $colorVariantsService;

    public function __construct(SystemConfigService $configService, ColorVariantsServiceInterface $colorVariantsService)
    {
        $this->configService = $configService;
        $this->colorVariantsService = $colorVariantsService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingResultEvent::class => 'extendProductListingResults',
            ProductSearchResultEvent::class => 'extendProductListingResults',
        ];
    }

    /**
     * @phpstan-param ProductListingResultEvent|ProductSearchResultEvent $event
     */
    public function extendProductListingResults(NestedEvent $event): void
    {
        $config = $this->configService->get('saltyColorVariants.config', $event->getSalesChannelContext()->getSalesChannel()->getId());

        if (!\is_array($config)) {
            return;
        }

        $this->config = $config;

        $criteria = $this->colorVariantsService->buildCriteria($event->getResult()->getEntities(), $event->getSalesChannelContext());
        $colorVariants = $this->colorVariantsService->getColorVariants($criteria, $event->getSalesChannelContext());

        $event->getResult()->addExtension('colorVariants', new StorefrontListingData($colorVariants, $this->config));
    }
}
