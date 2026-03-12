<?php
declare(strict_types=1);

// Redirect to vendor_import.php for automatic queue processing
// Only process if called from import_processor.php or CLI
if (!defined('IMPORT_PROCESSOR_MODE') && php_sapi_name() !== 'cli' && empty($_POST['temp_file'])) {
    // If direct browser access without file upload, show message and redirect
    if (empty($_FILES['csv_file']['tmp_name'])) {
        echo '<!DOCTYPE html>
<html><head><title>Redirecting...</title>
<meta http-equiv="refresh" content="0;url=vendor_import.php">
<style>body{font-family:Arial;padding:50px;text-align:center;}h1{color:#667eea;}</style>
</head><body>
<h1>🔄 Redirecting to Import Manager...</h1>
<p>For automatic tier price import with progress tracking, please use:</p>
<h2><a href="vendor_import.php" style="color:#667eea;">Vendor Import System</a></h2>
<p>Redirecting automatically in 3 seconds...</p>
<p><small>This page now only processes imports via queue system.</small></p>
</body></html>';
        exit;
    }
    
    // If file uploaded directly, process through import_processor.php
    if (!empty($_FILES['csv_file']['tmp_name']) && !empty($_POST['vendor'])) {
        $_POST['import_type'] = 'tierprice';
        $_POST['vendor_name'] = $_POST['vendor'];
        
        // Include import processor to handle queue creation
        require_once __DIR__ . '/import_processor.php';
        exit;
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

if (defined('IMPORT_PROCESSOR_MODE')) {
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode([
                'success' => false,
                'message' => 'Fatal error: ' . $error['message']
            ]);
        }
    });
}

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

class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private int $startRow = 1;
    private int $endRow = 1;

    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    public function readCell($column, $row, $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}

function iterateSpreadsheetRows(string $filePath, callable $rowHandler, int $chunkSize = 500): void
{
    $reader = IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    if (method_exists($reader, 'setReadEmptyCells')) {
        $reader->setReadEmptyCells(false);
    }

    $info = $reader->listWorksheetInfo($filePath);
    $sheetName = $info[0]['worksheetName'] ?? null;
    $totalRows = (int)($info[0]['totalRows'] ?? 0);
    if ($totalRows <= 0) {
        return;
    }

    $filter = new ChunkReadFilter();
    for ($startRow = 1; $startRow <= $totalRows; $startRow += $chunkSize) {
        $filter->setRows($startRow, $chunkSize);
        $reader->setReadFilter($filter);
        if ($sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $endRow = min($startRow + $chunkSize - 1, $totalRows);
        foreach ($sheet->getRowIterator($startRow, $endRow) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getCalculatedValue();
            }
            $rowHandler($rowData, $row->getRowIndex());
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
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
$allowedTypes        =  ['text/csv', 'application/vnd.ms-excel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
$salesChannelId          =  '0197e3dc1566708987331d818f8e1867';
$taxId                   =  '0197e3c80947729bbb9c9ca9f3238a05';
$taxRate                 =  '19';
$taxName                 =  'Standard rate';
$stock                   =  10;
$isActive                =  1;

// Debug logging
$logFile = __DIR__ . '/import-logs/tier_price_debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] File accessed - POST data: " . json_encode($_POST) . "\n", FILE_APPEND);

// Support both 'vendor' and 'vendor_name' for direct upload and queue worker
$vendor = $_POST['vendor'] ?? $_POST['vendor_name'] ?? null;
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Detected vendor: " . ($vendor ?? 'none') . "\n", FILE_APPEND);

// Check if this is a direct file upload (csv_file field present)
$isDirectUpload = !empty($_FILES['csv_file']['tmp_name']);
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Is Direct Upload: " . ($isDirectUpload ? 'YES' : 'NO') . "\n", FILE_APPEND);

// For tier prices with temp_file (NOT direct upload), block it
if (!$isDirectUpload && !empty($_POST['temp_file']) && isset($_POST['import_type']) && $_POST['import_type'] === 'tierprice') {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] BLOCKING: temp_file not supported for tier price. Use direct XLS upload.\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Please upload XLS file directly for tier price import. Temp CSV not supported.'
    ]);
    exit;
}

// Skip temp_file processing for tier prices - use direct XLS upload instead
if (!$isDirectUpload && !empty($_POST['temp_file']) && file_exists($_POST['temp_file']) && (!isset($_POST['import_type']) || $_POST['import_type'] !== 'tierprice')) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Processing from temp file: " . $_POST['temp_file'] . "\n", FILE_APPEND);
    
    // Read the temp CSV file and process tier prices
    $tempFile = $_POST['temp_file'];
    $productCsvData = [];
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Reading temp file with semicolon delimiter\n", FILE_APPEND);
    
    if (($handle = fopen($tempFile, "r")) !== FALSE) {
        $header = fgetcsv($handle, 0, ";");
        
        // Validate and clean header
        if (!$header || empty($header)) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Invalid CSV header\n", FILE_APPEND);
            fclose($handle);
            if (defined('IMPORT_PROCESSOR_MODE')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid CSV file format']);
            } else {
                die('Invalid CSV file format');
            }
            exit;
        }
        
        // Remove empty header columns
        $header = array_filter($header, function($val) { return $val !== null && $val !== ''; });
        $header = array_values($header); // Re-index
        
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] CSV Header (" . count($header) . " columns): " . json_encode($header) . "\n", FILE_APPEND);
        
        $rowNumber = 0;
        while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Check if header and row have same count
            if (count($header) !== count($row)) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Row $rowNumber: Header count=" . count($header) . ", Row count=" . count($row) . " - Adjusting\n", FILE_APPEND);
                
                // Adjust arrays to match
                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), '');
                } else {
                    // Row has more columns than header, trim it
                    $row = array_slice($row, 0, count($header));
                }
            }
            
            // Safe array_combine with validation
            try {
                $dataRow = array_combine($header, $row);
            } catch (ValueError $e) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR Row $rowNumber: Cannot combine - " . $e->getMessage() . "\n", FILE_APPEND);
                continue; // Skip this row
            }
            
            // Extract product number and prices
            $productNumber = $dataRow['product_number'] ?? ($row[2] ?? '');
            
            if (!empty($productNumber)) {
                // Parse tier prices from the row
                $prices = [];
                
                // Assuming tier prices are in columns after basic product info
                // Adjust based on your actual CSV structure
                $productCsvData[] = [
                    0 => $dataRow['id'] ?? '',
                    1 => $dataRow['parent_id'] ?? '',
                    2 => $productNumber,
                    3 => $dataRow['active'] ?? 1,
                    4 => $dataRow['stock'] ?? 10,
                    5 => $dataRow['name'] ?? '',
                    6 => $dataRow['description'] ?? '',
                    7 => $dataRow['price_net'] ?? '',
                    8 => $dataRow['price_gross'] ?? '',
                    41 => $dataRow['prices'] ?? []
                ];
                
                if ($rowNumber <= 3) {
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Sample Row $rowNumber: product_number=$productNumber\n", FILE_APPEND);
                }
            }
        }
        fclose($handle);
        
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Loaded " . count($productCsvData) . " products from temp file\n", FILE_APPEND);
        
        if (!empty($productCsvData) && !empty($vendor)) {
            createTierPriceProductCsv($productCsvData, $container);
            exit;
        }
    }
}

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
                $rowCount   = 1;
                $header     = [];
                $parentIds  = [];
                $parentIdSkuArr = [];
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

                iterateSpreadsheetRows($fileTmpPath, function (array $rowData, int $rowNumber) use (
                    &$rowCount,
                    &$header,
                    &$parentIds,
                    &$productCsvData,
                    &$sizeProperyData,
                    &$colorProperyData,
                    &$productDefaultArray,
                    &$lastParent,
                    &$newCategory,
                    &$newBrands,
                    &$newBrandsNameArr,
                    &$isActive,
                    &$stock,
                    &$taxId,
                    &$taxRate,
                    &$taxName,
                    &$salesChannelId,
                    &$manufacturerList,
                    &$parentIdSkuArr
                ) {
                    $rowCount = $rowNumber;
                    if (in_array($rowNumber, [1,3,4,5], true)) {
                        return;
                    }

                    if ($rowNumber === 2) {
                        $header = [];
                        foreach ($rowData as $idx => $value) {
                            $header[] = (trim((string)$value) === '') ? 'Unknown' . ($idx + 1) : $value;
                        }
                        return;
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
                        return;
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

                });
                createTierPriceProductCsv($productCsvData, $container);

                exit;
            } catch (\Exception $e) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error loading file: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
}else if((isset($_POST['vendor']) && $_POST['vendor'] === 'harko') || (isset($_POST['vendor_name']) && $_POST['vendor_name'] === 'harko'))
{
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Harko vendor selected - Processing started\n", FILE_APPEND);
    
    // Ensure vendor is set for CSV naming
    if (empty($_POST['vendor']) && !empty($_POST['vendor_name'])) {
        $_POST['vendor'] = $_POST['vendor_name'];
    }
    
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
                    $rowCount   = 1;
                    $header     = [];
                    $parentIds  = [];
                    $parentIdSkuArr = [];
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

                    iterateSpreadsheetRows($fileTmpPath, function (array $rowData, int $rowNumber) use (
                        &$rowCount,
                        &$header,
                        &$parentIds,
                        &$productCsvData,
                        &$sizeProperyData,
                        &$colorProperyData,
                        &$productDefaultArray,
                        &$lastParent,
                        &$newCategory,
                        &$newBrands,
                        &$newBrandsNameArr,
                        &$isActive,
                        &$stock,
                        &$taxId,
                        &$taxRate,
                        &$taxName,
                        &$salesChannelId,
                        &$manufacturerList,
                        &$parentIdSkuArr
                    ) {
                        $rowCount = $rowNumber;
                        if (in_array($rowNumber, [3,4,5], true)) {
                            return;
                        }

                        if ($rowNumber === 1) {
                            $header = [];
                            foreach ($rowData as $idx => $value) {
                                $header[] = (trim((string)$value) === '') ? 'Unknown' . ($idx + 1) : $value;
                            }
                            return;
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
                            return;
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
                    });
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Harko: Total products processed: " . count($productCsvData) . "\n", FILE_APPEND);
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Harko: Calling createTierPriceProductCsv with vendor=" . ($_POST['vendor'] ?? 'unknown') . "\n", FILE_APPEND);
                    
                    $csvFile = createTierPriceProductCsv($productCsvData, $container);
                    
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Harko: CSV File generated: " . ($csvFile ?? 'NULL') . "\n", FILE_APPEND);
                    
                    // If called from import_processor.php, set the GLOBALS variable
                    if (defined('IMPORT_PROCESSOR_MODE')) {
                        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] IMPORT_PROCESSOR_MODE: Setting GLOBALS['TIER_PRICE_CSV_FILE'] = " . $csvFile . "\n", FILE_APPEND);
                        $GLOBALS['TIER_PRICE_CSV_FILE'] = $csvFile;
                    } else {
                        // Clean output buffer for browser response
                        while (ob_get_level()) ob_end_clean();
                        
                        // Return JSON response for direct browser upload
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Tier price CSV generated successfully',
                            'file' => basename($csvFile ?? 'harko_tier_price.csv'),
                            'file_path' => $csvFile,
                            'records' => count($productCsvData),
                            'vendor' => 'harko'
                        ]);
                        exit;
                    }
                } catch (\Exception $e) {
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                    
                    // If called from import_processor.php, just log and continue
                    if (defined('IMPORT_PROCESSOR_MODE')) {
                        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] IMPORT_PROCESSOR_MODE: Exception caught, TIER_PRICE_CSV_FILE remains null\n", FILE_APPEND);
                    } else {
                        // Show error to browser
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Error loading file: ' . $e->getMessage()
                        ]);
                        exit;
                    }
                }
            }

        }
    
}
else if((isset($_POST['vendor']) && $_POST['vendor'] === 'newwave') || (isset($_POST['vendor_name']) && $_POST['vendor_name'] === 'newwave'))
{
    // Accept temp_file from mapping form, or vendor_file from direct upload
    $targetFile = null;
    if (!empty($_POST['temp_file'])) {
        // Clean up the temp_file path - remove ALL spaces from the filename
        $tempFileParam = $_POST['temp_file'];
        
        // Remove spaces from basename only (not from path)
        $dir = dirname($tempFileParam);
        $file = basename($tempFileParam);
        $file = str_replace(' ', '', $file); // Remove ALL spaces from filename
        $tempFileParam = $dir . '/' . $file;
        
        $tempFile = basename($tempFileParam);
        $tempDir = __DIR__ . '/uploads/temp/';
        $targetFile = $tempDir . $tempFile;
    } else if (!empty($_FILES['vendor_file']['tmp_name']) || !empty($_FILES['csv_file']['tmp_name'])) {
        // Accept either vendor_file or csv_file as upload field
        $fileField = !empty($_FILES['vendor_file']['tmp_name']) ? 'vendor_file' : 'csv_file';
        $fileTmp = $_FILES[$fileField]['tmp_name'];
        $originalName = basename($_FILES[$fileField]['name']);
        $tempDir = __DIR__ . '/uploads/temp/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        
        // Check if file is a real form upload (exists in sys_get_temp_dir) or already saved
        $isFormUpload = (strpos($fileTmp, sys_get_temp_dir()) === 0 || strpos($fileTmp, '/tmp') === 0);
        
        $tempFile = uniqid('vendor_') . '_' . $originalName;
        $targetPath = $tempDir . $tempFile;
        
        if ($isFormUpload && is_uploaded_file($fileTmp)) {
            // Real form upload - use move_uploaded_file
            move_uploaded_file($fileTmp, $targetPath);
        } elseif (file_exists($fileTmp)) {
            // Already saved file from import_processor - use copy
            copy($fileTmp, $targetPath);
        }
        
        $targetFile = $targetPath;
    } else {
        if (defined('IMPORT_PROCESSOR_MODE')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No file provided for newwave vendor.']);
        } else {
            die('❌ No file provided for newwave vendor.');
        }
        exit;
    }

    // Decode JSON
    if (!file_exists($targetFile)) {
        $error = 'File not found: ' . htmlspecialchars($targetFile);
        if (defined('IMPORT_PROCESSOR_MODE')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            die('❌ ' . $error);
        }
        exit;
    }
    
    $jsonContent = file_get_contents($targetFile);
    if ($jsonContent === false) {
        $error = 'Failed to read file: ' . htmlspecialchars($targetFile);
        if (defined('IMPORT_PROCESSOR_MODE')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            die('❌ ' . $error);
        }
        exit;
    }
    
    $jsonData = json_decode($jsonContent, true);
    if ($jsonData === null) {
        $error = 'Invalid JSON format: ' . json_last_error_msg();
        if (defined('IMPORT_PROCESSOR_MODE')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            die('❌ ' . $error);
        }
        exit;
    }
    
    if (empty($jsonData['result']) || !is_array($jsonData['result'])) {
        $error = 'Invalid JSON format. Missing "result" array.';
        if (defined('IMPORT_PROCESSOR_MODE')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            die('❌ ' . $error);
        }
        exit;
    }

    $productCsvData   = [];
    $productDefaultArray    =   [
        0 => '', 1 => '', 2 => '', 3 => $isActive, 4 => $stock, 5 => '', 6 => '', 7 => '', 8 => '', 9 => '',
        10 => '', 11 => $taxId, 12 => $taxRate, 13 => $taxName, 14 => '', 15 => '', 16 => '', 17 => '', 18 => '', 19 => '',
        20 => '', 21 => $salesChannelId, 22 => '', 23 => '', 24 => '', 25 => '', 26 => '', 27 => '', 28 => '', 29 => '',
        30 => '', 31 => '', 32 => '', 33 => '', 34 => '', 35 => '', 36 => '', 37 => '', 38 => '', 39 => '', 40 => ''
    ];
    foreach ($jsonData['result'] as $index => $product) {
        if (!is_array($product)) continue;
        $productNumber = isset($product['productNumber']) && is_string($product['productNumber']) ? trim($product['productNumber']) : '';
        $name = '';
        if (!empty($product['productName']) && is_array($product['productName'])) {
            $name = trim((string)($product['productName']['en'] ?? ''));
            if (empty($name)) {
                $name = trim((string)($product['productName']['de'] ?? ''));
            }
        }
        if (empty($name)) {
            $name = "Default Product Name";
        }
        $description = '';
        if (!empty($product['productText']) && is_array($product['productText'])) {
            $description = trim((string)($product['productText']['en'] ?? ''));
        }
        $brandName = trim((string)($product['productBrand'] ?? ''));
        $categoryName = '';
        if (!empty($product['productCategory']) && isset($product['productCategory'][0]['translation']) && is_array($product['productCategory'][0]['translation'])) {
            $categoryName = trim((string)($product['productCategory'][0]['translation']['en'] ?? ''));
        }
        $imageUrlsParent = [];
        if (!empty($product['pictures']) && is_array($product['pictures'])) {
            foreach ($product['pictures'] as $pic) {
                if (!empty($pic['imageUrl'])) $imageUrlsParent[] = $pic['imageUrl'];
            }
        }
        // iterate SKUs to extract sizes and create variant rows
        if (!empty($product['skus']) && is_array($product['skus'])) {
            foreach ($product['skus'] as $skuItem) {
                $variantSku = $skuItem['sku'] ?? '';
                if ($variantSku === '') continue;
                $skuSizeWeb = $skuItem['skuSize']['webtext'] ?? '';
                $skuSizeCode = $skuItem['skuSize']['size'] ?? '';
                $skuColor = $skuItem['skucolor'] ?? '';
                $availability = isset($skuItem['availability']) ? (int)$skuItem['availability'] : 0;
                $price = $skuItem['retailPrice']['price'] ?? null;
                $vRow = $productDefaultArray;
                $vRow[0] = Uuid::randomHex();
                $vRow[2] = $variantSku;
                $vRow[3] = $isActive;
                $vRow[4] = $availability;
                $vRow[5] = $name . " " . trim($skuItem['description'] ?? '');
                $vRow[6] = $description;
                if ($price !== null) {
                    $vRow[7] = $price;
                    $vRow[8] = $price;
                    $vRow[9] = $price;
                    $vRow[10] = $price;
                }
                $vRow[19] = $brandName;
                $vRow[23] = $skuColor . "|" . $skuSizeWeb;
                $imgList = $imageUrlsParent;
                if (!empty($imgList)) {
                    $vRow[15] = $imgList[0];
                    $vRow[16] = $name;
                    $vRow[17] = $name;
                } else {
                    $vRow[15] = '';
                    $vRow[16] = '';
                    $vRow[17] = '';
                }
                // Tier prices
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
                if (!empty($skuItem['tierPrices']) && is_array($skuItem['tierPrices'])) {
                    foreach ($skuItem['tierPrices'] as $tp) {
                        if (!is_array($tp)) {
                            continue;
                        }
                        $qty = $tp['quantity']
                            ?? $tp['minQuantity']
                            ?? $tp['quantityStart']
                            ?? $tp['from']
                            ?? $tp['qty']
                            ?? null;
                        $tpPrice = $tp['price']
                            ?? $tp['value']
                            ?? ($tp['gross'] ?? null)
                            ?? null;
                        if ($qty !== null && $tpPrice !== null) {
                            $prices[(int)$qty] = $tpPrice;
                        }
                    }
                } elseif ($price !== null) {
                    // If no tierPrices, keep only single-unit price
                    $prices[1] = $price;
                }
                $vRow[41] = $prices;
                $productCsvData[] = $vRow;
            }
        }
    }
    createTierPriceProductCsv($productCsvData, $container);
    exit;
}
echo $message. " Last Message";

function createTierPriceProductCsv($csvData, $container)
{
    $logFile = __DIR__ . '/import-logs/tier_price_debug.log';
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] createTierPriceProductCsv() called with " . count($csvData) . " products\n", FILE_APPEND);
    
    //print_r($csvData);
    $date    = date("dmy");
    $vendor  = $_POST['vendor'] ?? $_POST['vendor_name'] ?? 'default';
    
    // Normalize vendor name
    $vendor = strtolower(trim($vendor));
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Using vendor: " . $vendor . " (from POST)\n", FILE_APPEND);

    // CSV filename with vendor name
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

// Check if it's an AJAX request or from upload form
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$isFormUpload = !empty($_FILES['csv_file']['tmp_name']);

// Check if running from CLI (queue_worker) or web browser
$isCliMode = (php_sapi_name() === 'cli' || defined('STDIN'));

// Always save to disk (no browser download)
$outputDir = __DIR__ . '/csv-imports/tierprice/';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}
$outputPath = $outputDir . $csvName;
$proFile = fopen($outputPath, 'w');

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Saving CSV to: " . $outputPath . " (CLI: " . ($isCliMode ? 'YES' : 'NO') . ", AJAX: " . ($isAjaxRequest ? 'YES' : 'NO') . ", Form: " . ($isFormUpload ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    
    fputcsv($proFile, $productCsvHeader, ";");

    // Deduplicate CSV data by product number to prevent duplicate tier prices
    $seenProducts = [];
    
    foreach ($csvData as $csvDatakey => $csvDatavalue) {
        $articleNumber = $csvDatavalue[2] ?? null;
        
        // Skip duplicate products
        if (!empty($articleNumber) && in_array($articleNumber, $seenProducts)) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Skipping duplicate product: $articleNumber\n", FILE_APPEND);
            continue;
        }
        
        if (!empty($articleNumber)) {
            $seenProducts[] = $articleNumber;
        }
        
        $productRepository = $container->get('product.repository');
        $context           = Context::createDefaultContext();

        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Processing product: $articleNumber\n", FILE_APPEND);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $articleNumber));

        $product   = $productRepository->search($criteria, $context)->first();
        $productId = $product ? $product->getId() : null;
        
        if (!$productId) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] WARNING: Product not found in DB: $articleNumber\n", FILE_APPEND);
            // Skip this product or use empty product_id
            continue; // Skip products not found in database
        }
        
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Found product ID: $productId for $articleNumber\n", FILE_APPEND);

        $rule_id   = '0197e3c809b773c591e619aa39e52a29';
        
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
                        $grossPrice = floatval($priceValue);
                        $netPrice = round($grossPrice / 1.19, 2); // 19% tax
                        $row = [
                            Uuid::randomHex(),               // unique id for each tier price
                            $productId,                      // product id
                            $rule_id,                        // rule id
                            $netPrice,                       // net (calculated)
                            $grossPrice,                     // gross (from CSV)
                            $range["start"],                 // quantity_start
                            $range["end"] ?? null            // quantity_end
                        ];
                        fputcsv($proFile, $row, ";");
                    }
                }
            }
        } else if (!empty($vendor) && $vendor === 'harko') {
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
                        $grossPrice = floatval($csvDatavalue[41][$key]);
                        $netPrice = round($grossPrice / 1.19, 2); // 19% tax
                        $row = [
                            Uuid::randomHex(),           // unique id for each tier price
                            $productId ?? '',           // product id
                            $rule_id ?? '',             // rule_id
                            $netPrice,                   // price net (calculated)
                            $grossPrice,                 // price gross (from CSV)
                            $range["start"],             // quantity start
                            $range["end"] ?? ''          // quantity end
                        ];
                        fputcsv($proFile, $row, ";");
                    }
                }
            }
        } else if (!empty($vendor) && $vendor === 'newwave') {
            if (isset($csvDatavalue[41]) && is_array($csvDatavalue[41])) {
                $qtyKeys = array_map('intval', array_keys($csvDatavalue[41]));
                $qtyKeys = array_values(array_filter($qtyKeys, fn($v) => $v > 0));
                sort($qtyKeys);

                $standardRanges = [
                    1    => ["start" => 1,    "end" => 9],
                    10   => ["start" => 10,   "end" => 24],
                    25   => ["start" => 25,   "end" => 49],
                    50   => ["start" => 50,   "end" => 99],
                    100  => ["start" => 100,  "end" => 249],
                    250  => ["start" => 250,  "end" => 499],
                    500  => ["start" => 500,  "end" => 999],
                    1000 => ["start" => 1000, "end" => null],
                ];

                $isStandard = !array_diff($qtyKeys, array_keys($standardRanges));

                if (count($qtyKeys) === 1) {
                    $onlyQty = $qtyKeys[0];
                    $basePrice = floatval($csvDatavalue[41][$onlyQty]);
                    foreach ($standardRanges as $range) {
                        $netPrice = round($basePrice / 1.19, 2);
                        $row = [
                            Uuid::randomHex(),
                            $productId,
                            $rule_id,
                            $netPrice,
                            $basePrice,
                            $range["start"],
                            $range["end"]
                        ];
                        fputcsv($proFile, $row, ";");
                    }
                } elseif ($isStandard) {
                    foreach ($qtyKeys as $startQty) {
                        $range = $standardRanges[$startQty];
                        $grossPrice = floatval($csvDatavalue[41][$startQty]);
                        $netPrice = round($grossPrice / 1.19, 2);
                        $row = [
                            Uuid::randomHex(),
                            $productId,
                            $rule_id,
                            $netPrice,
                            $grossPrice,
                            $range["start"],
                            $range["end"]
                        ];
                        fputcsv($proFile, $row, ";");
                    }
                } else {
                    $count = count($qtyKeys);
                    for ($i = 0; $i < $count; $i++) {
                        $startQty = $qtyKeys[$i];
                        $endQty = ($i < $count - 1) ? ($qtyKeys[$i + 1] - 1) : null;
                        if ($startQty === 1 && $endQty === null) {
                            $endQty = 9;
                        }
                        $grossPrice = floatval($csvDatavalue[41][$startQty]);
                        $netPrice = round($grossPrice / 1.19, 2);
                        $row = [
                            Uuid::randomHex(),
                            $productId,
                            $rule_id,
                            $netPrice,
                            $grossPrice,
                            $startQty,
                            $endQty
                        ];
                        fputcsv($proFile, $row, ";");
                    }
                }
            }
        }

    }

    fclose($proFile);
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] CSV file closed. Total products: " . count($csvData) . "\n", FILE_APPEND);
    
    if ($isCliMode) {
        // Return file path for queue_worker to use
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Returning file path for CLI: " . $outputPath . "\n", FILE_APPEND);
        return $outputPath;
    } else {
        // Automatically import tier prices to database after CSV generation
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Starting automatic tier price import...\n", FILE_APPEND);
        
        $imported = 0;
        $errors = 0;
        
        try {
            require_once __DIR__ . '/db_config.php';
            $db = Database::getConnection();
            
            // Read CSV and import to database
            $handle = fopen($outputPath, 'r');
            fgetcsv($handle, 0, ';'); // Skip header
            
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                try {
                    $tierPriceId = $row[0];
                    $productId = $row[1];
                    $ruleId = $row[2];
                    $priceNet = floatval($row[3]);
                    $priceGross = floatval($row[4]);
                    $quantityStart = intval($row[5]);
                    $quantityEnd = !empty($row[6]) ? intval($row[6]) : null;
                    
                    $priceJson = json_encode([[
                        'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                        'net' => $priceNet,
                        'gross' => $priceGross,
                        'linked' => false
                    ]]);
                    
                    $sql = "INSERT INTO product_price 
                            (id, version_id, product_id, product_version_id, rule_id, price, quantity_start, quantity_end, created_at) 
                            VALUES 
                            (UNHEX(?), UNHEX('0fa91ce3e96a4bc2be4bd9ce752c3425'), UNHEX(?), UNHEX('0fa91ce3e96a4bc2be4bd9ce752c3425'), UNHEX(?), ?, ?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE 
                                price = VALUES(price), 
                                quantity_start = VALUES(quantity_start), 
                                quantity_end = VALUES(quantity_end)";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $tierPriceId,
                        $productId,
                        $ruleId,
                        $priceJson,
                        $quantityStart,
                        $quantityEnd
                    ]);
                    
                    $imported++;
                } catch (Exception $e) {
                    $errors++;
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Import error: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            
            fclose($handle);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Automatic import completed: $imported imported, $errors errors\n", FILE_APPEND);
            
        } catch (Exception $e) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Automatic import failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // Return JSON response for web requests (AJAX or form upload)
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Returning JSON response for web request\n", FILE_APPEND);
        
        // Clean any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Tier price CSV generated and $imported prices imported automatically!",
            'file' => $csvName,
            'file_path' => $outputPath,
            'records' => count($csvData),
            'imported' => $imported,
            'errors' => $errors,
            'vendor' => $vendor
        ]);
        exit;
    }
}
