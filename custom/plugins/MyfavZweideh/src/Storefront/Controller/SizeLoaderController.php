<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Biloba\ArticleVariantOrderMatrix\Structs\StoreFrontPageCollection;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\Configurator\ProductPageConfiguratorLoader;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
 
/**
 * 
 * Dieser Controller lädt erweiterte Daten zu einem Artikel.
 * Insbesondere orientiert er sich am BilobaVariantMatrix,
 * und lädt die verfügbaren Größen eines Artikels,
 * damit der Kunde diese im Anfrage-Popup auswählen kann.
 * 
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class SizeLoaderController extends StorefrontController {
    private bool $debug = false;
    private Connection $connection;
    private EntityRepository $productRepository;
    private SalesChannelRepository $salesChannelProductRepository;
    private TagAwareAdapterInterface $cache;
    private SystemConfigService $systemConfigService;
    protected ProductPageConfiguratorLoader $productPageConfiguratorLoader;
    
    public function __construct(
        Connection $connection,
        EntityRepository $productRepository,
        SalesChannelRepository $salesChannelProductRepository,
        TagAwareAdapterInterface $cache, 
        SystemConfigService $systemConfigService = null,
        ProductPageConfiguratorLoader $productPageConfiguratorLoader
    ) {
        $this->connection = $connection;
        $this->productRepository = $productRepository;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->cache = $cache;
        $this->systemConfigService = $systemConfigService;
        $this->productPageConfiguratorLoader = $productPageConfiguratorLoader;
    }

    
    #[Route(
        path: '/lumiseSizeLoader/fetch',
        name: 'frontend.zweideh.lumise.article.data.fetch',
        methods: ['GET'],
        defaults: ['XmlHttpRequest' => true]
    )]
	public function fetch(Request $request, SalesChannelContext $salesChannelContext): JsonResponse
    {
        //print_r($request);die;
        //dump($request->query->all());die;
        if($this->debug === true) {
            return new JsonResponse($this->getDebugArray());
        }
        
        $lumiseArticleId = (int)$request->query->get('lumise_article_id');

        if(0 === $lumiseArticleId) {
            throw new \Exception('Article not found');
        }

        // Load shopware product id by lumise product id.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products_hashes', 'lph');
        $queryBuilder->where('lph.lumise_product_id = :lumise_product_id');
        $queryBuilder->setParameter('lumise_product_id', $lumiseArticleId);
        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (!is_array($results) || count($results) === 0) {
            throw new \Exception('Product with custom field value lumis_designer_article_id = ' . 
                htmlspecialchars((string) $lumiseArticleId) . ' not found');
        }
        /*$results = $queryBuilder->executeStatement();

        if (!is_array($results) || count($results) == 0) {
            throw new \Exception('Product with custom field value lumis_designer_article_id = ' . htmlspecialchars("" . $lumiseArticleId) . 'not found');
        }*/

        $shopwareProductId = $results[0]['shopware_product_id'];

        // Load Shopware Article by Custom Field?!?
        $criteria = new Criteria([$shopwareProductId]);
        $criteria->addAssociation('customFields')
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media')
            ->addAssociation('configuratorSettings.option')
            ->addAssociation('configuratorSettings.option.media')
            ->addAssociation('configuratorSettings.option.group');
        //$criteria->addFilter(new EqualsFilter('customFields.lumis_designer_article_id', $lumiseArticleId));
        
        $products = $this->salesChannelProductRepository->search(
            $criteria,
            $salesChannelContext
        );

        $product = $products->first();

        if(null === $product) {
            throw new \Exception('Product not found');
        }

        $data = $this->loadAdditionalData($salesChannelContext, $products->first());

        return new JsonResponse([
            'status' => 'success',
            'data' => $data
        ]);
    }
    
    /**
     * loadAdditionalData
     *
     * @return void
     */
    private function loadAdditionalData($salesChannelContext, $product)
    {
        //$extensionData = new StoreFrontPageCollection();
        $data = [];
        $productVariantMatrixActive = false;
        $variantMatrix = [];
        $selectedOptions = [];
        $lastOption = [];
        $allSelectedOptions = $product->getOptions()->getElements();
        $optionGroups = $this->productPageConfiguratorLoader->load($product, $salesChannelContext)->getElements();

        $availableSizes = [];

        foreach($optionGroups as $index => $value) {
            $name = $value->getName();

            if($name == 'Size') {
                $tmpOptions = $value->getOptions();

                foreach($tmpOptions as $tmpOptionIndex => $tmpOptionValue) {
                    $optionValueName = $tmpOptionValue->getName();
                    $availableSizes[] = [
                        'optionId' => $tmpOptionValue->getId(),
                        'name' => $optionValueName
                    ];
                }
            }
        }
        $data['availableSizes'] = $availableSizes;

        /**
         * Der nachfolgende Code stammt so (mit Anpassungen, dass er hier funktioniert) aus BilobaArticleVariantOrderMatrix.
         */
        /**
         * Trying to filter prices by rule and to calculatePrices for every child product 
         */
        //$product = $event->getPage()->getProduct();
        /*
        $lastGroupAsMatrixAndOtherGroupsAsSwOptionsLayout = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixLayout');
        $hideVariantsWithNoStock = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.hideVariantsWithNoStock');
        $firstGroupSelectionSecondGroupMatrix = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixLayout');

        $breakpoints = $this->systemConfigService->get('BilobaArticleVariantOrderMatrix.config.variantMatrixSelectorBreakpointsNames');

        $lastGroup = end($optionGroups);
        $lastGroupOptionsIds = [];

        if($optionGroups) {
            foreach($lastGroup->getOptions()->getPropertyGroupIds() as $groupOptionId => $groupId ) {
                array_push($lastGroupOptionsIds, $groupOptionId);
            }
        }

        $parentId = $product->getParentId();
        
        foreach($allSelectedOptions as $key => $element) {
            if($lastGroupAsMatrixAndOtherGroupsAsSwOptionsLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions') {
                if(array_key_exists($key, $lastGroup->getOptions()->getElements())) {
                    array_push($lastOption, $element);
                }else {
                    array_push($selectedOptions, $element->get('id'));
                }
            }
        }

        if($parentId && $parentId != 'null') {
            $optionIds = $product->getOptionIds();
            
            // Change condition to optionIds>0 && optionIds<=3 
            if(
                count($optionIds) > 0  && 
                count($optionIds) < 3 or 
                    (
                        count($optionIds) > 0 && 
                        $lastGroupAsMatrixAndOtherGroupsAsSwOptionsLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions'
                    )
            ) {
                $productVariantMatrixActive = true;

                $criteria = new Criteria([
                    $parentId
                ]);
                $criteria->addAssociation('children');
                $criteria->addAssociation('configuratorGroupConfig.groups');
                $criteria->addAssociation('children.prices');

                $entities = $this->productRepository->search(
                    $criteria,
                    $salesChannelContext->getContext()
                )->getElements();
        
                if(count($entities) > 0) {
                    $parentProduct = $entities[array_key_first($entities)];
                }
                
                if(
                    $parentProduct->getCustomFields() &&
                    array_key_exists('biloba_variant_matrix_hide', $parentProduct->getCustomFields()) &&
                    $parentProduct->getCustomFields()['biloba_variant_matrix_hide'])
                {
                    if($parentProduct->getCustomFields()['biloba_variant_matrix_hide'] == true)
                    {
                        $productVariantMatrixActive = false;
                    }
                    else
                    {
                        $productVariantMatrixActive = true;
                    }
                }
                else
                {
                    $productVariantMatrixActive = true;
                }

                if($productVariantMatrixActive)
                {
                    $children = $parentProduct->getChildren();
                    
                    if($children)
                    {
                        if(
                            $lastGroupAsMatrixAndOtherGroupsAsSwOptionsLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions' &&
                            // is_countable($optionGroups) &&
                            count($optionGroups) > 1)
                        {
                            // Only add child products for current selection.
                            foreach($children as $child) {
                                if(($hideVariantsWithNoStock == true && $child->getStock() > 0) || ($hideVariantsWithNoStock == false)) {
                                    $optionIds = $child->getOptionIds();
                                    
                                    $key = '';
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
                        }
                        else
                        {
                            foreach($children as $child)
                            {
                                /**
                                 * check for if 1. Group as selection, 2. group as matrix true and stock > 0 or other layout
                                 */
                                /*
                                if(
                                    (
                                        $hideVariantsWithNoStock == true &&
                                        $child->getStock() > 0 &&
                                        (
                                            $firstGroupSelectionSecondGroupMatrix == 'selector' ||
                                            $lastGroupAsMatrixAndOtherGroupsAsSwOptionsLayout == 'lastGroupAsMatrixAndOtherGroupsAsSwOptions'
                                        )
                                    ) || (
                                        $hideVariantsWithNoStock == false
                                    ) || (
                                            $hideVariantsWithNoStock == true &&
                                            (
                                                $firstGroupSelectionSecondGroupMatrix != 'selector'&&
                                                $lastGroupAsMatrixAndOtherGroupsAsSwOptionsLayout != 'lastGroupAsMatrixAndOtherGroupsAsSwOptions'
                                            )
                                        )
                                    )
                                {
                                    $optionIds = $child->getOptionIds();

                                    if(count($optionIds) == 1)
                                    {
                                        $variantMatrix[$optionIds[0]] = $child->getId();
                                    }
                                    elseif(count($optionIds) == 2)
                                    {
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

                $data['variantMatrix'] = $variantMatrix;
                $data['children'] =  $parentProduct->getChildren()->getElements();
                $data['parent'] =  $parentProduct;
                $data['selectedOptions'] = $selectedOptions; 
            }
        }

        $data['variantMatrixActive'] = $productVariantMatrixActive;
        $data['breakpoints'] = $breakpoints;
        */

        return $data;
    }

    private function getDebugArray() {
        return ([
            "status" => "success",
            "debugData" => "true",
            "debugDataInfo" => "Achtung, es handelt sich um Daten zum Debuggen, die fest geschrieben sind in SizeLoaderController. Bitte deaktivieren Sie den Debug-Modus im Controller über die Klassen-Variable debug, um Live-Daten zu empfangen.",
            "data" => [
                "availableSizes" => [
                    [
                        "optionId" => "477dd914c32942bdbfc571eeef4cdcc4",
                        "name" => "XS"
                    ],
                    [
                        "optionId" => "7644dba523d444449b9e96db9ffe25be",
                        "name" => "S"
                    ],
                    [
                        "optionId" => "1847101bc3f44801ace9c247e02b9560",
                        "name" => "M"
                    ],
                    [
                        "optionId" => "7dfb0874d81f45728569a2aa26295d18",
                        "name" => "L"
                    ],
                    [
                        "optionId" => "1bbeec3a3b5d4580b90ff4eb92f108c0",
                        "name" => "XL"
                    ],
                    [
                        "optionId" => "5375b87920d947c7a310895bbafe5846",
                        "name" => "3XL"
                    ],
                    [
                        "optionId" => "e750802f78264ee99326ee27a5e1d867",
                        "name" => "4XL"
                    ],
                    [
                        "optionId" => "77e0d5fcb2f842b4b164f4de47e497a1",
                        "name" => "5XL"
                    ],
                    [
                        "optionId" => "17e1366a3e644cfa9e211001a3096264",
                        "name" => "2XL"
                    ]
                ]
            ]
        ]);
    }
}