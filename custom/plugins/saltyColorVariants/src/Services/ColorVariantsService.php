<?php

declare(strict_types=1);

namespace salty\ColorVariants\Services;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingCollection;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\ProductCloseoutFilter;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ColorVariantsService implements ColorVariantsServiceInterface
{
    /**
     * @phpstan-var EntityRepository<ProductCollection> $repository
     */
    protected EntityRepository $repository;

    protected SystemConfigService $configService;

    /**
     * @phpstan-param EntityRepository<ProductCollection> $repository
     */
    public function __construct(EntityRepository $repository, SystemConfigService $configService)
    {
        $this->repository = $repository;
        $this->configService = $configService;
    }

    public function getColorVariants(?Criteria $criteria, SalesChannelContext $context): array
    {
        if (null === $criteria) {
            return [];
        }

        $colorVariants = [];

        $products = $this->getProducts($criteria, $context);

        if (null === $products) {
            return [];
        }

        foreach ($products as $product) {
            /** @var ProductEntity $product */
            $colorOptions = $this->extractColorOptions($product);
            $colorVariants = $this->fetchColorVariants($colorOptions, $product, $colorVariants, $context);
        }

        return $colorVariants;
    }

    /**
     * {@inheritDoc}
     */
    public function buildCriteria(EntityCollection $result, SalesChannelContext $salesChannelContext): ?Criteria
    {
        $addPropertyMedia = $this->configService->getBool('saltyColorVariants.config.usePropertyMedia', $salesChannelContext->getSalesChannelId());
        $useVariantConfigurationMedia = $this->configService->getBool('saltyColorVariants.config.useVariantConfigurationMedia', $salesChannelContext->getSalesChannelId());
        $addVariantCover = $this->configService->getBool('saltyColorVariants.config.enablePreview', $salesChannelContext->getSalesChannelId());
        $hideCloseoutProductsWhenOutOfStock = $this->configService->getBool('core.listing.hideCloseoutProductsWhenOutOfStock', $salesChannelContext->getSalesChannelId());

        $productIds = $this->getRelevantParentIds($result);

        if (empty($productIds)) {
            return null;
        }

        try {
            $criteria = new Criteria($productIds);

            $criteria->addAssociation('configuratorSettings.option.group');
            $criteria->addAssociation('configuratorSettings.option.translations');
            $criteria->addAssociation('children');

            $criteria->getAssociation('configuratorSettings')
                ->addSorting(new FieldSorting('position'))
                ->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

            $criteria->getAssociation('children')->addFilter(new ProductAvailableFilter($salesChannelContext->getSalesChannelId()));

            if ($hideCloseoutProductsWhenOutOfStock) {
                $criteria->getAssociation('children')->addFilter(new ProductCloseoutFilter());
            }

            if (true === $addPropertyMedia) {
                $criteria->addAssociation('configuratorSettings.option.media');
            }

            if (true === $useVariantConfigurationMedia) {
                $criteria->addAssociation('configuratorSettings.media');
            }

            if (true === $addVariantCover) {
                $criteria
                    ->addAssociation('children.cover.media');
            }
        } catch (InconsistentCriteriaIdsException $e) {
            return null;
        }

        return $criteria;
    }

    protected function extractColorOptions(ProductEntity $product): ?ProductConfiguratorSettingCollection
    {
        if (null === $product->getConfiguratorSettings()) {
            return null;
        }

        return $product->getConfiguratorSettings()->filter(static function (ProductConfiguratorSettingEntity $variant) {
            if (null === $variant->getOption()) {
                return false;
            }

            return null !== $variant->getOption()->getColorHexCode() || null !== $variant->getOption()->getMedia();
        });
    }

    /**
     * @param EntityCollection<ProductEntity> $result
     *
     * @phpstan-return array<array<string, string>|string>
     */
    protected function getRelevantParentIds(EntityCollection $result): array
    {
        $productIds = [];

        foreach ($result as $product) {
            if (!$product instanceof SalesChannelProductEntity) {
                continue;
            }

            if (null !== $product->getParentId()) {
                $productIds[] = $product->getParentId();
                continue;
            }

            if ($product->getChildCount() > 0) {
                $productIds[] = $product->getId();
            }
        }

        return $productIds;
    }

    /**
     * @phpstan-return EntitySearchResult<ProductCollection>|null
     */
    protected function getProducts(?Criteria $criteria, SalesChannelContext $context): ?EntitySearchResult
    {
        if (null === $criteria) {
            return null;
        }

        return $this->repository->search($criteria, $context->getContext());
    }

    protected function getRelatedVariant(ProductEntity $product, string $optionId): ?ProductEntity
    {
        return $this->filterByOptionIds($product, [$optionId])->first();
    }

     private function

    fetchColorVariants(?ProductConfiguratorSettingCollection $colorOptions, ProductEntity $product, array $colorVariants, SalesChannelContext $salesChannelContext): array
    {
        $productId = $product->getId();
        $maxVariants = $this->configService->getInt('saltyColorVariants.config.maxIndicators', $salesChannelContext->getSalesChannelId());

        if (null === $colorOptions || null === $colorOptions->first()) {
            return $colorVariants;
        }

        foreach ($colorOptions as $colorOption) {
            /** @var ProductConfiguratorSettingEntity $colorOption */
            $variant = $this->getRelatedVariant($product, $colorOption->getOptionId());

            if (null === $variant || null === $colorOption->getOption()) {
                continue;
            }

            $optionImageUrl = $this->getOptionImageUrl($colorOption);
            $optionComparisonHash = md5($colorOption->getOption()->getColorHexCode() . $optionImageUrl);

            // avoid duplicates
            if (\array_key_exists($productId, $colorVariants)
                && \in_array($optionComparisonHash, array_column($colorVariants[$productId], 'optionComparisonHash'), true)) {
                continue;
            }

            if (\array_key_exists($productId, $colorVariants) && \count($colorVariants[$productId]) >= $maxVariants) {
                $this->addMoreColorsIndicator($colorVariants, $productId, $salesChannelContext);

                return $colorVariants;
            }

            $colorVariants[$productId][] = [
                'hasMoreColors' => false,
                'url' => '',
                'image' => (null !== $variant->getCover() && null !== $variant->getCover()->getMedia()) ? $variant->getCover()->getMedia()->getUrl() : '',
                'cover' => (null !== $variant->getCover()) ? $variant->getCover()->getMedia() : '',
                'productId' => $variant->getId(),
                'optionGroupId' => $colorOption->getOption()->getGroupId(),
                'colorName' => $colorOption->getOption()->getName(),
                'colorHexCode' => $colorOption->getOption()->getColorHexCode(),
                'optionImage' => $optionImageUrl,
                'optionComparisonHash' => $optionComparisonHash,
            ];
        }

        return $colorVariants;
    }

    private function getOptionImageUrl(ProductConfiguratorSettingEntity $colorOption): string
    {
        if (null === $colorOption->getOption()) {
            return '';
        }

        $optionImageUrl = '';

        if (null !== $colorOption->getOption()->getMedia()) {
            $optionImageUrl = $colorOption->getOption()->getMedia()->getUrl();
        }

        if (null !== $colorOption->getMedia()) {
            $optionImageUrl = $colorOption->getMedia()->getUrl();
        }

        return $optionImageUrl;
    }

    /**
     * @param array<int|string, mixed> $colorVariants
     */
    private function addMoreColorsIndicator(array &$colorVariants, string $productId, SalesChannelContext $salesChannelContext): void
    {
        $moreColorsIndicator = $this->configService->getBool('saltyColorVariants.config.moreColorsIndicator', $salesChannelContext->getSalesChannelId());

        if (false === $moreColorsIndicator) {
            return;
        }

        $colorVariants[$productId][] = [
            'hasMoreColors' => true,
        ];
    }

    /**
     * @param string[] $optionIds
     */
    private function filterByOptionIds(ProductEntity $product, array $optionIds): ?ProductCollection
    {
        if (null === $product->getChildren() || $product->getChildren()->count() < 1) {
            return null;
        }

        return $product->getChildren()->filter(
            function (ProductEntity $product) use ($optionIds) {
                $ids = $product->getOptionIds() ?? [];
                $same = array_intersect($ids, $optionIds);

                return \count($same) === \count($optionIds);
            }
        );
    }
}
