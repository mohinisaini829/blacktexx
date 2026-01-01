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
$dotenv->loadEnv(__DIR__ . '/../.env');

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
$salesChannelId          =  '0197e3dc1566708987331d818f8e1867';
$taxId                   =  '0197e3c80947729bbb9c9ca9f3238a05';
$taxRate                 =  '19';
$taxName                 =  'Standard rate';
$stock                   =  10;
$isActive                =  1;
if (isset($_POST['vendor']) && $_POST['vendor'] === 'ross') {
    $file = $_FILES['csv_file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . ($file['error'] ?? 'no file');
    } else {
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            $message = "Invalid file type. Please upload a CSV / XLS file.";
        } else {
            try {
                $fileTmpPath = $file['tmp_name'];
                $spreadsheet = IOFactory::load($fileTmpPath);

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
                //$rootCategory   =   getRootCategory();

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
                    //print_r($data);die('kkkkkkk');
                    $data = array_combine(
                        array_map('trim', array_keys($data)),
                        $data
                    );
                    $ranges = [
                    1    => ["start" => 1,    "end" => 9],
                    10   => ["start" => 10,   "end" => 24],
                    25   => ["start" => 25,   "end" => 49],
                    50   => ["start" => 50,   "end" => 99],
                    100  => ["start" => 100,  "end" => 249],
                    250  => ["start" => 250,  "end" => 499],
                    500  => ["start" => 500,  "end" => 999],
                    1000 => ["start" => 1000],
                    ];

                    $prices = [];

                    // Loop quantity keys
                    foreach ($ranges as $qtyKey => $range) {
                        if (!empty($data[$qtyKey])) {
                            $prices[$qtyKey] = $data[$qtyKey]; // सिर्फ qty=>price format
                        }
                    }

                    // Merge back into $data
                    $data['prices'] = $prices;

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
                        //$proArr[20]         =   $categoryIds;
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
                    
                    /* Add Child Row */

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
                    $proArr[41]         =   $data['prices'];

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
                createTierPriceProductCsv($productCsvData, $container);

                exit;
            } catch (\Exception $e) {
                echo 'Error loading file: ', $e->getMessage();
            }
        }
    }
}else if(isset($_POST['vendor']) && $_POST['vendor'] === 'harko')
{
    $file = $_FILES['csv_file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . ($file['error'] ?? 'no file');
    } else {
        $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                $message = "Invalid file type. Please upload a CSV / XLS file.";
            } else {
                try {
                    $fileTmpPath = $file['tmp_name'];
                    $spreadsheet = IOFactory::load($fileTmpPath);

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
                    //$rootCategory   =   getRootCategory();

                    foreach ($sheet->getRowIterator() as $row) {
                        if (in_array($rowCount, [3,4,5])) {
                            $rowCount++;
                            continue;
                        }

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
                        //print_r($data);die('kkkkkkk');
                        $data = array_combine(
                            array_map('trim', array_keys($data)),
                            $data
                        );
                        //die('dsdfasfd');
                        $ranges = [
                            "Price 1"    => ["start" => 1,    "end" => 9],
                            "Price 10"   => ["start" => 10,   "end" => 24],
                            "Price 25"   => ["start" => 25,   "end" => 49],
                            "Price 50"   => ["start" => 50,   "end" => 99],
                            "Price 100"  => ["start" => 100,  "end" => 249],
                            "Price 250"  => ["start" => 250,  "end" => 499],
                            "Price 500"  => ["start" => 500,  "end" => 999],
                            "Price 1000" => ["start" => 1000],
                        ];

                        $prices = [];

                        foreach ($ranges as $qtyKey => $range) {
                            if (!empty($data[$qtyKey])) {
                                $normalizedKey = strtolower(str_replace(' ', '_', $qtyKey)); 
                                $prices[$normalizedKey] = $data[$qtyKey];
                            }
                        }

                        // Merge back into $data
                        $data['prices'] = $prices;

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
                            //$proArr[20]         =   $categoryIds;
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
                        /* Add Child Row */

                        $proArr             =   $productDefaultArray;
                        $proArr[0]          =   Uuid::randomHex();
                        $proArr[1]          =   $parentIdSkuArr[$parentSku];
                        $proArr[2]          =   $data['Article Number'];
                        $proArr[5]          =   $data['Web Shop Article Name (Product Page, Listing)'] ?? '';
                        $proArr[6]          =   $description;
                        $proArr[7]          =   $data['Price 1'] ?? '';
                        $proArr[8]          =   $data['Price 1'] ?? '';
                        $proArr[18]         =   $brandId;
                        $proArr[19]         =   $brandName;
                        $proArr[24]         =   $data['13 Mateial Group'] ?? '';
                        $proArr[25]         =   $gender;
                        $proArr[26]         =   $data['14 Sleeve Length'] ?? '';
                        $proArr[27]         =   $data['1 Article Number Short'] ?? '';
                        $proArr[28]         =   $data['EAN/GTIN'] ?? '';
                        $proArr[29]         =   $data['2 Photo Number Short'] ?? '';
                        $proArr[30]         =   $data['20 Pieces per Carton'] ?? '';
                        $proArr[31]         =   $data['Quantity Pack'] ?? '1';
                        $proArr[32]         =   $data['17 Fabric Weight'] ?? '';
                        $proArr[33]         =   $data['7 Country of Origin'] ?? '';
                        $proArr[34]         =   $data['16 Washing Temperature'] ?? '';
                        $proArr[35]         =   $Supplier;
                        $proArr[36]         =   $data['15 Cutting Style'] ?? '';
                        $proArr[37]         =   $data['17 Fabric Weight'] ?? '';
                        $proArr[38]         =   $data['5. Color Code'] ?? '';
                        $proArr[39]         =   $data['EAN/GTIN'] ?? '';
                        $proArr[40]         =   $partnerArticle;
                        $proArr[41]         =   $data['prices'];
                        //print_r($proArr);die;
                        

                        $productCsvData[]   =   $proArr;
                        /* Add Child Row */

                        $rowCount++;
                    }
                    createTierPriceProductCsv($productCsvData, $container);

                    exit;
                } catch (\Exception $e) {
                    echo 'Error loading file: ', $e->getMessage();
                }
            }

        }
    
}
echo $message. " Last Message";

function createTierPriceProductCsv($csvData, $container)
{
    //print_r($csvData);
    $date    = date("dmy");
    $vendor  = $_POST['vendor'] ?? 'default';

    $csvName = $vendor . "_product_tier_price_import_{$date}.csv";

    // Header
    $productCsvHeader = [
        'id',
        'product_id',
        'rule_id',
        'price_net',
        'price_gross',
        'quantity_start',
        'quantity_end'
    ];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $csvName . '"');

    $proFile = fopen('php://output', 'w');
    fputcsv($proFile, $productCsvHeader, ";");

    foreach ($csvData as $csvDatakey => $csvDatavalue) {
        $productRepository = $container->get('product.repository');
        $articleNumber     = $csvDatavalue[2] ?? null; // आपका Article Number
        $context           = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $articleNumber));

        $product   = $productRepository->search($criteria, $context)->first();
        $productId = $product ? $product->getId() : null;

        $rule_id   = '0197e3c809b773c591e619aa39e52a29';
        $idPrefix   = Uuid::randomHex();
        // Vendor-wise ranges
        if (!empty($vendor) && $vendor === 'ross') {
            $ranges = [
                1    => ["start" => 1,    "end" => 9],
                10   => ["start" => 10,   "end" => 24],
                25   => ["start" => 25,   "end" => 49],
                50   => ["start" => 50,   "end" => 99],
                100  => ["start" => 100,  "end" => 249],
                250  => ["start" => 250,  "end" => 499],
                500  => ["start" => 500,  "end" => 999],
                1000 => ["start" => 1000], // open ended
            ];
            if (isset($csvDatavalue[41]) && is_array($csvDatavalue[41])) {
                foreach ($csvDatavalue[41] as $qtyKey => $priceValue) {
                    if (isset($ranges[$qtyKey])) {
                        $range    = $ranges[$qtyKey];
                        //$idPrefix = Uuid::randomHex();

                        $row = [
                            $idPrefix,                       // unique id
                            $productId,                      // product id
                            $rule_id,                        // rule id
                            $priceValue,                     // net
                            $priceValue,                     // gross
                            $range["start"],                 // quantity_start
                            $range["end"] ?? null            // quantity_end
                        ];

                        fputcsv($proFile, $row, ";");
                    }
                }
            }
        } else if(!empty($vendor) && $vendor === 'harko'){
            $ranges = [
                ["key" => "price_1",    "start" => 1,    "end" => 9],
                ["key" => "price_10",   "start" => 10,   "end" => 24],
                ["key" => "price_25",   "start" => 25,   "end" => 49],
                ["key" => "price_50",   "start" => 50,   "end" => 99],
                ["key" => "price_100",  "start" => 100,  "end" => 249],
                ["key" => "price_250",  "start" => 250,  "end" => 499],
                ["key" => "price_500",  "start" => 500,  "end" => 999],
                ["key" => "price_1000", "start" => 1000] // open ended
            ];
            
            if (isset($csvDatavalue[41]) && is_array($csvDatavalue[41])) {
                foreach ($ranges as $range) {
                    $key = $range["key"];
                    if (isset($csvDatavalue[41][$key])) {
                        $price = $csvDatavalue[41][$key];

                        $row = [
                            $idPrefix,                   // unique id
                            $productId ?? '',           // product id
                            $rule_id ?? '',             // rule_id
                            $price,                      // price net
                            $price,                      // price gross = net
                            $range["start"],             // quantity start
                            $range["end"] ?? ''          // quantity end
                        ];

                        fputcsv($proFile, $row, ";");
                    }
                }
            }
        }

    }

    fclose($proFile);
    exit;
}
