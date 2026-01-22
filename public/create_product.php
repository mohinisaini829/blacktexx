<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Shopware\Core\Kernel;
use Composer\Autoload\ClassLoader;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\DbalKernelPluginLoader;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Shopware\Core\Framework\ImportExport\Service\ImportExportService;
use Shopware\Core\Framework\ImportExport\Struct\Config;
use Shopware\Core\Framework\Context;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env.local');

$classLoader = require __DIR__ . '/../vendor/autoload.php';
 
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$debug  = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

$pluginLoader = null;
 
if (EnvironmentHelper::getVariable('COMPOSER_PLUGIN_LOADER', false)) {
    $pluginLoader = new ComposerPluginLoader($classLoader, null);
}

/* Product CSV ARRAY INDEX
0 => id  
1 => parent_id
2 => product_number
3 => active  
4 => stock   
5 => name    
6 => description 
7 => price_net   
8 => price_gross 
9 => purchase_prices_net
10 => purchase_prices_gross
11 => tax_id  
12 => tax_rate    
13 => tax_name    
14 => cover_media_id  
15 => cover_media_url 
16 => cover_media_title   
17 => cover_media_alt 
18 => manufacturer_id 
19 => manufacturer_name   
20 => categories  
21 => sales_channel  
22 => propertyIds 
23 => optionIds
24 => material
25 => gender
26 => sleeve_length
27 => article_number_short
28 => ean
29 => model_name
30 => item_in_box
31 => item_in_bag
32 => weight
33 => country
34 => washing_temp
35 => supplier
36 => cut
37 => febric_weight
38 => article_code
39 => gtin
40 => supplier_article
*/

$kernel = KernelFactory::create(
    environment: $appEnv,
    debug: $debug,
    classLoader: $classLoader,
    pluginLoader: $pluginLoader
);
 
$kernel->boot();

$container  = $kernel->getContainer();
$connection = $container->get(Connection::class);

// ---- Your CSV import logic starts here ----

$message             =  '';
$allowedTypes        =  ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
$propertyCsvHeader    = [
                            'id',
                            'color_hex_code',
                            'name',
                            'position',
                            'group_id',
                            'group_display_type',
                            'group_sorting_type',
                            'group_name',
                            'group_description',
                            'group_position',
                            'media_id',
                            'media_url',
                            'media_folder_id',
                            'media_type',
                            'media_title',
                            'media_alt'
                        ];

$salesChannelId          =  '0197e3dc1566708987331d818f8e1867';
$taxId                   =  '0197e3c80947729bbb9c9ca9f3238a05';
$taxRate                 =  '19';
$taxName                 =  'Standard rate';
$stock                   =  10;
$isActive                =  1;
$colorPropertyGrpId      =  '0198135f7a2f7600a44ed9ab388d112a';
$sizePropertyGrpId       =  '0198135ff7147512ab7153a50575bdc8';

$colorOptions   =   getPropertyOptions('Color', $container);
$sizeOptions    =   getPropertyOptions('Size', $container);
$categoryList   =   getCategories($container);
$manufacturerList   =   getManufacturers($container);
$parentIdSkuArr     =   [];
$categoryMapping    =   [];

if(!empty($_POST['vendorCategories']) && is_array($_POST['vendorCategories'])){
    foreach($_POST['vendorCategories'] as $key=>$cat){
        
        $categoryMapping[trim($cat)]    =   trim(isset($_POST['shopware_category'][$key]) ? $_POST['shopware_category'][$key] : '');
    }
}


if (isset($_POST['vendor']) && $_POST['vendor'] === 'ross') {

        // Accept temp_file from mapping form, or vendor_file from direct upload (if needed)
        $targetFile = null;
        if (!empty($_POST['temp_file'])) {
            $tempFile = basename($_POST['temp_file']);
            $tempDir = __DIR__ . '/uploads/temp/';
            $targetFile = $tempDir . $tempFile;
            if (!file_exists($targetFile)) {
                die('❌ Temporary file not found: ' . htmlspecialchars($tempFile));
            }
        } else if (!empty($_FILES['vendor_file']['tmp_name'])) {
            $fileTmp = $_FILES['vendor_file']['tmp_name'];
            $originalName = basename($_FILES['vendor_file']['name']);
            $tempDir = __DIR__ . '/uploads/temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
            $tempFile = uniqid('vendor_') . '_' . $originalName;
            move_uploaded_file($fileTmp, $tempDir . $tempFile);
            $targetFile = $tempDir . $tempFile;
        } else {
            die('❌ No file provided for ross vendor.');
        }

        // Only allow Excel/CSV files
        $fileType = mime_content_type($targetFile);
        if (!in_array($fileType, $allowedTypes) && !preg_match('/spreadsheet|excel|officedocument|csv/i', $fileType)) {
            die('Ross: Invalid file type. Please upload a CSV / XLS file.');
        }
        try {
            $spreadsheet = IOFactory::load($targetFile);

                $sheet      = $spreadsheet->getActiveSheet();
                $rowCount   = 1;
                $header     = [];
                $parentIds  = [];
                $productCsvData     =   [];
                $sizeProperyData     =   [];
                $colorProperyData     =   [];

                $productDefaultArray    =   [
                    0 => '',
                    1 => '',
                    2 => '',
                    3 => $isActive,
                    4 => $stock,
                    5 => '',
                    6 => '',
                    7 => '',
                    8 => '',
                    9 => '',
                    10 => '',
                    11 => $taxId,
                    12 => $taxRate,
                    13 => $taxName,
                    14 => '',
                    15 => '',
                    16 => '',
                    17 => '',
                    18 => '',
                    19 => '',
                    20 => '',
                    21 => $salesChannelId,
                    22 => '',
                    23 => '',
                    24 => '',
                    25 => '',
                    26 => '',
                    27 => '',
                    28 => '',
                    29 => '',
                    30 => '',
                    31 => '',
                    32 => '',
                    33 => '',
                    34 => '',
                    35 => '',
                    36 => '',
                    37 => '',
                    38 => '',
                    39 => '',
                    40 => ''
                ];

                $lastParent     =   $newCategory    =   $newBrands  =   $newBrandsNameArr  =   [];
                $rootCategory   =   getRootCategory();

                foreach ($sheet->getRowIterator() as $row) {
                    if (in_array($rowCount, [1,3,4,5])) {
                        $rowCount++;
                        continue;
                    }

                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $columnCount = 1;

                    foreach ($cellIterator as $cell) {
                        if ($rowCount === 2) {
                            if ($cell->getValue() === '' || empty($cell->getValue())) {
                                $header[] = "Unknown" . $columnCount;
                            } else {
                                $header[] = $cell->getValue();
                            }
                        } else {
                            $rowData[] = $cell->getCalculatedValue();
                        }
                        $columnCount++;
                    }

                    if ($rowCount === 2) {
                        $rowCount++;
                        continue;
                    }

                    $data = array_combine($header, $rowData);
                    $data = array_combine(
                        array_map('trim', array_keys($data)),
                        $data
                    );

                    if (empty($data['Article Number'])){
                        $rowCount++;
                        continue;
                    }

                    /* Add Parent Row */
                    $parentSku = $data['Master Article Number'] ?? null;

                    $brandId        =   '';
                    $brandName      =   $data['Brand'];

                    if (isset($manufacturerList[$brandName])) {
                        $brandId    =   $manufacturerList[$brandName];
                    } elseif (!empty($brandName) && !in_array($brandName, $newBrandsNameArr)) {
                        $newBrandsNameArr[]     =   $brandName;
                        $newBrands[]            =   ['name' => $brandName];
                    }

                    $categoryIds    =   '';
                    $categoryName   =   $data['Category Shop'];
                    if (!empty($categoryName)){
                        if (isset($categoryList[$categoryName])){
                            $categoryIds    =   $categoryList[$categoryName];
                        } else {
                            $categoryIds    =   Uuid::randomHex();
                            $newCategory[]  =   [
                                                    'id'       => $categoryIds,
                                                    'name'     => $categoryName,
                                                    'active'   => true,
                                                    'parentId' => $rootCategory
                                                ];
                            $categoryList[$categoryName]    =   $categoryIds;
                        }
                    }

                    $gender     =   $data['Gender/Sex'];
                    if (!empty($gender)){
                        $gender     = str_replace(";", "/", $gender);
                    }

                    $Supplier     =   $data['Supplier'];
                    if (!empty($Supplier)){
                        $Supplier     = str_replace(";", "|", $Supplier);
                    }

                    $partnerArticle     =   $data['Partner Article'];
                    if (!empty($partnerArticle)){
                        $partnerArticle     = str_replace(";", "|", $partnerArticle);
                    }

                    $description     =   $data['Web Shop Article Description'];
                    if (!empty($description)){
                        $description     = str_replace(";", "|", $description);
                    }

                    if ($parentSku && !in_array($parentSku, $parentIds, true)) {

                        $parentHexId        =   Uuid::randomHex();
                        $parentIdSkuArr[$parentSku]     =   $parentHexId;
                        $parentIds[]        =   $parentSku;
                        $proArr             =   $productDefaultArray;
                        $proArr[0]          =   $parentHexId;
                        $proArr[2]          =   $parentSku;
                        $proArr[5]          =   $data['Web Shop Article Name (Product Page, Listing)'] ?? '';
                        $proArr[6]          =   $description;
                        $proArr[19]         =   $brandName;
                        $proArr[18]         =   $brandId;
                        $proArr[20]         =   $categoryIds;
                        $proArr[7]          =   $data[1] ?? '';
                        $proArr[8]          =   $data[1] ?? '';
                        $proArr[25]         =   $gender;
                        $proArr[35]         =   $Supplier;
                        $productCsvData[]   =   $proArr;
                        $lastParent         =   $proArr;
                    }
                    /* Add Parent Row */

                    if (count($productCsvData) % 50 == 0){
                        $productCsvData[]     =   $lastParent;
                    }

                    // --- CHILD PRODUCT OPTIONS ---
                    $configOptions      =   $data['Master Field für Variant (Color-Size)'] ?? '';

                    $optIdArr           =   [];
                    if ($configOptions) {
                        $masterColorSizeS  = explode('; ', $configOptions);
                        if (isset($masterColorSizeS[1])) {
                            $masterColorSizeSC =    explode(' | ', $masterColorSizeS[1]);
                            $sizeVal           =    trim($masterColorSizeSC[1] ?? '');
                            $colorVal          =    trim($masterColorSizeSC[0] ?? '');
                            $sizeName          =    $colorName  =   '';
                            /* Write Size Property */

                            if (!empty($sizeVal)){
                                if (!isset($sizeOptions[$sizeVal])){
                                    $sizeProperyData[]  =   ['name' => $sizeVal];
                                    $sizeName           =   $sizeVal;
                                    $optIdArr[]         =   $sizeName;
                                } else {
                                    $optIdArr[]         =   $sizeOptions[$sizeVal];
                                }                                
                            }

                            /* Write Size Property */

                            /* Write Color Property */
                            if (!empty($colorVal)){
                                if (!isset($colorOptions[$colorVal])){
                                    $colorProperyData[]  =   ['name' => $colorVal];
                                    $colorName           =   $colorVal;
                                    $optIdArr[]          =   $colorName;
                                } else {
                                    $optIdArr[]         =   $colorOptions[$colorVal];
                                }
                            }
                            /* Write Color Property */
                        }
                    }

                    /* Add Child Row */
                    $optIdStr           =   implode('|', $optIdArr);

                    $proArr             =   $productDefaultArray;
                    $proArr[0]          =   Uuid::randomHex();
                    $proArr[1]          =   $parentIdSkuArr[$parentSku];
                    $proArr[2]          =   $data['Article Number'];
                    $proArr[5]          =   $data['Web Shop Article Name (Product Page, Listing)'] ?? '';
                    $proArr[6]          =   $description;
                    $proArr[7]          =   $data[1] ?? '';
                    $proArr[8]          =   $data[1] ?? '';
                    $proArr[18]         =   $brandId;
                    $proArr[19]         =   $brandName;
                    $proArr[20]         =   $categoryIds;
                    $proArr[23]         =   $optIdStr;
                    $proArr[24]         =   $data['Fabric'] ?? '';
                    $proArr[25]         =   $gender;
                    $proArr[26]         =   $data['Sleeve Length'] ?? '';
                    $proArr[27]         =   $data['Article Number Short'] ?? '';
                    $proArr[28]         =   $data['EAN/GTIN'] ?? '';
                    $proArr[29]         =   $data['Model Name'] ?? '';
                    $proArr[30]         =   $data['Quantity Box'] ?? '';
                    $proArr[31]         =   $data['Quantity Pack'] ?? '';
                    $proArr[32]         =   $data['Weight'] ?? '';
                    $proArr[33]         =   $data['Country of origin'] ?? '';
                    $proArr[34]         =   $data['Washing Temp'] ?? '';
                    $proArr[35]         =   $Supplier;
                    $proArr[36]         =   $data['Cut'] ?? '';
                    $proArr[37]         =   $data['Fabric Weight'] ?? '';
                    $proArr[38]         =   $data['Article Code'] ?? '';
                    $proArr[39]         =   $data['EAN/GTIN'] ?? '';
                    $proArr[40]         =   $partnerArticle;

                    if (!empty($colorName)) {
                        $proArr['color_opt_name']         =   $colorName;                        
                    }
                    if (!empty($sizeName)) {
                        $proArr['size_opt_name']         =   $sizeName;                        
                    }

                    $productCsvData[]   =   $proArr;
                    /* Add Child Row */

                    $rowCount++;
                }

                /* CREATE BRANDS/MANUFACTURER */
                if (!empty($newBrands)){
                    createManufacturers($newBrands, $container);
                }

                if (!empty($sizeProperyData)){
                    $sizeProperyData = array_map("unserialize", array_unique(array_map("serialize", $sizeProperyData)));
                    createPropertyOptions($sizePropertyGrpId, $sizeProperyData, $container);
                }

                if (!empty($colorProperyData)){
                    $colorProperyData = array_map("unserialize", array_unique(array_map("serialize", $colorProperyData)));
                    createPropertyOptions($colorPropertyGrpId, $colorProperyData, $container);
                }

                if (!empty($newCategory)){
                    createBulkCategories($newCategory, $container);
                }   
                createProductCsv($productCsvData, $container, $categoryMapping);

                exit;
            } catch (\Exception $e) {
                echo 'Error loading file: ', $e->getMessage();
            }
        
    
}else if(isset($_POST['vendor']) && $_POST['vendor'] === 'harko'){
    //die('kkkk');
    // Accept temp_file from mapping form, or vendor_file from direct upload (if needed)
    $targetFile = null;
    if (!empty($_POST['temp_file'])) {
        $tempFile = basename($_POST['temp_file']);
        $tempDir = __DIR__ . '/uploads/temp/';
        $targetFile = $tempDir . $tempFile;
        if (!file_exists($targetFile)) {
            die('❌ Temporary file not found: ' . htmlspecialchars($tempFile));
        }
    } else if (!empty($_FILES['vendor_file']['tmp_name'])) {
        $fileTmp = $_FILES['vendor_file']['tmp_name'];
        $originalName = basename($_FILES['vendor_file']['name']);
        $tempDir = __DIR__ . '/uploads/temp/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $tempFile = uniqid('vendor_') . '_' . $originalName;
        move_uploaded_file($fileTmp, $tempDir . $tempFile);
        $targetFile = $tempDir . $tempFile;
    } else {
        die('❌ No file provided for harko vendor.');
    }

    // Only allow Excel/CSV files
    $fileType = mime_content_type($targetFile);
    if (!in_array($fileType, $allowedTypes) && !preg_match('/spreadsheet|excel|officedocument|csv/i', $fileType)) {
        die('Harko: Invalid file type. Please upload a CSV / XLS file.');
    }
    try {
        $spreadsheet = IOFactory::load($targetFile);
        $sheet      = $spreadsheet->getActiveSheet();
        $rowCount   = 1;
        $header     = [];
        $parentIds  = [];
        $productCsvData     =   [];
        $sizeProperyData     =   [];
        $colorProperyData     =   [];

                $productDefaultArray    =   [
                                                0 => '',
                                                1 => '',
                                                2 => '',
                                                3 => $isActive,
                                                4 => $stock,
                                                5 => '',
                                                6 => '',
                                                7 => '',
                                                8 => '',
                                                9 => '',
                                                10 =>'',
                                                11 => $taxId,
                                                12 => $taxRate,
                                                13 => $taxName,
                                                14 => '',
                                                15 => '',
                                                16 => '',
                                                17 => '',
                                                18 => '',
                                                19 => '',
                                                20 => '',
                                                21 => $salesChannelId,
                                                22 => '',
                                                23 => '',
                                                24 => '',
                                                25 => '',
                                                26 => '',
                                                27 => '',
                                                28 => '',
                                                29 => '',
                                                30 => '',
                                                31 => '',
                                                32 => '',
                                                33 => '',
                                                34 => '',
                                                35 => '',
                                                36 => '',
                                                37 => '',
                                                38 => '',
                                                39 => '',
                                                40 => ''
                                            ];

                $lastParent     =   $newCategory    =   $newBrands  =   $newBrandsNameArr  =   [];
                $rootCategory   =   getRootCategory();

                foreach ($sheet->getRowIterator() as $row) {

                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $columnCount = 1;

                    foreach ($cellIterator as $cell) {
                        if ($rowCount === 1) {
                            if ($cell->getValue() === '' || empty($cell->getValue())) {
                                $header[] = "Unknown" . $columnCount;
                            } else {
                                $header[] = $cell->getValue();
                            }
                        } else {
                            $rowData[] = $cell->getCalculatedValue();
                        }
                        $columnCount++;
                    }

                    if ($rowCount === 1) {
                        $rowCount++;
                        continue;
                    }
                    $data = array_combine($header, $rowData);
                    $data = array_combine(
                        array_map('trim', array_keys($data)),
                        $data
                    );

                    if (empty($data['Article Number'])){
                        $rowCount++;
                        continue;
                    }

                    /* Add Parent Row */
                    $parentSku = $data['Master Article Number'] ?? null;

                    $brandId        =   '';
                    $brandName      =   $data['4 Brand'];

                    if (isset($manufacturerList[$brandName])) {
                        $brandId    =   $manufacturerList[$brandName];
                    } elseif (!empty($brandName) && !in_array($brandName, $newBrandsNameArr)) {
                        $newBrandsNameArr[]     =   $brandName;
                        $newBrands[]            =   ['name' => $brandName];
                    }

                    $categoryIds    =   '';
                    $categoryName   =   $data['Article Shop Group'];
                    if (!empty($categoryName)){
                        if (isset($categoryList[$categoryName])){
                            $categoryIds    =   $categoryList[$categoryName];
                        } else {
                            $categoryIds    =   Uuid::randomHex();
                            $newCategory[]  =   [
                                                    'id'       => $categoryIds,
                                                    'name'     => $categoryName,
                                                    'active'   => true,
                                                    'parentId' => $rootCategory
                                                ];
                            $categoryList[$categoryName]    =   $categoryIds;
                        }
                    }

                    $gender     =   $data['12 Sex'];
                    if (!empty($gender)){
                        $gender     = str_replace(";", "/", $gender);
                    }

                    //$Supplier     =   $data['Supplier'];
                    $Supplier     =   '';
                    if (!empty($Supplier)){
                        $Supplier     = str_replace(";", "|", $Supplier);
                    }
                    $partnerArticle     =    '';
                    //$partnerArticle     =   $data['Partner Article'];
                    if (!empty($partnerArticle)){
                        $partnerArticle     = str_replace(";", "|", $partnerArticle);
                    }

                    $description     =   $data['Shop Article Desription'];
                    if (!empty($description)){
                        $description     = str_replace(";", "|", $description);
                    }

                    if ($parentSku && !in_array($parentSku, $parentIds, true)) {

                        $parentHexId        =   Uuid::randomHex();
                        $parentIdSkuArr[$parentSku]     =   $parentHexId;
                        $parentIds[]        =   $parentSku;
                        $proArr             =   $productDefaultArray;
                        $proArr[0]          =   $parentHexId;
                        $proArr[2]          =   $parentSku;
                        $proArr[5]          =   $data['3 Article Name'] ?? '';
                        $proArr[6]          =   $description;
                        $proArr[19]         =   $brandName;
                        $proArr[18]         =   $brandId;
                        $proArr[20]         =   $categoryIds;
                        $proArr[7]          =   $data['Price 1'] ?? '';
                        $proArr[8]          =   $data['Price 1'] ?? '';
                        $proArr[25]         =   $gender;
                        $proArr[35]         =   $Supplier;
                        $productCsvData[]   =   $proArr;
                        $lastParent         =   $proArr;
                    }
                    /* Add Parent Row */

                    if (count($productCsvData) % 50 == 0){
                        $productCsvData[]     =   $lastParent;
                    }


                    // --- CHILD PRODUCT OPTIONS ---
                    //$configOptions      =   $data['Master Field für Variant (Color-Size)'] ?? '';

                    $configOptions      =   $data['Master Formular (master; 1; Color | Size)'] ?? '';
                    $str = $data['Master Formular (master; 1; Color | Size)'] ?? '';
                    //die('here111');

                    // step 1: explode by ";"
                    $parts = explode(";", $str);

                    // last part has "Farbe | Größe"
                    $lastPart = trim(end($parts));

                    // step 2: explode last part by "|"
                    $attributes = array_map('trim', explode("|", $lastPart));

                    $colorVal  = $attributes[0] ?? null; // "Farbe"
                    $sizeVal   = $attributes[1] ?? null; // "Größe"
                    $optIdArr           =   [];
                            if (!empty($sizeVal)){
                                if (!isset($sizeOptions[$sizeVal])){
                                    $sizeProperyData[]  =   ['name' => $sizeVal];
                                    $sizeName           =   $sizeVal;
                                    $optIdArr[]         =   $sizeName;
                                } else {
                                    $optIdArr[]         =   $sizeOptions[$sizeVal];
                                }                                
                            }

                            /* Write Size Property */

                            /* Write Color Property */
                            if (!empty($colorVal)){
                                if (!isset($colorOptions[$colorVal])){
                                    $colorProperyData[]  =   ['name' => $colorVal];
                                    $colorName           =   $colorVal;
                                    $optIdArr[]          =   $colorName;
                                } else {
                                    $optIdArr[]         =   $colorOptions[$colorVal];
                                }
                            }
                    
                    /* Add Child Row */
                    $optIdStr           =   implode('|', $optIdArr);

                    $proArr             =   $productDefaultArray;
                    $proArr[0]          =   Uuid::randomHex();
                    $proArr[1]          =   $parentIdSkuArr[$parentSku];
                    $proArr[2]          =   $data['Article Number'];
                    $proArr[5]          =   $data['ERP Article Name'] ?? '';
                    $proArr[6]          =   $description;
                    $proArr[7]          =   $data['Price 1'] ?? '';
                    $proArr[8]          =   $data['Price 1'] ?? '';
                    $proArr[18]         =   $brandId;
                    $proArr[19]         =   $brandName;
                    $proArr[20]         =   $categoryIds;
                    $proArr[23]         =   $optIdStr;
                    $proArr[24]         =   $data['24 Material Composition'] ?? '';
                    $proArr[25]         =   $gender;
                    $proArr[26]         =   $data['14 Sleeve Length'] ?? '';
                    $proArr[27]         =   $data['2 Photo Number Short'] ?? '';
                    $proArr[28]         =   $data['EAN/GTIN'] ?? '';
                    $proArr[29]         =   $data['2 Photo Number Short1'] ?? '';
                    $proArr[30]         =   $data['20 Pieces per Carton'] ?? '';
                    //$proArr[31]         =   $data['Quantity Pack'] ?? '1';
                    //$proArr[32]         =   $data['17 Fabric Weight1'] ?? '';
                    $proArr[33]         =   $data['7 Country of Origin'] ?? '';
                    $proArr[34]         =   $data['16 Washing Temperature'] ?? '';
                    $proArr[35]         =   $Supplier;
                    $proArr[36]         =   $data['15 Cutting Style'] ?? '';
                    $proArr[37]         =   $data['17 Fabric Weight'] ?? '';
                    $proArr[38]         =   $data['5. Color Code'] ?? '';
                    $proArr[39]         =   $data['EAN/GTIN'] ?? '';
                    $proArr[40]         =   $partnerArticle;
                    //print_r($proArr);die;
                    if (!empty($colorName)) {
                        $proArr['color_opt_name']         =   $colorName;                        
                    }
                    if (!empty($sizeName)) {
                        $proArr['size_opt_name']         =   $sizeName;                        
                    }

                    $productCsvData[]   =   $proArr;
                    /* Add Child Row */

                    $rowCount++;
                }

                /* CREATE BRANDS/MANUFACTURER */
                if (!empty($newBrands)){
                    createManufacturers($newBrands, $container);
                }

                if (!empty($sizeProperyData)){
                    $sizeProperyData = array_map("unserialize", array_unique(array_map("serialize", $sizeProperyData)));
                    createPropertyOptions($sizePropertyGrpId, $sizeProperyData, $container);
                }

                if (!empty($colorProperyData)){
                    $colorProperyData = array_map("unserialize", array_unique(array_map("serialize", $colorProperyData)));
                    createPropertyOptions($colorPropertyGrpId, $colorProperyData, $container);
                }

                if (!empty($newCategory)){
                    createBulkCategories($newCategory, $container);
                } 

                createProductCsv($productCsvData, $container, $categoryMapping);

                exit;
            } catch (\Exception $e) {
                echo 'Error loading file: ', $e->getMessage();
            }
        
    

}else if (isset($_POST['vendor']) && $_POST['vendor'] === 'newwave') {
    

    // Accept temp_file from mapping form, or vendor_file from direct upload
    $targetFile = null;
    if (!empty($_POST['temp_file'])) {
        $tempFile = basename($_POST['temp_file']);
        $tempDir = __DIR__ . '/uploads/temp/';
        $targetFile = $tempDir . $tempFile;
        if (!file_exists($targetFile)) {
            die('❌ Temporary file not found: ' . htmlspecialchars($tempFile));
        }
    } else if (!empty($_FILES['vendor_file']['tmp_name'])) {
        $fileTmp = $_FILES['vendor_file']['tmp_name'];
        $originalName = basename($_FILES['vendor_file']['name']);
        $tempDir = __DIR__ . '/uploads/temp/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $tempFile = uniqid('vendor_') . '_' . $originalName;
        move_uploaded_file($fileTmp, $tempDir . $tempFile);
        $targetFile = $tempDir . $tempFile;
    } else {
        die('❌ No file provided for newwave vendor.');
    }

    // Decode JSON
    $jsonData = json_decode(file_get_contents($targetFile), true);
    if (empty($jsonData['result']) || !is_array($jsonData['result'])) {
        die('❌ Invalid JSON format. Missing "result" array.');
    }


    // --- Prepare arrays ---
    $productCsvData   = [];
    $sizeProperyData  = [];
    $colorProperyData = [];
    $newBrands        = [];
    $newBrandsNameArr = [];
    $newCategory      = [];
    $parentIds        = [];
    $parentIdSkuArr   = [];

    $rootCategory = getRootCategory();

    $manufacturerList = $manufacturerList ?? [];
    $categoryList = $categoryList ?? [];

    // ✅ Ensure product default structure exists
    $productDefaultArray    =   [
        0 => '',
        1 => '',
        2 => '',
        3 => $isActive,
        4 => $stock,
        5 => '',
        6 => '',
        7 => '',
        8 => '',
        9 => '',
        10 => '',
        11 => $taxId,
        12 => $taxRate,
        13 => $taxName,
        14 => '',
        15 => '',
        16 => '',
        17 => '',
        18 => '',
        19 => '',
        20 => '',
        21 => $salesChannelId,
        22 => '',
        23 => '',
        24 => '',
        25 => '',
        26 => '',
        27 => '',
        28 => '',
        29 => '',
        30 => '',
        31 => '',
        32 => '',
        33 => '',
        34 => '',
        35 => '',
        36 => '',
        37 => '',
        38 => '',
        39 => '',
        40 => ''
    ];

    // 🎯 Target product number
    //$targetProductNumber = '0200905';

        foreach ($jsonData['result'] as $index => $product) {
            if (!is_array($product)) {
                echo "⚠️ Skipping item $index (not an array)<br>";
                continue;
            }

            $productNumber = isset($product['productNumber']) && is_string($product['productNumber'])
                ? trim($product['productNumber'])
                : '';

            // Parent-level fields
            $skuParent = $productNumber;
            // Safe product name
            $name = '';
            if (!empty($product['productName']) && is_array($product['productName'])) {
                // Check for the 'en' translation first (English)
                $name = trim((string)($product['productName']['en'] ?? ''));
                if (empty($name)) {
                    // Fallback: use the 'de' translation (German) if English name is missing
                    $name = trim((string)($product['productName']['de'] ?? ''));
                }
            }
            
            // If name is still empty, set a default name to avoid the "blank" error
            if (empty($name)) {
                $name = "Default Product Name";  // Replace with a fallback name
            }

            // Safe description
            $description = '';
            if (!empty($product['productText']) && is_array($product['productText'])) {
                $description = trim((string)($product['productText']['en'] ?? ''));
            }

            // Safe brand
            $brandName = trim((string)($product['productBrand'] ?? ''));

            // Safe category name
            $categoryName = '';
            if (
                !empty($product['productCategory']) &&
                isset($product['productCategory'][0]['translation']) &&
                is_array($product['productCategory'][0]['translation'])
            ) {
                $categoryName = trim((string)($product['productCategory'][0]['translation']['en'] ?? ''));
            }
            
            $imageUrlsParent = [];
            if (!empty($product['pictures']) && is_array($product['pictures'])) {
                foreach ($product['pictures'] as $pic) {
                    if (!empty($pic['imageUrl'])) $imageUrlsParent[] = $pic['imageUrl'];
                }
            }

            // collect property option candidates
            $colorsFound = []; // colorCode => ['name'=>..., 'pictures'=>[...] ]
            $sizesFound = [];  // sizeWebText => ['size_code'=>..., 'webtext'=>...]
            if (!empty($product['variations']) && is_array($product['variations'])) {
                foreach ($product['variations'] as $variation) {
                    $colorCode = isset($variation['itemColorCode']) ? trim($variation['itemColorCode']) : '';
                    //$colorName = $variation['itemColorName']['en'] ?? $variation['itemColorName']['de'] ?? '';
                    if ($colorCode === '') continue;
                     $colorName = '';
                    if (!empty($variation['itemColorName']['en'])) {
                        $colorName = $variation['itemColorName']['en'];
                        //die('jjjj');
                    } elseif (!empty($variation['itemColorName']['de'])) {
                        $colorName = $variation['itemColorName']['de'];
                    } else {
                        //die('oooo');
                        $colorName = $colorCode;
                    }
                    // if (!isset($colorsFound[$colorCode])) {
                    //     $colorsFound[$colorCode] = [
                    //         'name' => is_string($colorName) ? trim($colorName) : $colorCode,
                    //         'pictures' => []
                    //     ];
                    // }
                    if (!isset($colorsFound[$colorCode])) {
                        //echo $colorName;die;
                        $colorsFound[$colorCode] = [
                            'code'     => $colorCode,
                            'name'     => trim($colorName),
                            'pictures' => []
                        ];
                    }
                    if (!empty($variation['pictures']) && is_array($variation['pictures'])) {
                        foreach ($variation['pictures'] as $pv) {
                            if (!empty($pv['imageUrl']) && !in_array($pv['imageUrl'], $colorsFound[$colorCode]['pictures'])) {
                                $colorsFound[$colorCode]['pictures'][] = $pv['imageUrl'];
                            }
                        }
                    }
                }
            }

            // iterate SKUs to extract sizes and create variant rows
            $variantRows = [];
            if (!empty($product['skus']) && is_array($product['skus'])) {
                foreach ($product['skus'] as $skuItem) {
                    $variantSku = $skuItem['sku'] ?? '';
                    if ($variantSku === '') continue;

                    // size
                    $skuSizeWeb = $skuItem['skuSize']['webtext'] ?? '';
                    $skuSizeCode = $skuItem['skuSize']['size'] ?? '';
                    $sizesFound[$skuSizeWeb] = ['code' => $skuSizeCode, 'webtext' => $skuSizeWeb];

                    // color
                    $skuColor = $skuItem['skucolor'] ?? '';
                    if (!isset($colorsFound[$skuColor])) {
                        // fallback: try product-level filterColor or variation mapping
                        $colorsFound[$skuColor] = [
                            'name' => $skuColor,
                            'pictures' => []
                        ];
                    }

                    $availability = isset($skuItem['availability']) ? (int)$skuItem['availability'] : 0;
                    $price = $skuItem['retailPrice']['price'] ?? null;

                    // create variant CSV row based on your $productDefaultArray shape
                    $vRow = $productDefaultArray;
                    $vRow[0] = Uuid::randomHex();         // unique id for variant
                    $vRow[1] = $skuParent;         // unique id for variant
                    $vRow[2] = $variantSku;               // sku (index 2 used previously)
                    $vRow[3] = $isActive;                 // active flag (keep your existing variable)
                    $vRow[4] = $availability;             // stock
                    $vRow[5] = $name . " " . trim($skuItem['description'] ?? ''); // product name + small desc
                    $vRow[6] = $description;              // long description
                    // price mapping (you used 7/8/9/10 for net/gross etc in parent example)
                    if ($price !== null) {
                        $vRow[7] = $price; // net price (best effort). Adjust if you need gross/net calc
                        $vRow[8] = $price; // gross
                        $vRow[9] = $price;
                        $vRow[10] = $price;
                    }
                    // brand
                    if (isset($manufacturerList[$brandName])) {
                        $vRow[18] = $manufacturerList[$brandName];
                    } else {
                        $vRow[18] = ''; // will be created later with createManufacturers()
                        if (!in_array($brandName, $newBrandsNameArr) && $brandName !== '') {
                            $newBrandsNameArr[] = $brandName;
                            $newBrands[] = ['name' => $brandName];
                        }
                    }
                    $vRow[19] = $brandName;

                    // categories
                    if (!empty($categoryName)) {
                        if (isset($categoryList[$categoryName])) {
                            $vRow[20] = $categoryList[$categoryName];
                        } else {
                            $catId = Uuid::randomHex();
                            $newCategory[] = [
                                'id'       => $catId,
                                'name'     => $categoryName,
                                'active'   => true,
                                'parentId' => $rootCategory
                            ];
                            $categoryList[$categoryName] = $catId;
                            $vRow[20] = $catId;
                        }
                    }

                    // property option text field (you used index 23 for color|size)
                    //$vRow[23] = implode('|', array_filter([ $skuColor, $skuSizeWeb ]));
                    //$vRow[23] = $skuColor . "|" . $skuSizeWeb;
                    //$vRow[23] = $colorsFound[$colorCode]['name'] . "|" . $skuSizeWeb;
                    $colorNameForSku = $colorsFound[$skuColor]['name'] ?? $skuColor;
                    $vRow[23] = $colorNameForSku . "|" . $skuSizeWeb;

                    // keep friendly option names for createPropertyOptions usage later
                    $vRow['color_opt_name'] = $colorsFound[$skuColor]['name'] ?? $skuColor;
                    $vRow['size_opt_name']  = $skuSizeWeb;

                    // images: prefer variation color pictures, else SKU-level pictures (none here), else parent pictures
                    $imgList = [];
                    if (!empty($colorsFound[$skuColor]['pictures'])) {
                        $imgList = $colorsFound[$skuColor]['pictures'];
                    } elseif (!empty($imageUrlsParent)) {
                        $imgList = $imageUrlsParent;
                    }
                    //$vRow[41] = implode('|', $imgList); // you used 41 previously
                    if (!empty($imgList)) {
                        // Cover image
                        $vRow[15] = $imgList[0];   // cover_media_url
                        $vRow[16] = $name;         // cover_media_title (REQUIRED)
                        $vRow[17] = $name;         // cover_media_alt   (REQUIRED)

                        // Gallery images
                        //$vRow[41] = implode('|', array_unique($imgList));
                    }else {
                        $vRow[15] = '';  // cover_media_url
                        $vRow[16] = '';  // cover_media_title
                        $vRow[17] = '';  // cover_media_alt

                        // Optionally, you can also set gallery images to empty (if necessary)
                        //$vRow[41] = '';
                    }
                    //$vRow[42]=$name.'_526565';

                    // store mapping to set parent relation later
                    /*$vRow['parent_sku'] = $skuParent;
                    $vRow['is_variant'] = true;
                    $vRow['variant_color_code'] = $skuColor;
                    $vRow['variant_size_code'] = $skuSizeCode;
                    $vRow['variant_price'] = $price;*/

                    $variantRows[] = $vRow;
                }
            }

            // --- Create parent product row (single) ---
            $parentRow = $productDefaultArray;
            $parentRow[0] = Uuid::randomHex();       // parent id
            $parentRow[2] = $skuParent;              // parent sku
            $parentRow[3] = $isActive;               // active
            $parentRow[4] = 0;                       // parent stock typically 0 for configurator parent
            $parentRow[5] = $name;                   // name
            $parentRow[6] = $description;            // description

            // price on parent — leave empty or set to lowest variant price; here we set to lowest variant price if found
            $lowestPrice = null;
            foreach ($variantRows as $vr) {
                if (isset($vr['variant_price']) && $vr['variant_price'] !== null) {
                    if ($lowestPrice === null || $vr['variant_price'] < $lowestPrice) $lowestPrice = $vr['variant_price'];
                }
            }
            if ($lowestPrice !== null) {
                    $parentRow[7]  = $lowestPrice;
                    $parentRow[8]  = $lowestPrice;
                    $parentRow[9]  = $lowestPrice;
                    $parentRow[10] = $lowestPrice;
                } else {
                    // Force zero when no price found
                    $parentRow[7]  = 0;
                    $parentRow[8]  = 0;
                    $parentRow[9]  = 0;
                    $parentRow[10] = 0;
                }

            // brand & category on parent
            $parentRow[19] = $brandName;
            if (!empty($categoryName)) {
                $parentRow[20] = $categoryList[$categoryName] ?? ($newCategory[0]['id'] ?? '');
            }

            // images on parent
            //$parentRow[41] = implode('|', $imageUrlsParent);
            if (!empty($imageUrlsParent)) {
                // Handle Cover Image (MANDATORY 3 fields)
                // Ensure the first image URL is set for cover media
                $parentRow[15] = $imageUrlsParent[0] ?? '';   // cover_media_url
                $parentRow[16] = $name;                       // cover_media_title (REQUIRED)
                $parentRow[17] = $name;                       // cover_media_alt   (REQUIRED)

                // Handle Gallery Images
                // If there are multiple images in the array, join them with a pipe (|)
                //$parentRow[41] = implode('|', array_unique($imageUrlsParent)); // Gallery images (unique URLs)
            } else {
                // In case no images are available, leave the cover and gallery image fields blank (or set a default)
                $parentRow[15] = '';   // cover_media_url
                $parentRow[16] = '';   // cover_media_title
                $parentRow[17] = '';   // cover_media_alt

                // Optionally, clear gallery images if there are no cover images
                //$parentRow[41] = '';   // Gallery images (empty if no images available)
            }

            //$parentRow[42]=$name.'_526565';


            // Important: mark parent as configurable (if your CSV needs a special flag, set it here)
            //$parentRow['is_parent'] = true;
            // If your CSV needs "parentId" field for variants, we will set it on variant rows after we know parentRow[0]

            // --- Build property option arrays for createPropertyOptions() ---
            // color property options
            // Colors
            foreach ($colorsFound as $colorCode => $cdata) {
                $colorName = trim((string)($cdata['name'] ?? ''));
                if ($colorName === '') {
                    $colorName = 'Color ' . $colorCode; // fallback if name is empty
                }

                $colorProperyData[] = [
                    'id' => Uuid::randomHex(),
                    'name' => $colorName,
                    'code' => $colorCode
                ];
            }

            // Sizes
            foreach ($sizesFound as $webtext => $sdata) {
                $sizeName = trim((string)($sdata['webtext'] ?? ''));
                if ($sizeName === '') {
                    $sizeName = 'Size ' . $sdata['code']; // fallback if webtext is empty
                }

                $sizeProperyData[] = [
                    'id' => Uuid::randomHex(),
                    'name' => $sizeName,
                    'code' => $sdata['code']
                ];
            }


            // --- Add parent and variants to productCsvData with correct parent link ---
            // First push parent
            $productCsvData[] = $parentRow;
            $parentId = $parentRow[0];

            // push variants and attach parent id
            foreach ($variantRows as $vr) {
                // set variant parent linkage (adjust column index if your CSV expects a specific index)
                // I add explicit keys so your downstream createProductCsv can map them: 'parentId' => $parentId
                //$vr['parentId'] = $parentId;
                // If your CSV expects the parent ID at a certain numeric index instead, set that index too, e.g. $vr[14] = $parentId;
                $vr[14] = $parentId; // <-- adjust index if needed by your createProductCsv
                $vr[1] = $parentId; // <-- adjust index if needed by your createProductCsv

                // also set variant's display name to include options
                //$optTitle = trim( ($vr['color_opt_name'] ?? '') . ' ' . ($vr['size_opt_name'] ?? '') );
                $optTitle = trim($vr['color_opt_name'] ?? '');
                $vr[5] = $name . ' - ' . $optTitle;

                $productCsvData[] = $vr;
            }

            // only process the one product (keep your original break)
           // break;
        }

        // --- Existing flow: create manufacturers, properties, categories, then create CSV import ---
        // create manufacturers (new ones collected earlier)
        if (!empty($newBrands)) {
            createManufacturers($newBrands, $container);
        }

        // Remove duplicate property data (keep unique by name)
        if (!empty($sizeProperyData)) {
            // avoid duplicates by name
            $seen = [];
            $uniqueSizeProps = [];
            foreach ($sizeProperyData as $p) {
                if (!in_array($p['name'], $seen)) {
                    $seen[] = $p['name'];
                    $uniqueSizeProps[] = $p;
                }
            }
            $sizeProperyData = $uniqueSizeProps;
            // create size options under $sizePropertyGrpId (assumes it's set earlier)
            createPropertyOptions($sizePropertyGrpId, $sizeProperyData, $container);
        }

        if (!empty($colorProperyData)) {
            $seen = [];
            $uniqueColorProps = [];
            foreach ($colorProperyData as $p) {
                if (!in_array($p['name'], $seen)) {
                    $seen[] = $p['name'];
                    $uniqueColorProps[] = $p;
                }
            }
            $colorProperyData = $uniqueColorProps;
            createPropertyOptions($colorPropertyGrpId, $colorProperyData, $container);
        }

        if (!empty($newCategory)) {
            createBulkCategories($newCategory, $container);
        }

        if (!empty($productCsvData)) {
            // Hand-off to your CSV creator
            createProductCsv($productCsvData, $container, $categoryMapping);

            // Count parent and variants
            $parentCount  = 0;
            $variantCount = 0;
            $importedSkus = [];

            foreach ($productCsvData as $row) {
                // Parent row logic (adjust index if your structure is different)
                if (isset($row[2]) && !empty($row[2]) && isset($row[4]) && (int)$row[4] === 0) {
                    $parentCount++;
                    $importedSkus[] = $row[2];
                } else {
                    $variantCount++;
                }
            }

            echo "🎉 Imported {$parentCount} parent products and {$variantCount} variants successfully!<br>";
            echo "✅ Imported SKUs: " . implode(', ', array_unique($importedSkus));

        } else {
            echo "⚠️ No products found or no variants available.";
        }

        exit;
}




echo $message. " Last Message";

// --- Helper functions ---
function getPropertyOptions(string $propery, $container) {
    $criteria                       =   new Criteria();
    $context                        =   Context::createDefaultContext();
    $propertyGroupOptionRepository  =   $container->get('property_group_option.repository');

    $criteria->addAssociation('group');
    $criteria->addFilter(new EqualsFilter('group.name', $propery));

    $result = $propertyGroupOptionRepository->search($criteria, $context);

    $options    =   [];

    foreach ($result as $option) {
        $optName    =   $option->getName();
        $optId      =   $option->getId();
        $options[$optName]  =   $optId;
    }
    return $options;
}

function createPropertyOptions(string $properyId, $data, $container) {
    try {
        $criteria       =   new Criteria();
        $context        =   Context::createDefaultContext();
        $repository     =   $container->get('property_group.repository');
        $repository->update([
            [
                'id' => $properyId, 
                'options' => $data,
            ],
        ], $context);
    } catch (\Exception $e) {
        print_r($data); 
        echo 'Issue in creating property : ', $e->getMessage(); die;
    }
}

function getManufacturers($container) {
    $criteria       =   new Criteria();
    $context        =   Context::createDefaultContext();
    $repository     =   $container->get('product_manufacturer.repository');
    $result         =   $repository->search($criteria, $context);
    $options    =   [];
    foreach ($result as $option) {
        $optName    =   $option->getName();
        $optId      =   $option->getId();
        $options[$optName]  =   $optId;
    }
    return $options;
}

function createManufacturers($data, $container) {
    $criteria       =   new Criteria();
    $context        =   Context::createDefaultContext();
    $repository     =   $container->get('product_manufacturer.repository');
    $repository->create($data, $context);
}

function createProductCsv($csvData, $container,$categoryMapping)
{
    $date       = date("dmy");
    $vendor     = $_POST['vendor'] ?? 'vendor';
    $csvName    = $vendor . "_product_import_{$date}.csv";

    $manufacturerList = getManufacturers($container);
    $colorOptions     = getPropertyOptions('Color', $container);
    $sizeOptions      = getPropertyOptions('Size', $container);
    // Ensure categoryList is available for ID-to-name mapping
    $categoryList = getCategories($container);

    // Prevent duplicate mapping inserts
    $vendorCategoryMap = [];

    // ===================== INSERT / UPDATE CATEGORY MAPPING =====================
    try {
        // Native PDO
        $pdo = $container->get(\Doctrine\DBAL\Connection::class)->getNativeConnection();
        $vendorCode = $_POST['vendor'] ?? '';

        foreach ($csvData as $row) {
            $vendorCatId = trim((string)($row[20] ?? ''));
            // Get vendor category name from categoryList (id => name)
            $vendorCatName = '';
            if (!empty($vendorCatId)) {
                // Try to find the name by id (reverse lookup)
                $vendorCatName = array_search($vendorCatId, $categoryList, true);
                if ($vendorCatName === false) {
                    $vendorCatName = '';
                }
            }
            // Get shopware category id from categoryMapping
            $shopwareCatId = $categoryMapping[$vendorCatName] ?? '';
            if ($vendorCatName === '') continue;
            $shopwareCatId = $categoryMapping[$vendorCatName] ?? '';
            if ($shopwareCatId === '') continue;
            if (isset($vendorCategoryMap[$vendorCatName])) continue;
            $vendorCategoryMap[$vendorCatName] = true;
            // Check if mapping already exists (by vendor_code + vendor_cat)
            $checkSql = "SELECT id FROM custom_category_mapping WHERE vendor_code = ? AND vendor_cat = ? LIMIT 1";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$vendorCode, $vendorCatId]);
            $existing = $checkStmt->fetchColumn();

            if ($existing) {
                // Update existing row
                $sql = "UPDATE custom_category_mapping SET shopware_cat = ?, vendor_cat_name = ?, shopware_cat_name = ?, updated_at = ? WHERE id = ?";
                $params = [
                    $shopwareCatId,
                    $vendorCatName,
                    $shopwareCatName = $categoryList[$shopwareCatId] ?? '',
                    date('Y-m-d H:i:s'),
                    $existing
                ];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                // Insert new row with new binary id
                $binaryId = \Shopware\Core\Framework\Uuid\Uuid::randomBytes();
                $sql = "INSERT INTO custom_category_mapping (id, vendor_code, vendor_cat, shopware_cat, vendor_cat_name, shopware_cat_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $binaryId,
                    $vendorCode,
                    $vendorCatId,
                    $shopwareCatId,
                    $vendorCatName,
                    $shopwareCatName = $categoryList[$shopwareCatId] ?? '',
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
        }

    } catch (\Exception $e) {
        error_log($e->getMessage());
    }

    // ===================== CSV HEADER =====================
    $productCsvHeader = [
        'id','parent_id','product_number','active','stock','name','description',
        'price_net','price_gross','purchase_prices_net','purchase_prices_gross',
        'tax_id','tax_rate','tax_name','cover_media_id','cover_media_url',
        'cover_media_title','cover_media_alt','manufacturer_id','manufacturer_name',
        'categories','sales_channel','propertyIds','optionIds','material','gender',
        'sleeve_length','article_number_short','ean','model_name','item_in_box',
        'item_in_bag','weight','country','washing_temp','supplier','cut',
        'febric_weight','article_code','gtin','supplier_article',
        'vendor_category','shopware_category_id'
    ];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$csvName.'"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $productCsvHeader, ";");
    

    // ===================== CSV ROWS =====================
    foreach ($csvData as $row) {
        // Manufacturer ID resolve
        if (empty($row[18]) && !empty($row[19]) && isset($manufacturerList[$row[19]])) {
            $row[18] = $manufacturerList[$row[19]];
        }
        // Property mapping (Color | Size)
        if (isset($row['color_opt_name']) && isset($row[23])) {
            $colorName = trim($row['color_opt_name']);
            [$c, $s] = array_pad(explode('|', $row[23]), 2, '');
            $row[23] =
                ($colorOptions[$c] ?? '') . "|" .
                ($sizeOptions[$s] ?? '');
            unset($row['color_opt_name'], $row['size_opt_name']);
        }
        // Category mapping using $categoryMapping
        $vendorCatId = trim((string)($row[20] ?? ''));
        // Get vendor category name from categoryList (id => name)
        $vendorCatName = '';
        if (!empty($vendorCatId)) {
            // Try to find the name by id (reverse lookup)
            $vendorCatName = array_search($vendorCatId, $categoryList, true);
            if ($vendorCatName === false) {
                $vendorCatName = '';
            }
        }
        // Get shopware category id from categoryMapping
        $shopwareCatId = $categoryMapping[$vendorCatName] ?? '';
        // Append mapping columns: vendor category name, id, shopware category id
        //$row[] = $vendorCatName;
        $row[] = $vendorCatId;
        $row[] = $shopwareCatId;
        fputcsv($out, $row, ";");
    }

    fclose($out);
    exit;
}





function getRootCategory(){
    return '01980cc2b7f6704fafdb4844bd5c097f';
}

function getCategories($container){
    $parentCategory =   getRootCategory();
    $criteria       =   new Criteria();
    $criteria->addFilter(new EqualsFilter('parentId', $parentCategory));
    $context        =   Context::createDefaultContext();
    $repository     =   $container->get('category.repository');
    $result         =   $repository->search($criteria, $context);
    $data           =   [];
    foreach ($result as $option) {
        $name    =   $option->getName();
        $id      =   $option->getId();
        $data[$name]  =   $id;
    }
    return $data;
}

function createBulkCategories($data, $container)
{
    $criteria       =   new Criteria();
    $context        =   Context::createDefaultContext();
    $repository     =   $container->get('category.repository');
    $repository->create($data, $context);
}