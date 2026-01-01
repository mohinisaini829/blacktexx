<?php declare(strict_types=1);

namespace Biloba\ArticleVariantOrderMatrix\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Biloba\ArticleVariantOrderMatrix\Structs\StoreFrontPageCollection;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\Configurator\ProductPageConfiguratorLoader;

class ProductDetailSubscriber implements EventSubscriberInterface
{
    private $productRepository;
    private $productRepositorySalesChannel;
    private $cache;
    private $systemConfigService;

    /**
     * @var ProductPageConfiguratorLoader
     */
    protected $productPageConfiguratorLoader;

    public function __construct($productRepository,
            $productRepositorySalesChannel,
            TagAwareAdapterInterface $cache, 
            SystemConfigService $systemConfigService = null,
            ProductPageConfiguratorLoader $productPageConfiguratorLoader
            )
    {
        $this->productRepository = $productRepository;
        $this->productRepositorySalesChannel = $productRepositorySalesChannel;
        $this->cache = $cache;
        $this->systemConfigService = $systemConfigService;
        $this->productPageConfiguratorLoader = $productPageConfiguratorLoader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            MinimalQuickViewPageLoadedEvent::class => 'onProductPageLoaded',
            ProductListingResultEvent::class => 'onProductListingLoaded',
        ];
    }
    
    public function onProductListingLoaded(ProductListingResultEvent $event): void
    {
        $extensionData = new StoreFrontPageCollection();
        
        // load parents of products in list to get custom fields
        $result = $event->getResult();
        $parentIds = [];
        foreach($result->getEntities() as $entity) {
            if($entity->getParentId()) {
                $parentIds[] = $entity->getParentId();
            }
        }

        if(count($parentIds) > 0) {
            $criteria = new Criteria($parentIds);
            
            $entities = $this->productRepository->search(
                $criteria,
                $event->getContext()
            )->getElements();
        }
        else {
            $entities = null;
        }

        $extensionData->setValue('parentProducts', $entities);
        $extensionData->setValue('showShopwareSelection', false);
        $event->getContext()->addExtension('bilobaArticleVariantOrderMatrix', $extensionData);
    }

    public function onProductPageLoaded($event): void
    {
        $extensionData = new StoreFrontPageCollection();
        $productVariantMatrixActive = false;
        $variantMatrix = [];
        $selectedOptions = [];
        $lastOption = [];
        $showShopwareSelection = false;

        if($this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixEnabled', $event->getSalesChannelContext()->getSalesChannel()->getId())) {
            // check if loaded via listing and set correct layout
            if($event->getSalesChannelContext()->getExtension('bilobaArticleVariantOrderMatrix')
                && $event->getSalesChannelContext()->getExtension('bilobaArticleVariantOrderMatrix')->getValue('listingLayout')
                && $event->getSalesChannelContext()->getExtension('bilobaArticleVariantOrderMatrix')
                    && $event->getSalesChannelContext()->getExtension('bilobaArticleVariantOrderMatrix')->getValue('listingLayout')
                    && substr($event->getSalesChannelContext()->getExtension('bilobaArticleVariantOrderMatrix')->getValue('listingLayout'), 0, strlen('inline_')) == 'inline_') {
                $variantMatrixLayout = explode('_', $event->getSalesChannelContext()->getExtension('bilobaArticleVariantOrderMatrix')->getValue('listingLayout'))[1];
            }
            else {
                $variantMatrixLayout = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixLayout', $event->getSalesChannelContext()->getSalesChannel()->getId());
            }

            $allSelectedOptions = $event->getPage()->getProduct()->getOptions()->getElements();
            $optionGroups = $this->productPageConfiguratorLoader->load($event->getPage()->getProduct(), $event->getSalesChannelContext())->getElements();
            /**
             * Trying to filter prices by rule and to calculatePrices for every child product 
             */
            $product = $event->getPage()->getProduct();
            
            $hideVariantsWithNoStock = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.hideVariantsWithNoStock', $event->getSalesChannelContext()->getSalesChannel()->getId());
            $lastGroup = end($optionGroups);
            $lastGroupOptionsIds = [];
            
            $variantMatrixLayoutStandardEnableShopwareSelection = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixLayoutStandardEnableShopwareSelection', $event->getSalesChannelContext()->getSalesChannel()->getId());
            if($variantMatrixLayout == 'standard' && $variantMatrixLayoutStandardEnableShopwareSelection) {
                $showShopwareSelection = true;
            }

            $variantMatrixLayoutSelectorEnableShopwareSelection = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixLayoutSelectorEnableShopwareSelection', $event->getSalesChannelContext()->getSalesChannel()->getId());
            if($variantMatrixLayout == 'selector' && $variantMatrixLayoutSelectorEnableShopwareSelection) {
                $showShopwareSelection = true;
            }
            
            if($optionGroups) {
                foreach($lastGroup->getOptions()->getPropertyGroupIds() as $groupOptionId => $groupId ) {
                    array_push($lastGroupOptionsIds, $groupOptionId);
                }
            }
            $parentId = $event->getPage()->getProduct()->getParentId();
            
            foreach($allSelectedOptions as $key => $element) {
                if($variantMatrixLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions') {
                    if(array_key_exists($key, $lastGroup->getOptions()->getElements())) {
                        array_push($lastOption, $element);
                    }else {
                        array_push($selectedOptions, $element->get('id'));
                    }
                }
            }
            
            if($parentId && $parentId != 'null') {
                
                $optionIds =  (array)$event->getPage()->getProduct()->getOptionIds();
                // Change condition to optionIds>0 && optionIds<=3 
                if(count($optionIds) > 0 && count($optionIds) < 3 or (count($optionIds) > 0 && $variantMatrixLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions')) {
                    $productVariantMatrixActive = true;

                    $criteria = new Criteria([
                        $parentId
                    ]);
                    $criteria->addAssociation('children');
                    if($this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.useVariantImageOnXAxis', $event->getSalesChannelContext()->getSalesChannel()->getId())
                            || $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.useVariantImageOnYAxis', $event->getSalesChannelContext()->getSalesChannel()->getId())
                            || $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.useVariantImageOnSelectionButton', $event->getSalesChannelContext()->getSalesChannel()->getId())) {
                        $criteria->addAssociation('children.media');
                        $criteria->addAssociation('children.media.media');
                        $criteria->addAssociation('children.cover');
                    }
                    $criteria->addAssociation('configuratorGroupConfig.groups');
                    $criteria->addAssociation('children.prices');

                    $entities = $this->productRepositorySalesChannel->search(
                        $criteria,
                        $event->getSalesChannelContext()
                    )->getElements();
                    
                    if(count($entities) > 0) {
                        $parentProduct = $entities[array_key_first($entities)];

                        if($parentProduct->getCustomFields() && array_key_exists('biloba_variant_matrix_hide', $parentProduct->getCustomFields()) && $parentProduct->getCustomFields()['biloba_variant_matrix_hide']) {
                            if($parentProduct->getCustomFields()['biloba_variant_matrix_hide'] == true) {
                                $productVariantMatrixActive = false;
                            }
                            else {
                                $productVariantMatrixActive = true;
                            }
                        }
                        else {
                            $productVariantMatrixActive = true;
                        }

                        if($productVariantMatrixActive) {
                            $children = $parentProduct->getChildren();
                            
                            if($children) {
                                
                                if($variantMatrixLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions' && count($optionGroups) > 1) {
                                    // only add child products for current selection
                                    foreach($children as $child) {
                                        if(($hideVariantsWithNoStock == true && $child->getStock() > 0) || ($hideVariantsWithNoStock == false)) {
                                            $optionIds = $child->getOptionIds();
                                            
                                            $key = '';
                                            // $found = true;
                                            $found = false;
                                            
                                            foreach($optionIds as $index => $option) {
                                                // check if option belongs to options of last group
                                                if(in_array($option, $lastGroupOptionsIds)) {
                                                    // check if other option of child belongs to the current selection
                                                    foreach($optionIds as $optionSelected) {
                                                        if(in_array($optionSelected, $selectedOptions)) {
                                                            $found = true;
                                                        }
                                                        elseif(!in_array($optionSelected, $lastGroupOptionsIds)) {
                                                            $found = false;
                                                            break;
                                                        }
                                                    }             
                                                }
                                                
                                                if($found) {
                                                    $variantMatrix[$option] = $child->getId();
                                                }    
                                            }
                                        }
                                    }
                                } else {
                                    foreach($children as $child) {
                                        /**
                                         * check for if 1. Group as selection, 2. group as matrix true and stock > 0 or other layout
                                         */
                                        if(
                                            ($hideVariantsWithNoStock == true && $child->getStock() > 0 && ($variantMatrixLayout == 'selector' || $variantMatrixLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions'))
                                            || ($hideVariantsWithNoStock == false) 
                                            || ($hideVariantsWithNoStock == true && ($variantMatrixLayout != 'selector' && $variantMatrixLayout != 'lastGroupAsMatrixAndOtherGroupsAsSwOptions'))
                                        )
                                        {
                                            $optionIds = $child->getOptionIds();
                                            if(count($optionIds) == 1) {
                                                $variantMatrix[$optionIds[0]] = $child->getId();
                                            }
                                            elseif(count($optionIds) == 2) {
                                                $key1 = $optionIds[0] . '_' . $optionIds[1];
                                                $key2 = $optionIds[1] . '_' . $optionIds[0];
                                                
                                                $variantMatrix[$key1] = $child->getId();
                                                $variantMatrix[$key2] = $child->getId();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $extensionData->setValue('variantMatrix', $variantMatrix);
                        $extensionData->setValue('children', $parentProduct->getChildren()->getElements());
                        $extensionData->setValue('parent', $parentProduct);
                        $extensionData->setValue('selectedOptions', $selectedOptions); 
                    }
                    else {
                        $productVariantMatrixActive = false;
                    }
                }
            }

            $extensionData->setValue('variantMatrixLayout', $variantMatrixLayout);
        }
        
        if($productVariantMatrixActive == false) {
            $showShopwareSelection = false;
        }
        
        if (isset($variantMatrixLayout) && $variantMatrixLayout === 'selector') {
            if(isset($allSelectedOptions) && is_array($allSelectedOptions) && count($allSelectedOptions) === 1) {
                // get EnableGroupMatrix config
                $enableGroupMatrix = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.EnableGroupMatrix', $event->getSalesChannelContext()->getSalesChannel()->getId());

                if(!$enableGroupMatrix) {
                    $productVariantMatrixActive = false;
                }
            }
        }

        $extensionData->setValue('showShopwareSelection', $showShopwareSelection);
        $extensionData->setValue('variantMatrixActive', $productVariantMatrixActive);
        $event->getPage()->addExtension('bilobaArticleVariantOrderMatrix', $extensionData);
    }
}