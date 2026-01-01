<?php declare(strict_types=1);

namespace ArticleImport\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Shopware\Core\Defaults;
use ArticleImport\Helper\Data;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

#[AsCommand(
    name: 'import:articlesfalkross',
    description: 'Import Articles/Products from a file for Falkross',
)]
class ImportArticlesFalkross extends Command
{
    private SystemConfigService $systemConfigService;
    private $ImportHelper;
    private $productPriceRepository;
    public $productConfiguratorSettingRepo;
    public $customFieldRepository;
    private Connection $connection;

    public function __construct(
        SystemConfigService $systemConfigService,
        #[Autowire('product.repository')] 
        EntityRepository $productRepository,
        Data $importHelper,
        EntityRepository $productPriceRepository,
        EntityRepository $productConfiguratorSettingRepo,
        EntityRepository $customFieldRepository,
        Connection $connection
    ){
        $this->systemConfigService  =   $systemConfigService;
        $this->productRepository    =   $productRepository;
        $this->importHelper         =   $importHelper;
        $this->customFieldRepository            =   $customFieldRepository;
        $this->productPriceRepository           =   $productPriceRepository;
        $this->productConfiguratorSettingRepo   =   $productConfiguratorSettingRepo;
        $this->connection                       =   $connection;
        parent::__construct();
    }

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Import Products falkross.');
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvPath = __DIR__ . '/csv/Falkross.csv';

        if (!file_exists($csvPath)) {
            $output->writeln('<error>CSV file not found at: ' . $csvPath . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Reading CSV from:</info> ' . $csvPath);

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $output->writeln('<error>Unable to open the CSV file.</error>');
            return Command::FAILURE;
        }

        /* IGNORING FIRST $ ROWS */
        $headers = fgetcsv($handle);
        $headers = fgetcsv($handle);
        $headers = fgetcsv($handle);
        $headers = fgetcsv($handle);
        /* IGNORING FIRST $ ROWS */

        $headers = fgetcsv($handle);
        if (!$headers) {
            $output->writeln('<error>CSV file is empty or invalid.</error>');
            return Command::FAILURE;
        }

        $unsetKeys  =   [8,12,15,20,29,32,33,34,36,37,38,39,50];

        /* MODiFY HEADER */
        $headers[14]  =   'Artikelnummer_kurz_1';
        $headers[19]  =   'color_size';
        $headers[3]  =   'Article Shop Group';
        $headers[17]  =   'child_sku';
        $headers[18]  =   'master_sku';
        /* MODiFY HEADER */

        $rowCount   =   1;

        $parentProIds   =   [];

        while (($row = fgetcsv($handle)) !== false) {


            foreach ($unsetKeys as $unsetKeysKey => $unsetKeysValue) {
                unset($headers[$unsetKeysValue]);
                unset($row[$unsetKeysValue]);
            }

            $data = array_combine($headers, $row);
            //print_r($data); die;

            $data = array_combine(
                array_map('trim', array_keys($data)),
                $data
            );

            /* Parent Product */
            $masterSku  =   $data['master_sku'];
            if (isset($parentProIds[$masterSku]) && !empty($parentProIds[$masterSku])){
                $parentProductId            =   $parentProIds[$masterSku];
            } else {
                $parentData =   [
                                    'Sku'   =>  $masterSku,
                                    'Name'  =>  $data['Artikelname'],
                                    'Ean'  =>  '',
                                    'Description'  =>  $data['Zolltarifnummer'],
                                    'Price'     =>  $data['Price_Qty_1']
                                ];

                if (isset($data['Falk & Ross']) && !empty($data['Falk & Ross'])){
                    $parentData['brand']    =   $data['Falk & Ross'];
                }

                $parentProductId            =   $this->createParentProduct($parentData);
                $parentProIds[$masterSku]   =   $parentProductId;
            }
            /* Parent Product */
            $childProId       =   $this->importHelper->checkIfProductExist($data['Hersteller_Code']);
            if (empty($childProId)){

                if ($rowCount >= 20){
                    die("20 product created");
                }

                $this->processRow($data, $parentProIds[$masterSku]);
                $rowCount++;
                $output->writeln("Product created/updated {$data['Artikelname']}");
            } else {
                $output->writeln("Product already created {$data['Artikelname']}");
            }
            //die("first pro created");
        }
        fclose($handle);

        return Command::SUCCESS;
    }

    private function processRow($data, $parentProductId = ''){
        /* VARIANTS */
        $childResultData    =   $this->createChildProduct($data, $parentProductId);

        $optionsCreated     =   $childResultData['optionsCreated'];
        $variantData        =   $childResultData['variantData'];
        $childProductId     =   $childResultData['childProductId'];

        if (!empty($optionsCreated)){
            foreach ($optionsCreated as $optionsCreatedKey => $optionsCreatedValue) {
                $data = [
                            'id' => $parentProductId,
                            'variantListingConfig' => [
                                'displayParent' => false,
                                'mainVariantId' => $childProductId
                            ],
                            'configuratorSettings' =>   [$optionsCreatedValue]
                        ];

                try {
                    $this->productRepository->update([$data], Context::createDefaultContext());
                } catch (\Exception $e) {
                    echo "Issue in updating product config setting ".$e->getMessage();
                    //die("Issue in updating product config setting");
                }
            }
        }

        if (!empty($variantData)){
            $upData     =   [];
            /* UPDATE PRODUCT VARIANTS/SWATCH IMAGE */
            if (!empty($variantData)) {
                foreach ($variantData as $variantDataKey => $variantDataValue) {
                    if (isset($variantDataValue['optId'])) {
                        $optId               =   $variantDataValue['optId'];
                        $proVariantProperyId =   $this->importHelper->getProuctVariantProperyId($parentProductId, $optId);
                        if ($proVariantProperyId) {
                            $upData[]   =   [
                                                'id' => $proVariantProperyId,
                                                'mediaId' => $variantDataValue['medId']
                                            ];
                        }
                    }                    
                }
                if (!empty($upData)){
                    $this->productConfiguratorSettingRepo->update($upData, Context::createDefaultContext());
                }
            }
            /* UPDATE PRODUCT VARIANTS/SWATCH IMAGE */
        }
        /* VARIANTS */
    }

    private function uploadMedia($galleryData) {
        $pos        =   1;
        $result     =   [];
        $folderId   =   $this->importHelper->getProductFolderId();
        foreach ($galleryData as $galleryKey => $galleryValue) {
            $imgUrl    =   $galleryValue;
            $imgName   =   basename($galleryValue);
            $mediaId   =   $this->importHelper->uploadImageFromUrl($imgUrl, $folderId, $imgName);
            if (!empty($mediaId)){
                $result[]    =  [
                                    'mediaId' => $mediaId,
                                    'position' => $pos
                                ];
            }
            $pos++;
        }
        return $result;
    }

    private function uploadCover($imageUrl){
        $fileName   =   basename($imageUrl);
        $folderId   =   $this->importHelper->getProductFolderId();
        $mediaId    =   $this->importHelper->uploadImageFromUrl($imageUrl, $folderId, $fileName);

        if ($mediaId) {
            return ['mediaId' => $mediaId];
        }
    }

    private function createChildProduct($childProData, $parentProductId){
        $variantData        =       [];
        $context            =       Context::createDefaultContext();
        $optionsCreated     =       [];
        $optionsCreatedRaw  =       [];
        $sizeVal            =       $colorVal   =   '';
        $options            =       [];
        $colorOptId         =       $sizeOptId  =   '';
        $masterColorSize    =       $childProData['color_size'];

        $masterColorSizeS   =       explode('; ', $masterColorSize);
        $masterColorSizeSC  =       explode(' | ', $masterColorSizeS[1]);
        $sizeVal            =       trim($masterColorSizeSC[1]);
        $colorVal           =       trim($masterColorSizeSC[0]);

        if (!empty($colorVal)){
            $colorOptId         =       $this->importHelper->getPropertyOptionId($colorVal, 'Color');
            if (!empty($colorOptId)) {
                $options[]  =  ['id' => $colorOptId];
            }
        }

        if (!empty($sizeVal)){
            $sizeOptId  =  $this->importHelper->getPropertyOptionId($sizeVal, 'Size');
            if (!empty($sizeOptId)) {
                $options[]  =  ['id' => $sizeOptId];
            }
        }
        $chProductId    =   '';
        try {
            $chResultData    =   $this->createProduct($childProData, $parentProductId, $options, $colorOptId);
            $chProductId      =   $chResultData['productId'];
            $variantData        =   [];
            if (isset($chResultData['variantData'])){
                $variantData[]    =   $chResultData['variantData'];
            }

            if ($chProductId) {
                if (!empty($colorOptId) && !in_array($colorOptId, $optionsCreatedRaw)) {
                    $optionsCreatedRaw[]  =   $colorOptId;
                    $optionsCreated[]   =   ['optionId' => $colorOptId];
                }
                if (!empty($sizeOptId) && !in_array($sizeOptId, $optionsCreatedRaw)) {
                    $optionsCreatedRaw[]  =   $sizeOptId;
                    $optionsCreated[]   =   ['optionId' => $sizeOptId];
                }
            }
        } catch (\Exception $e) {
            echo "Unable to create variant for {$childProData['Hersteller_Code']}, error is {$e->getMessage()} ";
        }
        return ['optionsCreated' => $optionsCreated, 'variantData' => $variantData, 'childProductId'=>$chProductId];
    }

    public function setTierPricing(string $productId, $priceData)
    {
        if (!empty($priceData)) {
            $context        =   Context::createDefaultContext();
            $priceFullData  =   [];
            foreach ($priceData as $priceDataKey => $priceDataValue) {
                $netPrice       =   $this->getNetPriceFromGross((float)$priceDataValue['price']);
                $priceInData    =   [
                                        'id' => Uuid::randomHex(),
                                        'productId' => $productId,
                                        'ruleId' => $this->importHelper->getDefaultRuleId($context),
                                        'quantityStart' => (int)$priceDataValue['qty'],
                                        'price' => [
                                            [
                                                'currencyId' => Defaults::CURRENCY, 
                                                'gross' => (float)$priceDataValue['price'], 
                                                'net' => $netPrice, 
                                                'linked' => true
                                            ]
                                        ]
                                    ];

                if (isset($priceData[$priceDataKey+1])){
                    $priceInData['quantityEnd']     =   (int)$priceData[$priceDataKey+1]['qty']-1;
                }
                $priceFullData[]    =   $priceInData;
            }
            try {
                $this->productPriceRepository->create($priceFullData, $context);
            } catch (\Exception $e){
                echo "Issue in saving tear price " . $e->getMessage()." in product id {$productId}";
                //die("Issue in saving tear price");
            }
        }
    }

    private function createProduct($data, $parentProId = '', $options = [], $colorOptId = '') {

        $ifProExist       =   $this->importHelper->checkIfProductExist($data['Hersteller_Code']);
        $variantUpData    =   [];
        if ($ifProExist) {
            $productId    =   $ifProExist;
        } else {
            try {
                $productId          =   Uuid::randomHex();
                $mainDeData         =   $data;
                $salesChannelId     =   $this->importHelper->getDefaultSalesChannel();
                $taxId              =   $this->importHelper->getDefaultTaxId();
                $colorGrpId         =   $this->importHelper->getPropertyId("Color");
                $sizeGrpId          =   $this->importHelper->getPropertyId("Size");

                $isActive           =   0;

                //if (isset($data['ItemImage']) && !empty($data['ItemImage'])){
                    $isActive           =   1;
                //}

                $proData            =   [
                                            'id'    => $productId,
                                            'stock' => 10,
                                            'name'  => $mainDeData['Artikelname'],
                                            'isActive'  => $isActive,
                                            "productNumber" => $data['child_sku'],
                                            'description'   => $mainDeData['Artikelbeschreibung'],
                                            'taxId'         => $taxId,
                                            'maxPurchase'  => 10000,
                                            'visibilities'  => [
                                                [
                                                    'salesChannelId' => $salesChannelId,
                                                    'visibility' => 30 // all
                                                ]
                                            ],                                  
                                        ];

                if (!empty($parentProId)){
                    $proData['parentId']   =   $parentProId;
                }

                if (!empty($options)) {
                    $proData['options']    =   $options;   
                }

                /*$category   =   $additionalData['Category'];

                if (!empty($category)){
                    $categoryId     =   $this->importHelper->getCategoryByKey($category);
                }*/
                
                /* MANUFACTURER */
                if (isset($data['4 Brand']) && !empty($data['4 Brand'])){
                    $brand      =   $data['4 Brand'];
                    $brandId    =   $this->importHelper->getManufacturerId($brand);
                    if (!$brandId){
                        $proData['manufacturer']    =   ['name' => $brand];
                    } else {
                        $proData['manufacturerId']  =   $brandId;
                    }
                }            
                /* MANUFACTURER */


                /* UPLOAD MEDIA */
/*                $articleShortCode   =   $data['1 Article Number Short'];
                $coverImagePath     =   $articleShortCode.'/m_item_p_'.$data['2 Photo Number Short'].'_01.jpg';
                $coverImg   =   $this->uploadCover($coverImagePath);
                if ($coverImg) {
                    $proData['cover']    =  $coverImg;
                }*/
                /* UPLOAD MEDIA */

                /* Upload Gallery */

/*                $galleryImg     =   [];

                $galleryImg[]         =        $articleShortCode.'/m_model_p_'.$data['2 Photo Number Short'].'_01.jpg';

                for ($imgCount = 2; $imgCount <5; $imgCount++){
                    $galleryImg[]     =        $articleShortCode.'/m_item_p_'.$data['2 Photo Number Short'].'_0'.$imgCount.'.jpg';
                    $galleryImg[]     =        $articleShortCode.'/m_model_p_'.$data['2 Photo Number Short'].'_0'.$imgCount.'.jpg';
                }

                if (!empty($galleryImg)){
                    $parentGallredyData  =   $this->uploadMedia($galleryImg);
                    $proData['media']    =   $parentGallredyData;
                }*/
                /* Upload Gallery */

                /* Custom Fields */
                $customFields   =   [];
/*                $sheetName  =   'productsheet_'.$data['1 Article Number Short'].'_de.pdf';
                $proSheet   =   $this->importHelper->uploadProductSheet($sheetName);*/

                if (!empty($parentProId)) {
                    $customFieldPrefix  =   'products_additional_data_';
                    //$customFields[$customFieldPrefix.'fit']         =  $data['15 Cutting Style'];
                    $customFields[$customFieldPrefix.'gtin']        =  $data['EAN'];
                    $customFields[$customFieldPrefix.'gender']      =  $data['Geschlecht'];
                    //$customFields[$customFieldPrefix.'itemcode']    =  $data['ItemCode'];
                    //$customFields[$customFieldPrefix.'armlength']   =  $data['14 Sleeve Length'];
                    //$customFields[$customFieldPrefix.'areaweight']  =  $data['17 Fabric Weight'];
                    //$customFields[$customFieldPrefix.'harmonizedcode'] =  $data['HarmonizedCode'];
                    //$customFields[$customFieldPrefix.'iteminbag']   =  (int)$data['ItemsPerBag'];
                    $customFields[$customFieldPrefix.'iteminbox']   =  $data['Pieces_in_Karton'];
                    //$customFields[$customFieldPrefix.'prosheet']    =  $proSheet;
                    //$customFields[$customFieldPrefix.'supgln']      =  $data['22 Supplier Article Number'];
                    $customFields[$customFieldPrefix.'washtemp']    =  $data['Wash_Temp'];
                    //$customFields[$customFieldPrefix.'modelcode']   =  $data['ModelCode'];
                    $customFields[$customFieldPrefix.'colorcode']   =  $data['Farb_Code'];
                    $customFields[$customFieldPrefix.'country']   =  $data['Ursprung'];
                    $customFields[$customFieldPrefix.'material']   =  $data['Material'];
                    //$customFields['short_article_number']   =  $data['2 Photo Number Short'];
                    
                }

                if (!empty($customFields)){
                    $proData['customFields']    =   $customFields;
                }

                /* Custom Fields */

                if (isset($mainDeData['SEO Keywords']) && !empty($mainDeData['SEO Keywords'])){
                    $proData['metaKeywords']    =    explode(',', $mainDeData['SEO Keywords']);
                }

                $category   =   $mainDeData['Article Shop Group'];

                if (!empty($category)){
                    $categoryId     =   $this->importHelper->findCategory($category);
                    if (empty($categoryId)){
                        $categoryId     =   $this->importHelper->createCategory($category);
                    }
                    $proData['categories'] = [['id' => $categoryId]];
                }

                $tierPrices     =   [];
                
                $tierPriceQty   =   ['1','10','25','50','100','250','500','1000'];

                foreach ($tierPriceQty as $tierPriceQtyKey => $tierPriceQtyValue) {
                    $priceKey   =   "Price_Qty_".$tierPriceQtyValue;
                    if (isset($data[$priceKey]) && $data[$priceKey] > 0){
                        $tierPriceQtyValueQty   =   $tierPriceQtyValue;
                        $tierPrices[]           =   [
                                                        'qty' => $tierPriceQtyValueQty, 
                                                        'price' => str_replace(',', '.', $data[$priceKey])
                                                    ];
                    }
                }

                $context = Context::createDefaultContext();
                $this->productRepository->create([$proData], $context);
            } catch (\Exception $e) {
                //print_r($proData);
                echo $e->getMessage();
                //die("issue in product save");
            }
                        
            if (!empty($tierPrices)) {
                $this->setTierPricing($productId, $tierPrices);
            }            

            /* UPDATE VARIANT IMAGE FOR PRODUCT */
            if (!empty($productId) && !empty($colorOptId) && isset($coverImg['mediaId'])){

                $variantUpData    =     [
                                            'optId' =>  $colorOptId,
                                            'medId' =>  $coverImg['mediaId'],
                                        ];
            }
            /* UPDATE VARIANT IMAGE FOR PRODUCT */

        }

        if (!empty($parentProId)) {
            return  [
                        'productId'     =>  $productId,
                        'variantData'   =>  $variantUpData
                    ];
        } else {
            return  [
                        'productId'     =>  $productId
                    ];
        }
    }

    function getNetPriceFromGross(float $grossPrice): float {
        $taxRate    =   19.00;
        return round($grossPrice / (1 + ($taxRate / 100)), 2);
    }


    private function createParentProduct($data){
        $ifProExist       =   $this->importHelper->checkIfProductExist($data['Sku']);
        if ($ifProExist){
            $productId    =   $ifProExist;
        } else {
            $productId          =   Uuid::randomHex();
            $salesChannelId     =   $this->importHelper->getDefaultSalesChannel();
            $taxId              =   $this->importHelper->getDefaultTaxId();

            $proData            =   [
                                        'id'    => $productId,
                                        'stock' => 10,
                                        'name'  => $data['Name'],
                                        "productNumber" => $data['Sku'],
                                        'description'   => '',
                                        'taxId'         => $taxId,
                                        'maxPurchase'  => 10000,
                                        'visibilities'  => [
                                            [
                                                'salesChannelId' => $salesChannelId,
                                                'visibility' => 30 // all
                                            ]
                                        ],                                  
                                    ];


            /* MANUFACTURER */
            if (isset($data['brand']) && !empty($data['brand'])){
                $brand      =   $data['brand'];
                $brandId    =   $this->importHelper->getManufacturerId($brand);
                if (!$brandId){
                    $proData['manufacturer']    =   ['name' => $brand];
                } else {
                    $proData['manufacturerId']  =   $brandId;
                }
            }            
            /* MANUFACTURER */
            $netPrice              =    $this->getNetPriceFromGross((float)$data['Price']);
            $proData['price']      =    [
                                            [
                                                'currencyId' => Defaults::CURRENCY,
                                                'gross' => str_replace(',', '.', $data['Price']),
                                                'net' => $netPrice,
                                                'linked' => true,
                                            ]
                                        ];

            try {
                $context = Context::createDefaultContext();
                $this->productRepository->create([$proData], $context);
            } catch (\Exception $e) {
                print_r($proData);
                echo "Issue in parent product save ".$e->getMessage();
                //die("issue in parent product save");
            }
        }

        return $productId;
    }    
}