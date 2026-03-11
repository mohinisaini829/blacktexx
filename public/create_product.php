<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('memory_limit', '4G');
set_time_limit(1800);

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
        $vendor = 'ross'; // Set vendor variable

        // Accept temp_file from mapping form, or vendor_file from direct upload (if needed)
        $targetFile = null;
        if (!empty($_POST['temp_file'])) {
            // Check if it's already a full path
            if (file_exists($_POST['temp_file'])) {
                $targetFile = $_POST['temp_file'];
            } else {
                // Legacy: basename only, look in uploads/temp/
                $tempFile = basename($_POST['temp_file']);
                $tempDir = __DIR__ . '/uploads/temp/';
                $targetFile = $tempDir . $tempFile;
                if (!file_exists($targetFile)) {
                    die('❌ Temporary file not found: ' . htmlspecialchars($tempFile));
                }
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
            $reader = IOFactory::createReaderForFile($targetFile);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($targetFile);

                $sheet      = $spreadsheet->getActiveSheet();
                $rowCount   = 1;
                $header     = [];
                $parentIds  = [];
                $productCsvData     =   [];
                $sizeProperyData     =   [];
                $colorProperyData     =   [];
                $parentSizeSpecPdfBySku = [];

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
                    $sizeSpecPdfUrl = buildRossSizeSpecPdfUrl($data);

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
                        error_log("[ROSS] Found category: '" . $categoryName . "'");
                        if (isset($categoryList[$categoryName])){
                            $categoryIds    =   $categoryList[$categoryName];
                            error_log("[ROSS] Category already exists with ID: " . $categoryIds);
                        } else {
                            $categoryIds    =   Uuid::randomHex();
                            $newCategory[]  =   [
                                                    'id'       => $categoryIds,
                                                    'name'     => $categoryName,
                                                    'active'   => true,
                                                    'parentId' => $rootCategory
                                                ];
                            error_log("[ROSS] New category created with ID: " . $categoryIds);
                            $categoryList[$categoryName]    =   $categoryIds;
                        }
                    } else {
                        error_log("[ROSS] No category found for this product (Category Shop is empty)");
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
                        $productName        =   trim($data['Web Shop Article Name (Product Page, Listing)'] ?? '');
                        if (empty($productName)) {
                            $productName    =   'Product ' . $parentSku;
                        }
                        $proArr[5]          =   $productName;
                        $proArr[6]          =   $description;
                        $proArr[19]         =   $brandName;
                        $proArr[18]         =   $brandId;
                        $proArr[20]         =   $categoryIds;
                        $grossPrice         =   floatval($data[1] ?? 0);
                        $proArr[7]          =   round($grossPrice / 1.19, 2);
                        $proArr[8]          =   $grossPrice;
                        $proArr[25]         =   $gender;
                        $proArr[35]         =   $Supplier;
                        $proArr['size_spec_pdf_url'] = $sizeSpecPdfUrl;
                        $productCsvData[]   =   $proArr;
                        $lastParent         =   $proArr;
                        $parentSizeSpecPdfBySku[$parentSku] = $sizeSpecPdfUrl;
                    }
                    /* Add Parent Row */

                    if (count($productCsvData) % 50 == 0){
                        $productCsvData[]     =   $lastParent;
                    }

                    // --- CHILD PRODUCT OPTIONS ---
                    $configOptions      =   $data['Master Field für Variant (Color-Size)'] ?? '';

                    $optIdArr           =   [];
                    $sizeName           =    $colorName  =   '';
                    
                    if ($configOptions) {
                        $masterColorSizeS  = explode('; ', $configOptions);
                        if (isset($masterColorSizeS[1])) {
                            $masterColorSizeSC =    explode(' | ', $masterColorSizeS[1]);
                            $sizeVal           =    trim($masterColorSizeSC[1] ?? '');
                            $colorVal          =    trim($masterColorSizeSC[0] ?? '');
                            
                            /* Write Size Property */

                            if (!empty($sizeVal)){
                                $sizeName = $sizeVal;  // Store actual size name
                                if (!isset($sizeOptions[$sizeVal])){
                                    $sizeProperyData[]  =   ['name' => $sizeVal];
                                    $optIdArr[]         =   $sizeVal;
                                } else {
                                    $optIdArr[]         =   $sizeOptions[$sizeVal];
                                }                                
                            }

                            /* Write Size Property */

                            /* Write Color Property */
                            if (!empty($colorVal)){
                                $colorName = $colorVal;  // Store actual color name
                                if (!isset($colorOptions[$colorVal])){
                                    $colorProperyData[]  =   ['name' => $colorVal];
                                    $optIdArr[]          =   $colorVal;
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
                    
                    // Set variant name from Color - Size extracted from Master Field
                    $variantNameParts = array_filter([$colorName, $sizeName]);
                    if (!empty($variantNameParts)) {
                        $proArr[5] = implode(' - ', $variantNameParts);  // e.g., "Black - XL"
                    } else {
                        $productName = trim($data['Web Shop Article Name (Product Page, Listing)'] ?? '');
                        $proArr[5] = !empty($productName) ? $productName : 'Product ' . $data['Article Number'];
                    }
                    
                    $proArr[6]          =   $description;
                    $grossPrice         =   floatval($data[1] ?? 0);
                    $proArr[7]          =   round($grossPrice / 1.19, 2);
                    $proArr[8]          =   $grossPrice;
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
                    $proArr['size_spec_pdf_url']         =   '';

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
                    error_log("[ROSS] Creating " . count($newCategory) . " new categories");
                    foreach ($newCategory as $cat) {
                        error_log("[ROSS] Category: " . $cat['name']);
                    }
                    createBulkCategories($newCategory, $container);
                } else {
                    error_log("[ROSS] No new categories to create (newCategory is empty)");
                }
                $result = createProductCsv($productCsvData, $container, $categoryMapping);
                
                // Show queue processing UI inline ONLY if not called from import_processor
                if (isset($result['jobId']) && !defined('IMPORT_PROCESSOR_MODE')) {
                    showQueueProcessingUI($result['jobId'], $vendor, basename($result['path']), count($productCsvData));
                    exit;
                }
                
                // Output CSV path for import_processor to capture
                if (defined('IMPORT_PROCESSOR_MODE') && isset($result['path'])) {
                    echo $result['path'];
                }
                
                if (!defined('IMPORT_PROCESSOR_MODE')) {
                    exit;
                }
            } catch (\Exception $e) {
                echo 'Error loading file: ', $e->getMessage();
            }
        
    
}else if(isset($_POST['vendor']) && $_POST['vendor'] === 'harko'){
    $vendor = 'harko'; // Set vendor variable
    // Accept temp_file from mapping form, or vendor_file from direct upload (if needed)
    $targetFile = null;
    if (!empty($_POST['temp_file'])) {
        // Check if it's already a full path
        if (file_exists($_POST['temp_file'])) {
            $targetFile = $_POST['temp_file'];
        } else {
            // Legacy: basename only, look in uploads/temp/
            $tempFile = basename($_POST['temp_file']);
            $tempDir = __DIR__ . '/uploads/temp/';
            $targetFile = $tempDir . $tempFile;
            if (!file_exists($targetFile)) {
                die('❌ Temporary file not found: ' . htmlspecialchars($tempFile));
            }
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
        $reader = IOFactory::createReaderForFile($targetFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($targetFile);
        $sheet      = $spreadsheet->getActiveSheet();
        $rowCount   = 1;
        $header     = [];
        $parentIds  = [];
        $productCsvData     =   [];
        $sizeProperyData     =   [];
        $colorProperyData     =   [];
        $colorOptionsLower   =   [];
        $sizeOptionsLower    =   [];
        $colorNameByLower    =   [];
        $sizeNameByLower     =   [];
        $colorKeysByLength   =   [];
        $sizeKeysByLength    =   [];

        foreach ($colorOptions as $name => $id) {
            $colorOptionsLower[mb_strtolower((string)$name, 'UTF-8')] = $id;
            $colorNameByLower[mb_strtolower((string)$name, 'UTF-8')] = (string)$name;
        }
        foreach ($sizeOptions as $name => $id) {
            $sizeOptionsLower[mb_strtolower((string)$name, 'UTF-8')] = $id;
            $sizeNameByLower[mb_strtolower((string)$name, 'UTF-8')] = (string)$name;
        }
        $colorKeysByLength = array_keys($colorNameByLower);
        usort($colorKeysByLength, function ($a, $b) {
            return mb_strlen((string)$b, 'UTF-8') <=> mb_strlen((string)$a, 'UTF-8');
        });
        $sizeKeysByLength = array_keys($sizeNameByLower);
        usort($sizeKeysByLength, function ($a, $b) {
            return mb_strlen((string)$b, 'UTF-8') <=> mb_strlen((string)$a, 'UTF-8');
        });

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
                        $productName        =   trim($data['3 Article Name'] ?? '');
                        if (empty($productName)) {
                            $productName    =   'Product ' . $parentSku;
                        }
                        $proArr[5]          =   $productName;
                        $proArr[6]          =   $description;
                        $proArr[19]         =   $brandName;
                        $proArr[18]         =   $brandId;
                        $proArr[20]         =   $categoryIds;
                        $grossPrice         =   floatval($data['Price 1'] ?? 0);
                        $proArr[7]          =   round($grossPrice / 1.19, 2);
                        $proArr[8]          =   $grossPrice;
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
                    $configOptions      =   $data['Master Formular (master; 1; Color | Size)'] ?? '';

                    $optIdArr           =   [];
                    $colorVal           =   trim((string)($data['4 Color'] ?? ''));
                    $sizeVal            =   trim((string)($data['11 Size'] ?? ''));
                    if ($colorVal === '') {
                        $colorVal = trim((string)($data['19 Color Group'] ?? ''));
                    }
                    if ($sizeVal === '') {
                        $sizeVal = trim((string)($data['Size'] ?? ''));
                    }
                    $combined           =   '';
                    
                    if ($configOptions && ($colorVal === '' || $sizeVal === '')) {
                        // Format: "master; variant_number; ColorValue | SizeValue"
                        // Example: "master; 1; kiwi | XS"
                        //          [0]     [1]           [2]
                        $masterColorSizeS  = explode('; ', $configOptions);
                        if (isset($masterColorSizeS[2])) {
                            $masterColorSizeSC =    explode(' | ', $masterColorSizeS[2]);
                            $colorVal          =    trim($masterColorSizeSC[0] ?? '');
                            $sizeVal           =    trim($masterColorSizeSC[1] ?? '');
                        }
                        if ($colorVal === '' || $sizeVal === '') {
                            $combined = trim((string)($masterColorSizeS[2] ?? $masterColorSizeS[1] ?? $masterColorSizeS[0] ?? ''));
                            if ($combined !== '') {
                                if (strpos($combined, ' | ') !== false) {
                                    [$cTmp, $sTmp] = array_pad(explode(' | ', $combined, 2), 2, '');
                                    $colorVal = $colorVal !== '' ? $colorVal : trim($cTmp);
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : trim($sTmp);
                                } elseif (strpos($combined, ' / ') !== false) {
                                    [$cTmp, $sTmp] = array_pad(explode(' / ', $combined, 2), 2, '');
                                    $colorVal = $colorVal !== '' ? $colorVal : trim($cTmp);
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : trim($sTmp);
                                } elseif (strpos($combined, ' - ') !== false) {
                                    [$cTmp, $sTmp] = array_pad(explode(' - ', $combined, 2), 2, '');
                                    $colorVal = $colorVal !== '' ? $colorVal : trim($cTmp);
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : trim($sTmp);
                                }
                            }
                        }
                        if (($colorVal === '' || $sizeVal === '') && !empty($combined)) {
                            $combinedLower = mb_strtolower($combined, 'UTF-8');
                            foreach ($sizeNameByLower as $sizeLower => $sizeOriginal) {
                                if ($sizeLower === '') {
                                    continue;
                                }
                                $sizeLen = mb_strlen($sizeLower, 'UTF-8');
                                if (mb_substr($combinedLower, -$sizeLen, null, 'UTF-8') === $sizeLower) {
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : $sizeOriginal;
                                    $colorPart = trim(mb_substr($combined, 0, mb_strlen($combined, 'UTF-8') - mb_strlen($sizeOriginal, 'UTF-8'), 'UTF-8'));
                                    $colorPart = trim(preg_replace('/[\s\-\/|]+$/u', '', $colorPart));
                                    $colorVal = $colorVal !== '' ? $colorVal : $colorPart;
                                    break;
                                }
                            }
                        }
                    }
                    if ($colorVal === '' || $sizeVal === '') {
                        $colorNameCandidate = '';
                        $colorCodeCandidate = '';
                        $sizeNameCandidate = '';
                        $sizeCodeCandidate = '';

                        foreach ($data as $key => $value) {
                            $keyLower = mb_strtolower((string)$key, 'UTF-8');
                            $keyNorm = preg_replace('/[^a-z0-9]+/u', '', $keyLower);
                            $val = trim((string)$value);
                            if ($val === '') {
                                continue;
                            }

                            $isColorKey = (bool)preg_match('/(color|colour|farbe)/', $keyNorm);
                            $isSizeKey = (bool)preg_match('/(size|groesse|grosse|grösse|größe)/u', $keyNorm);

                            if ($isColorKey) {
                                if (preg_match('/(name|bezeichnung)/', $keyNorm)) {
                                    $colorNameCandidate = $val;
                                } elseif (preg_match('/(code|nr|no|id)/', $keyNorm)) {
                                    $colorCodeCandidate = $val;
                                } elseif ($colorNameCandidate === '') {
                                    $colorNameCandidate = $val;
                                }
                            }

                            if ($isSizeKey) {
                                if (preg_match('/(name|bezeichnung)/', $keyNorm)) {
                                    $sizeNameCandidate = $val;
                                } elseif (preg_match('/(code|nr|no|id)/', $keyNorm)) {
                                    $sizeCodeCandidate = $val;
                                } elseif ($sizeNameCandidate === '') {
                                    $sizeNameCandidate = $val;
                                }
                            }
                        }

                        if ($colorVal === '' && $colorNameCandidate !== '') {
                            $colorVal = $colorNameCandidate;
                        } elseif ($colorVal === '' && $colorCodeCandidate !== '') {
                            $colorVal = $colorCodeCandidate;
                        }

                        if ($sizeVal === '' && $sizeNameCandidate !== '') {
                            $sizeVal = $sizeNameCandidate;
                        } elseif ($sizeVal === '' && $sizeCodeCandidate !== '') {
                            $sizeVal = $sizeCodeCandidate;
                        }
                    }
                    if ($colorVal === '' || $sizeVal === '') {
                        foreach ($data as $key => $value) {
                            $keyLower = mb_strtolower((string)$key, 'UTF-8');
                            $keyNorm = preg_replace('/[^a-z0-9]+/u', '', $keyLower);
                            $val = trim((string)$value);
                            if ($val === '') {
                                continue;
                            }

                            $isColorKey = (bool)preg_match('/(color|colour|farbe)/', $keyNorm);
                            $isSizeKey = (bool)preg_match('/(size|groesse|grosse|grösse|größe)/u', $keyNorm);

                            if (($colorVal === '' || $sizeVal === '') && $isColorKey && $isSizeKey) {
                                $combined = $combined !== '' ? $combined : $val;
                                if (strpos($combined, ' | ') !== false) {
                                    [$cTmp, $sTmp] = array_pad(explode(' | ', $combined, 2), 2, '');
                                    $colorVal = $colorVal !== '' ? $colorVal : trim($cTmp);
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : trim($sTmp);
                                } elseif (strpos($combined, ' / ') !== false) {
                                    [$cTmp, $sTmp] = array_pad(explode(' / ', $combined, 2), 2, '');
                                    $colorVal = $colorVal !== '' ? $colorVal : trim($cTmp);
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : trim($sTmp);
                                } elseif (strpos($combined, ' - ') !== false) {
                                    [$cTmp, $sTmp] = array_pad(explode(' - ', $combined, 2), 2, '');
                                    $colorVal = $colorVal !== '' ? $colorVal : trim($cTmp);
                                    $sizeVal = $sizeVal !== '' ? $sizeVal : trim($sTmp);
                                } else {
                                    $combinedLower = mb_strtolower($combined, 'UTF-8');
                                    foreach ($sizeNameByLower as $sizeLower => $sizeOriginal) {
                                        if ($sizeLower === '') {
                                            continue;
                                        }
                                        $sizeLen = mb_strlen($sizeLower, 'UTF-8');
                                        if (mb_substr($combinedLower, -$sizeLen, null, 'UTF-8') === $sizeLower) {
                                            $sizeVal = $sizeVal !== '' ? $sizeVal : $sizeOriginal;
                                            $colorPart = trim(mb_substr($combined, 0, mb_strlen($combined, 'UTF-8') - mb_strlen($sizeOriginal, 'UTF-8'), 'UTF-8'));
                                            $colorPart = trim(preg_replace('/[\s\-\/|]+$/u', '', $colorPart));
                                            $colorVal = $colorVal !== '' ? $colorVal : $colorPart;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                if ($colorVal === '' && $isColorKey) {
                                    $colorVal = $val;
                                }
                                if ($sizeVal === '' && $isSizeKey) {
                                    $sizeVal = $val;
                                }
                            }

                            if ($colorVal !== '' && $sizeVal !== '') {
                                break;
                            }
                        }
                    }
                    if ($colorVal === '' || $sizeVal === '') {
                        $sizeTokens = ['7XL','6XL','5XL','4XL','3XL','XXXL','XXL','XL','L','M','S','XS','XXS'];
                        foreach ($data as $value) {
                            $val = trim((string)$value);
                            if ($val === '') {
                                continue;
                            }
                            $valLower = mb_strtolower($val, 'UTF-8');

                            if ($sizeVal === '') {
                                foreach ($sizeTokens as $token) {
                                    if (preg_match('/(^|\b|\s)'.preg_quote(mb_strtolower($token, 'UTF-8'), '/').'(\b|\s|$)/u', $valLower)) {
                                        $sizeVal = $token;
                                        break;
                                    }
                                }
                                if ($sizeVal === '') {
                                    foreach ($sizeKeysByLength as $sizeLower) {
                                        if ($sizeLower === '') {
                                            continue;
                                        }
                                        if (preg_match('/(^|\b|\s)'.preg_quote($sizeLower, '/').'(\b|\s|$)/u', $valLower)) {
                                            $sizeVal = $sizeNameByLower[$sizeLower] ?? $sizeLower;
                                            break;
                                        }
                                    }
                                }
                            }

                            if ($colorVal === '') {
                                foreach ($colorKeysByLength as $colorLower) {
                                    if ($colorLower === '') {
                                        continue;
                                    }
                                    if (mb_stripos($valLower, $colorLower, 0, 'UTF-8') !== false) {
                                        $colorVal = $colorNameByLower[$colorLower] ?? $colorLower;
                                        break;
                                    }
                                }
                            }

                            if ($colorVal !== '' && $sizeVal !== '') {
                                break;
                            }
                        }
                    }
                    $colorName          =   ''; // Initialize color name variable
                    $sizeName           =   ''; // Initialize size name variable

                            if (!empty($sizeVal)){
                                $sizeName = (string)$sizeVal; // Always store size name for display
                                $sizeKey  = mb_strtolower($sizeName, 'UTF-8');
                                if (!isset($sizeOptionsLower[$sizeKey])){
                                    $newSizeId = Uuid::randomHex();
                                    $sizeProperyData[]  =   ['id' => $newSizeId, 'name' => $sizeName];
                                    $sizeOptions[$sizeName] = $newSizeId;
                                    $sizeOptionsLower[$sizeKey] = $newSizeId;
                                }
                                $optIdArr[] = $sizeOptionsLower[$sizeKey];
                            }

                            /* Write Size Property */

                            /* Write Color Property */
                            if (!empty($colorVal)){
                                $colorName = (string)$colorVal; // Always store color name for display
                                $colorKey  = mb_strtolower($colorName, 'UTF-8');
                                if (!isset($colorOptionsLower[$colorKey])){
                                    $newColorId = Uuid::randomHex();
                                    $colorProperyData[]  =   ['id' => $newColorId, 'name' => $colorName];
                                    $colorOptions[$colorName] = $newColorId;
                                    $colorOptionsLower[$colorKey] = $newColorId;
                                }
                                $optIdArr[] = $colorOptionsLower[$colorKey];
                            }
                    
                    /* Add Child Row */
                    $optIdStr           =   implode('|', $optIdArr);

                    $proArr             =   $productDefaultArray;
                    $proArr[0]          =   Uuid::randomHex();
                    $proArr[1]          =   $parentIdSkuArr[$parentSku];
                    $proArr[2]          =   $data['Article Number'];
                    // Use 3 Article Name for consistency with parent (base name only)
                    $productName        =   trim($data['3 Article Name'] ?? '');
                    if (empty($productName)) {
                        $productName    =   'Product ' . $data['Article Number'];
                    }
                    $proArr[5]          =   $productName;
                    $proArr[6]          =   $description;
                    $grossPrice         =   floatval($data['Price 1'] ?? 0);
                    $proArr[7]          =   round($grossPrice / 1.19, 2);
                    $proArr[8]          =   $grossPrice;
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
                    //print_r($productCsvData);die;
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
                    error_log("[HARKO] Creating " . count($newCategory) . " new categories");
                    foreach ($newCategory as $cat) {
                        error_log("[HARKO] Category: " . $cat['name']);
                    }
                    createBulkCategories($newCategory, $container);
                } else {
                    error_log("[HARKO] No new categories to create (newCategory is empty)");
                }

                $result = createProductCsv($productCsvData, $container, $categoryMapping);
                
                // Show queue processing UI inline ONLY if not called from import_processor
                if (isset($result['jobId']) && !defined('IMPORT_PROCESSOR_MODE')) {
                    showQueueProcessingUI($result['jobId'], $vendor, basename($result['path']), count($productCsvData));
                    exit;
                }
                
                // Output CSV path for import_processor to capture
                if (defined('IMPORT_PROCESSOR_MODE') && isset($result['path'])) {
                    echo $result['path'];
                }
                
                if (!defined('IMPORT_PROCESSOR_MODE')) {
                    exit;
                }
            } catch (\Exception $e) {
                echo 'Error loading file: ', $e->getMessage();
            }
        
    

}else if (isset($_POST['vendor']) && $_POST['vendor'] === 'newwave') {
    $vendor = 'newwave'; // Set vendor variable
    // Accept temp_file from mapping form, or vendor_file from direct upload
    $targetFile = null;
    if (!empty($_POST['temp_file'])) {
        // Check if it's already a full path
        if (file_exists($_POST['temp_file'])) {
            $targetFile = $_POST['temp_file'];
        } else {
            // Legacy: basename only, look in uploads/temp/
            $tempFile = basename($_POST['temp_file']);
            $tempDir = __DIR__ . '/uploads/temp/';
            $targetFile = $tempDir . $tempFile;
            if (!file_exists($targetFile)) {
                die('❌ Temporary file not found: ' . htmlspecialchars($tempFile));
            }
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
                $name = 'Product ' . $productNumber;  // Use product number as fallback
            }

            // Safe description
            $description = '';
            if (!empty($product['productText']) && is_array($product['productText'])) {
                $description = trim((string)($product['productText']['en'] ?? ''));
            }

            // Safe brand
            $brandName = trim((string)($product['productBrand'] ?? ''));

            // Safe category name - extract from productCategory nested JSON structure
            $categoryName = '';
            if (!empty($product['productCategory']) && is_array($product['productCategory'])) {
                $firstCategory = $product['productCategory'][0];
                
                // Try English translation first
                if (isset($firstCategory['translation']['en'])) {
                    $categoryName = trim((string)$firstCategory['translation']['en']);
                } 
                // Fallback to key field
                elseif (isset($firstCategory['key'])) {
                    $categoryName = trim((string)$firstCategory['key']);
                }
                // Fallback to any available translation (German first, then any)
                elseif (isset($firstCategory['translation']['de'])) {
                    $categoryName = trim((string)$firstCategory['translation']['de']);
                }
                elseif (is_array($firstCategory['translation'])) {
                    $translations = array_filter($firstCategory['translation'], function($v) {
                        return is_string($v) && !empty($v);
                    });
                    if (!empty($translations)) {
                        $categoryName = trim((string)current($translations));
                    }
                }
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
                    
                    // DEBUG: Log price data
                    if ($variantSku === 'HK0105001003') {
                        error_log("DEBUG Price for HK0105001003: price=$price, type=" . gettype($price));
                    }

                    // create variant CSV row based on your $productDefaultArray shape
                    $vRow = $productDefaultArray;
                    $vRow[0] = Uuid::randomHex();         // unique id for variant
                    $vRow[1] = $skuParent;         // unique id for variant
                    $vRow[2] = $variantSku;               // sku (index 2 used previously)
                    $vRow[3] = $isActive;                 // active flag (keep your existing variable)
                    $vRow[4] = $availability;             // stock
                    
                    // Set variant name immediately with color and size
                    $colorNameForVariant = $colorsFound[$skuColor]['name'] ?? $skuColor;
                    $sizeNameForVariant = $skuSizeWeb;
                    $variantNameParts = array_filter([$colorNameForVariant, $sizeNameForVariant]);
                    $vRow[5] = !empty($variantNameParts) ? implode(' - ', $variantNameParts) : $name;
                    
                    $vRow[6] = $description;              // long description
                    // price mapping (you used 7/8/9/10 for net/gross etc in parent example)
                    if ($price !== null) {
                        $grossPrice = floatval($price);
                        $netPrice = round($grossPrice / 1.19, 2); // 19% tax removed
                        $vRow[7] = $netPrice;    // net price
                        $vRow[8] = $grossPrice;  // gross price
                        $vRow[9] = $netPrice;    // purchase price net
                        $vRow[10] = $grossPrice; // purchase price gross
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
                        $vRow[14] = '';          // cover_media_id - keep blank for newwave
                        $vRow[15] = "";   // cover_media_url
                        $vRow[16] = "";         // cover_media_title (REQUIRED)
                        $vRow[17] = "";         // cover_media_alt   (REQUIRED)

                        // Gallery images
                        //$vRow[41] = implode('|', array_unique($imgList));
                    }else {
                        $vRow[14] = '';  // cover_media_id - keep blank for newwave
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
                    $grossPrice = floatval($lowestPrice);
                    $netPrice = round($grossPrice / 1.19, 2); // 19% tax removed
                    $parentRow[7]  = $netPrice;      // net price
                    $parentRow[8]  = $grossPrice;    // gross price
                    $parentRow[9]  = $netPrice;      // purchase price net
                    $parentRow[10] = $grossPrice;    // purchase price gross
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
                $parentRow[14] =  "";   // cover_media_id - keep blank for newwave
                $parentRow[15] =  "";   // cover_media_url
                $parentRow[16] = "";                       // cover_media_title (REQUIRED)
                $parentRow[17] = "";                       // cover_media_alt   (REQUIRED)

                // Handle Gallery Images
                // If there are multiple images in the array, join them with a pipe (|)
                //$parentRow[41] = implode('|', array_unique($imageUrlsParent)); // Gallery images (unique URLs)
            } else {
                // In case no images are available, leave the cover and gallery image fields blank (or set a default)
                $parentRow[14] = '';   // cover_media_id - keep blank for newwave
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
                // set variant parent linkage to index 1 only (parent_id column)
                // Index 14 is cover_media_id and must stay blank for newwave imports
                $vr[1] = $parentId; // parent_id

                // Variant name is already set during creation, just add to CSV
                // No need to re-calculate here as it's already in $vr[5]

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
            error_log("[NEWWAVE] Creating " . count($newCategory) . " new categories");
            foreach ($newCategory as $cat) {
                error_log("[NEWWAVE] Category: " . $cat['name']);
            }
            createBulkCategories($newCategory, $container);
        } else {
            error_log("[NEWWAVE] No new categories to create (newCategory is empty)");
        }

        if (!empty($productCsvData)) {
            // Hand-off to your CSV creator
            $result = createProductCsv($productCsvData, $container, $categoryMapping);
            
            // Show queue processing UI inline ONLY if not called from import_processor
            if (isset($result['jobId']) && !defined('IMPORT_PROCESSOR_MODE')) {
                showQueueProcessingUI($result['jobId'], $vendor, basename($result['path']), count($productCsvData));
                exit;
            }
            
            // Output CSV path for import_processor to capture
            if (defined('IMPORT_PROCESSOR_MODE') && isset($result['path'])) {
                echo $result['path'];
                return; // Exit this included file, let import_processor.php handle response wrapping
            }

            // Below is only executed when NOT in IMPORT_PROCESSOR_MODE
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
            exit;

        } else {
            echo "⚠️ No products found or no variants available.";
            exit;
        }
        
        return; // Exit included file so import_processor.php can continue
} else {
    echo "❌ Error: Vendor not recognized or no vendor specified. Vendor value: " . ($_POST['vendor'] ?? 'NOT SET');
    error_log("create_product.php: Vendor not matched - " . ($_POST['vendor'] ?? 'NOT SET'));
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
    
    // ===================== CREATE JOB ENTRY IN vendor_import_jobs =====================
    $jobId = 'JOB_' . strtoupper($vendor) . '_' . date('YmdHis') . '_' . substr(md5(uniqid()), 0, 6);
    $originalFileName = $_FILES['vendor_file']['name'] ?? $_POST['temp_file'] ?? 'unknown';
    $totalRows = count($csvData);
    
    try {
        $pdo = $container->get(\Doctrine\DBAL\Connection::class)->getNativeConnection();
        
        $insertJobSql = "INSERT INTO vendor_import_jobs 
            (job_id, vendor_name, import_type, file_name, file_path, batch_size, total_rows, processed_rows, error_rows, status, category_mapping, created_at, started_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($insertJobSql);
        $stmt->execute([
            $jobId,
            $vendor,
            'product',
            basename($originalFileName),
            '', // file_path will be updated after CSV is saved
            25, // default batch_size
            $totalRows,
            0, // processed_rows
            0, // error_rows
            'pending', // status
            json_encode($categoryMapping)
        ]);
        
    } catch (\Exception $e) {
        error_log("Failed to create job entry: " . $e->getMessage());
    }

    global $colorPropertyGrpId, $sizePropertyGrpId;
    $manufacturerList = getManufacturers($container);
    $colorOptions     = getPropertyOptions('Color', $container);
    $sizeOptions      = getPropertyOptions('Size', $container);
    $enableOptionIdFix = ($vendor === 'harko');
    $colorOptionsLower = [];
    foreach ($colorOptions as $name => $id) {
        $colorOptionsLower[mb_strtolower((string)$name, 'UTF-8')] = $id;
    }
    $sizeOptionsLower = [];
    foreach ($sizeOptions as $name => $id) {
        $sizeOptionsLower[mb_strtolower((string)$name, 'UTF-8')] = $id;
    }
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
        'id','parent_id','product_number','active','stock','translations.DEFAULT.name','translations.DEFAULT.description',
        'price_net','price_gross','purchase_prices_net','purchase_prices_gross',
        'tax_id','tax_rate','tax_name','cover_media_id','cover_media_url',
        'cover_media_title','cover_media_alt','manufacturer_id','manufacturer_name',
        'categories','sales_channel','propertyIds','optionIds','material','gender',
        'sleeve_length','article_number_short','ean','model_name','item_in_box',
        'item_in_bag','weight','country','washing_temp','supplier','cut',
        'febric_weight','article_code','gtin','supplier_article',
        'vendor_category','shopware_category_id','size_spec_pdf_url'
    ];

    // Always save to disk (no browser download)
    $outputDir = __DIR__ . '/csv-imports/product/';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    $outputPath = $outputDir . $csvName;
    $out = fopen($outputPath, 'w');
    
    fputcsv($out, $productCsvHeader, ";");
    

    // ===================== CSV ROWS =====================
    $rowNum = 0;
    $resolveOptionId = function ($value, $optionsLower) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        $valueNoDash = strtolower(str_replace('-', '', $value));
        if (preg_match('/^[0-9a-f]{32}$/i', $valueNoDash)) {
            return $valueNoDash;
        }
        $key = mb_strtolower($value, 'UTF-8');
        return $optionsLower[$key] ?? '';
    };

    $ensureOptionId = function ($name, $groupId, &$options, &$optionsLower, $container) {
        $name = trim((string)$name);
        if ($name === '') {
            return '';
        }
        $key = mb_strtolower($name, 'UTF-8');
        if (isset($optionsLower[$key])) {
            return $optionsLower[$key];
        }
        $newId = Uuid::randomHex();
        createPropertyOptions($groupId, [[
            'id' => $newId,
            'name' => $name,
        ]], $container);
        $options[$name] = $newId;
        $optionsLower[$key] = $newId;
        return $newId;
    };
    foreach ($csvData as $row) {
        $rowNum++;
        // Clean and normalize name field
        if (isset($row[5])) {
            $originalName = $row[5];
            $row[5] = str_replace(';', ',', trim($row[5]));
            
            // For child products (has parent_id), clean base name first
            if (!empty($row[1])) {
                // If this variant already has color/size keys and vendor is HARKO, set name to "Color - Size"
                $isHarkoVariant = ($vendor === 'harko') && isset($row['color_opt_name']) && isset($row['size_opt_name']);
                // NEWWAVE: If this variant already has color/size keys, the name is already correct
                // (set in newwave handler as "Color - Size" format)
                $isNewwaveVariant = isset($row['color_opt_name']) && isset($row['size_opt_name']);
                
                if ($isHarkoVariant) {
                    $colorName = trim((string)($row['color_opt_name'] ?? ''));
                    $sizeName = trim((string)($row['size_opt_name'] ?? ''));
                    $nameParts = array_filter([$colorName, $sizeName]);
                    $row[5] = !empty($nameParts) ? implode(' - ', $nameParts) : $row[5];
                } elseif (!$isNewwaveVariant) {
                    // Old behavior: clean and rebuild name for other vendors
                    $brandName = trim($row[19] ?? '');
                    
                    // Strip brand name from the beginning if present (case insensitive)
                    if (!empty($brandName) && stripos($row[5], $brandName) === 0) {
                        $row[5] = ltrim(substr($row[5], strlen($brandName)));
                    }
                    
                    // Remove existing variant info (color, size) which comes after comma
                    $commaPos = strpos($row[5], ',');
                    if ($commaPos !== false) {
                        $row[5] = substr($row[5], 0, $commaPos);
                    }
                    
                    $row[5] = trim($row[5]);
                    
                    // Debug first few rows
                    if ($rowNum <= 3) {
                        error_log("CSV ROW $rowNum: Original='$originalName' -> Cleaned='{$row[5]}' | Has Parent: YES | Brand: '$brandName'");
                    }
                } else {
                    // Newwave variant: name is already correct (e.g., "99 - 3XL"), keep as-is
                    $row[5] = trim($row[5]);
                    if ($rowNum <= 3) {
                        error_log("CSV ROW $rowNum: Newwave variant name (correct): '{$row[5]}'");
                    }
                }
            } else {
                // Parent product
                if ($rowNum <= 3) {
                    error_log("CSV ROW $rowNum: Name='{$row[5]}' | Has Parent: NO (Parent Product)");
                }
            }
            
            // Final validation - ensure name is not empty
            if (empty($row[5])) {
                $row[5] = 'Product ' . ($row[2] ?? 'Unknown');
                error_log("CSV ROW $rowNum: Name was EMPTY, using fallback: '{$row[5]}'");
            }
        }
        
        // Manufacturer ID resolve
        if (empty($row[18]) && !empty($row[19]) && isset($manufacturerList[$row[19]])) {
            $row[18] = $manufacturerList[$row[19]];
        }
        
        // Property mapping (Color | Size) for option IDs
        if ($enableOptionIdFix && isset($row[23])) {
            $colorName = trim((string)($row['color_opt_name'] ?? ''));
            $sizeName = trim((string)($row['size_opt_name'] ?? ''));

            if ($colorName !== '' || $sizeName !== '') {
                $resolvedColor = $resolveOptionId($colorName, $colorOptionsLower);
                $resolvedSize  = $resolveOptionId($sizeName, $sizeOptionsLower);

                if ($resolvedColor === '') {
                    $resolvedColor = $ensureOptionId($colorName, $colorPropertyGrpId, $colorOptions, $colorOptionsLower, $container);
                }
                if ($resolvedSize === '') {
                    $resolvedSize = $ensureOptionId($sizeName, $sizePropertyGrpId, $sizeOptions, $sizeOptionsLower, $container);
                }
            } else {
                [$c, $s] = array_pad(explode('|', $row[23]), 2, '');
                $resolvedColor = $resolveOptionId($c, $colorOptionsLower);
                $resolvedSize  = $resolveOptionId($s, $sizeOptionsLower);

                if ($resolvedColor === '') {
                    $fallbackColor = $row['color_opt_name'] ?? $c;
                    $resolvedColor = $ensureOptionId($fallbackColor, $colorPropertyGrpId, $colorOptions, $colorOptionsLower, $container);
                }
                if ($resolvedSize === '') {
                    $fallbackSize = $row['size_opt_name'] ?? $s;
                    $resolvedSize = $ensureOptionId($fallbackSize, $sizePropertyGrpId, $sizeOptions, $sizeOptionsLower, $container);
                }
            }

            $row[23] = $resolvedColor . "|" . $resolvedSize;

            unset($row['color_opt_name'], $row['size_opt_name']);
        } elseif (isset($row['color_opt_name']) && isset($row[23])) {
            $colorName = trim($row['color_opt_name']);
            $sizeName = isset($row['size_opt_name']) ? trim($row['size_opt_name']) : '';

            // Map option codes to option IDs
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

        $sizeSpecPdfUrl = '';
        if ($vendor === 'ross') {
            $isParentRow = empty($row[1]);
            $sizeSpecPdfUrl = $isParentRow ? trim((string)($row['size_spec_pdf_url'] ?? '')) : '';
        }
        unset($row['size_spec_pdf_url']);
        $row[] = $sizeSpecPdfUrl;

        fputcsv($out, $row, ";");
    }

    fclose($out);
    
    // ===================== AUTO-ADD VISIBILITY TO SALES CHANNEL =====================
    // All newly created products should be visible on the sales channel automatically
    try {
        $connection = $container->get(\Doctrine\DBAL\Connection::class);
        $salesChannelId = '0197e3dc1566708987331d818f8e1867';
        
        // Extract product numbers from CSV data and add visibility for each
        foreach ($csvData as $row) {
            $productNumber = $row[2] ?? null; // product_number at index 2
            if (empty($productNumber)) continue;
            
            // Get product ID from database
            $getProductSql = "SELECT HEX(id) as product_id, HEX(version_id) as version_id FROM product WHERE product_number = ? LIMIT 1";
            $getStmt = $connection->prepare($getProductSql);
            $getResult = $getStmt->executeQuery([$productNumber]);
            $product = $getResult->fetchAssociative();
            
            if ($product) {
                $productId = $product['product_id'];
                $versionId = $product['version_id'];
                
                // Check if visibility already exists
                $checkSql = "SELECT id FROM product_visibility WHERE product_id = UNHEX(?) AND sales_channel_id = UNHEX(?)";
                $checkStmt = $connection->prepare($checkSql);
                $checkResult = $checkStmt->executeQuery([$productId, $salesChannelId]);
                
                if ($checkResult->rowCount() === 0) {
                    // Add visibility to sales channel
                    $insertVisSql = "INSERT INTO product_visibility (id, product_id, product_version_id, sales_channel_id, visibility, created_at) 
                                    VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), 30, NOW())";
                    $visStmt = $connection->prepare($insertVisSql);
                    $visStmt->executeStatement([
                        str_replace('-', '', Uuid::randomHex()),
                        $productId,
                        $versionId,
                        $salesChannelId
                    ]);
                    echo "✓ Added visibility for: " . $productNumber . "\n";
                }
            }
        }
        echo "✓ Product visibility update completed\n";
    } catch (\Exception $e) {
        error_log("Failed to add product visibility: " . $e->getMessage());
        echo "✗ Error adding visibility: " . $e->getMessage() . "\n";
    }
    
    // ===================== UPDATE JOB WITH FILE PATH =====================
    try {
        $pdo = $container->get(\Doctrine\DBAL\Connection::class)->getNativeConnection();
        $updateJobSql = "UPDATE vendor_import_jobs SET file_path = ? WHERE job_id = ?";
        $stmt = $pdo->prepare($updateJobSql);
        $stmt->execute([$outputPath, $jobId]);
    } catch (\Exception $e) {
        error_log("Failed to update job file path: " . $e->getMessage());
    }
    
    // Return array with jobId and path for queue processing UI
    return [
        'jobId' => $jobId,
        'path' => $outputPath
    ];
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

function showQueueProcessingUI($jobId, $vendor, $fileName, $totalRows) {
    // Ensure vendor has a value
    $vendor = $vendor ?? 'unknown';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Processing Import - <?= htmlspecialchars($jobId) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 40px;
                max-width: 900px;
                margin: 0 auto;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
                font-size: 32px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .job-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
            .job-info p {
                margin: 8px 0;
                color: #666;
                font-size: 15px;
            }
            .job-info strong {
                color: #333;
                display: inline-block;
                min-width: 120px;
            }
            .progress-container {
                background: #f0f0f0;
                border-radius: 10px;
                height: 50px;
                overflow: hidden;
                margin: 20px 0;
                position: relative;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
            }
            .progress-bar {
                background: linear-gradient(90deg, #667eea, #764ba2);
                height: 100%;
                width: 0%;
                transition: width 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 16px;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 25px 0;
            }
            .stat-box {
                background: #f8f9fa;
                padding: 25px;
                border-radius: 12px;
                text-align: center;
                transition: transform 0.2s;
            }
            .stat-box:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .stat-box h3 {
                font-size: 36px;
                margin-bottom: 8px;
                font-weight: 700;
            }
            .stat-box.success h3 { color: #28a745; }
            .stat-box.danger h3 { color: #dc3545; }
            .stat-box.warning h3 { color: #ffc107; }
            .stat-box.info h3 { color: #17a2b8; }
            .stat-box p {
                color: #666;
                font-size: 14px;
                font-weight: 500;
            }
            .log-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 25px 0 10px;
            }
            .log-header h2 {
                color: #333;
                font-size: 20px;
            }
            .log {
                background: #1e1e1e;
                color: #d4d4d4;
                padding: 20px;
                border-radius: 10px;
                max-height: 400px;
                overflow-y: auto;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                line-height: 1.6;
            }
            .log-entry {
                margin: 5px 0;
                padding: 8px 12px;
                border-left: 3px solid transparent;
                border-radius: 4px;
                animation: slideIn 0.3s ease;
            }
            @keyframes slideIn {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .log-entry.success { 
                border-color: #28a745; 
                background: rgba(40, 167, 69, 0.1);
            }
            .log-entry.error { 
                border-color: #dc3545; 
                color: #ff6b6b; 
                background: rgba(220, 53, 69, 0.1);
            }
            .log-entry.skip { 
                border-color: #ffc107; 
                color: #ffd93d; 
                background: rgba(255, 193, 7, 0.1);
            }
            .log-entry.info { 
                border-color: #17a2b8; 
                background: rgba(23, 162, 184, 0.1);
            }
            .complete-message {
                background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                color: #155724;
                padding: 25px;
                border-radius: 12px;
                margin-top: 20px;
                border: 2px solid #28a745;
                display: none;
                animation: fadeIn 0.5s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
            .complete-message.show { display: block; }
            .complete-message h2 {
                margin-bottom: 10px;
                font-size: 24px;
            }
            .spinner {
                display: inline-block;
                width: 18px;
                height: 18px;
                border: 3px solid rgba(255,255,255,.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
                margin-right: 8px;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            .log::-webkit-scrollbar {
                width: 8px;
            }
            .log::-webkit-scrollbar-track {
                background: #2d2d2d;
            }
            .log::-webkit-scrollbar-thumb {
                background: #555;
                border-radius: 4px;
            }
            .log::-webkit-scrollbar-thumb:hover {
                background: #777;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 Processing Import Job</h1>
            
            <div class="job-info">
                <p><strong>Job ID:</strong> <?= htmlspecialchars($jobId) ?></p>
                <p><strong>Vendor:</strong> <?= htmlspecialchars(strtoupper($vendor)) ?></p>
                <p><strong>File:</strong> <?= htmlspecialchars($fileName) ?></p>
                <p><strong>Total Products:</strong> <?= $totalRows ?></p>
            </div>

            <div class="progress-container">
                <div class="progress-bar" id="progressBar">
                    <span id="progressText"><span class="spinner"></span> 0%</span>
                </div>
            </div>

            <div class="stats">
                <div class="stat-box info">
                    <h3 id="processedCount">0</h3>
                    <p>Processed</p>
                </div>
                <div class="stat-box success">
                    <h3 id="createdCount">0</h3>
                    <p>Created</p>
                </div>
                <div class="stat-box warning">
                    <h3 id="skippedCount">0</h3>
                    <p>Skipped (Duplicates)</p>
                </div>
                <div class="stat-box danger">
                    <h3 id="errorCount">0</h3>
                    <p>Errors</p>
                </div>
            </div>

            <div class="complete-message" id="completeMessage">
                <h2>✅ Import Completed Successfully!</h2>
                <p id="summaryText">All products have been processed.</p>
            </div>

            <div class="log-header">
                <h2>📋 Processing Log</h2>
            </div>
            <div class="log" id="logContainer">
                <div class="log-entry info">🔄 Initializing import process...</div>
            </div>
        </div>

        <script>
            const jobId = '<?= $jobId ?>';
            let processed = 0;
            let created = 0;
            let skipped = 0;
            let errors = 0;
            const totalRows = <?= $totalRows ?>;

            function updateProgress() {
                const progress = Math.min((processed / totalRows) * 100, 100);
                const progressBar = document.getElementById('progressBar');
                progressBar.style.width = progress + '%';
                
                const progressText = document.getElementById('progressText');
                if (progress < 100) {
                    progressText.innerHTML = '<span class="spinner"></span> ' + Math.round(progress) + '%';
                } else {
                    progressText.innerHTML = '✓ 100% Complete';
                }
                
                document.getElementById('processedCount').textContent = processed;
                document.getElementById('createdCount').textContent = created;
                document.getElementById('skippedCount').textContent = skipped;
                document.getElementById('errorCount').textContent = errors;

                if (processed >= totalRows) {
                    const completeMsg = document.getElementById('completeMessage');
                    const summary = `Processed ${processed} products: ${created} created, ${skipped} skipped (duplicates), ${errors} errors.`;
                    document.getElementById('summaryText').textContent = summary;
                    completeMsg.classList.add('show');
                }
            }

            function addLog(message, type = 'info') {
                const logContainer = document.getElementById('logContainer');
                const entry = document.createElement('div');
                entry.className = 'log-entry ' + type;
                const timestamp = new Date().toLocaleTimeString();
                entry.textContent = `[${timestamp}] ${message}`;
                logContainer.appendChild(entry);
                logContainer.scrollTop = logContainer.scrollHeight;
            }

            // Start processing immediately
            addLog('Starting CSV processing...', 'info');
            addLog('Connecting to worker: process_csv_worker.php?job_id=' + jobId, 'info');
            processCSV();

            async function processCSV() {
                try {
                    addLog('Fetching worker endpoint...', 'info');
                    const response = await fetch('process_csv_worker.php?job_id=' + encodeURIComponent(jobId));
                    
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    
                    if (!response.body) {
                        throw new Error('ReadableStream not supported in this browser');
                    }
                    
                    addLog('Worker connected, processing started...', 'success');
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    while (true) {
                        const {done, value} = await reader.read();
                        if (done) {
                            addLog('Stream ended', 'info');
                            break;
                        }

                        buffer += decoder.decode(value, {stream: true});
                        const lines = buffer.split('\n');
                        
                        // Keep the last incomplete line in buffer
                        buffer = lines.pop() || '';

                        for (const line of lines) {
                            if (!line.trim()) continue;

                            try {
                                const data = JSON.parse(line);
                                
                                if (data.type === 'progress') {
                                    processed = data.processed;
                                    created = data.created;
                                    skipped = data.skipped;
                                    errors = data.errors;
                                    updateProgress();
                                } else if (data.type === 'log') {
                                    addLog(data.message, data.level);
                                } else if (data.type === 'complete') {
                                    addLog('✅ Import completed successfully!', 'success');
                                    updateProgress();
                                }
                            } catch (e) {
                                console.error('Parse error:', e, 'Line:', line);
                                addLog('⚠️ Parse error in response', 'error');
                            }
                        }
                    }
                    
                    // Process any remaining data in buffer
                    if (buffer.trim()) {
                        try {
                            const data = JSON.parse(buffer);
                            if (data.type === 'complete') {
                                addLog('✅ Import completed!', 'success');
                                updateProgress();
                            }
                        } catch (e) {
                            console.error('Final buffer parse error:', e);
                        }
                    }
                } catch (error) {
                    addLog('❌ Connection Error: ' + error.message, 'error');
                    addLog('Please check if process_csv_worker.php exists and is accessible', 'error');
                    console.error('Fetch error:', error);
                }
            }
        </script>
    </body>
    </html>
    <?php
}

function buildRossSizeSpecPdfUrl(array $data): string
{
    $brand = trim((string)($data['Brand'] ?? ''));
    $style = trim((string)($data['Style'] ?? ''));
    $manufacturerCode = trim((string)($data['Manufacturer Code'] ?? ''));
    $articleCode = trim((string)($data['Article Code'] ?? ''));

    if ($brand === '' || $style === '' || $manufacturerCode === '' || $articleCode === '') {
        return '';
    }

    $sanitizePart = static function (string $value): string {
        $value = str_replace(['\\', '/'], '-', trim($value));
        return preg_replace('/\s+/u', ' ', $value) ?? '';
    };

    $brand = $sanitizePart($brand);
    $style = $sanitizePart($style);
    $manufacturerCode = $sanitizePart($manufacturerCode);
    $articleCode = $sanitizePart($articleCode);

    if ($brand === '' || $style === '' || $manufacturerCode === '' || $articleCode === '') {
        return '';
    }

    $brandFolder = $brand;
    if (preg_match('/^b\s*&\s*c$/iu', $brand) === 1) {
        $brandFolder = 'B&C';
    }

    $fileName = $style . '_' . $manufacturerCode . '--' . $articleCode . '_sizespecs.pdf';

    return '/my-imports/ROSS/PDF/size specs/' . $brandFolder . '/' . $fileName;
}