<?php
// Increase memory and execution time for large image imports
ini_set('memory_limit', '4096M');
ini_set('max_execution_time', '600');
set_time_limit(600);
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
<p>For automatic product media import with progress tracking, please use:</p>
<h2><a href="vendor_import.php" style="color:#667eea;">Vendor Import System</a></h2>
<p>Redirecting automatically in 3 seconds...</p>
<p><small>This page now only processes imports via queue system.</small></p>
</body></html>';
        exit;
    }
    
    // If file uploaded directly, process through import_processor.php
    if (!empty($_FILES['csv_file']['tmp_name']) && !empty($_POST['vendor'])) {
        $_POST['import_type'] = 'images';
        $_POST['vendor_name'] = $_POST['vendor'];
        
        // Include import processor to handle queue creation
        require_once __DIR__ . '/import_processor.php';
        $processor = new ImportProcessor();
        $result = $processor->processRequest();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

error_reporting(E_ALL);
// Disable display_errors when called from import processor
if (defined('IMPORT_PROCESSOR_MODE')) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
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
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\CachedObjectStorageFactory;

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

// Reduce PhpSpreadsheet memory usage (only if supported)
if (method_exists(Settings::class, 'setCacheStorageMethod')) {
    Settings::setCacheStorageMethod(
        CachedObjectStorageFactory::cache_to_phpTemp,
        ['memoryCacheSize' => '256MB']
    );
}

$classLoader = require __DIR__ . '/../vendor/autoload.php';
 
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$debug  = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

$pluginLoader = null;
 
if (EnvironmentHelper::getVariable('COMPOSER_PLUGIN_LOADER', false)) {
    $pluginLoader = new ComposerPluginLoader($classLoader, null);
}

/* Product CSV ARRAY INDEX
0 => id  
1 => mediaFolderId 
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
$allowedTypes        =  ['text/csv', 'application/vnd.ms-excel', 'text/plain'];

if (isset($_POST['vendor']) && $_POST['vendor'] === 'ross') {
    // $file = $_FILES['csv_file'] ?? null;

    // if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    //     $message = "Upload failed with error code: " . ($file['error'] ?? 'no file');
    // } else {
    //     $fileType = mime_content_type($file['tmp_name']);

    //     if (!in_array($fileType, $allowedTypes)) {
    //         $message = "Invalid file type. Please upload a CSV / XLS file.";
    //     } else {
    //         try {
    //             $spreadsheet = IOFactory::load($file['tmp_name']);
    //             $sheet       = $spreadsheet->getActiveSheet();

    //             $rowCount        = 1;
    //             $header          = [];
    //             $productCsvData  = [];

    //             foreach ($sheet->getRowIterator() as $row) {
    //                 // skip unwanted rows
    //                 if (in_array($rowCount, [1,3,4,5])) {
    //                     $rowCount++;
    //                     continue;
    //                 }

    //                 $rowData      = [];
    //                 $cellIterator = $row->getCellIterator();
    //                 $cellIterator->setIterateOnlyExistingCells(false);

    //                 foreach ($cellIterator as $cell) {
    //                     if ($rowCount === 2) {
    //                         $header[] = $cell->getValue() ?: "Unknown{$rowCount}";
    //                     } else {
    //                         $rowData[] = $cell->getCalculatedValue();
    //                     }
    //                 }

    //                 if ($rowCount === 2) {
    //                     $rowCount++;
    //                     continue;
    //                 }

    //                 $data = array_combine($header, $rowData);

    //                 if (empty($data['Article Number'])) {
    //                     $rowCount++;
    //                     continue;
    //                 }
    //                 // Get productId from Shopware by productNumber (Article Number)
    //                 $context        =   Context::createDefaultContext();
    //                 $criteria = new Criteria();
    //                 $criteria->addFilter(new EqualsFilter('productNumber', $data['Article Number']));
    //                 $productRepo = $container->get('product.repository');
    //                 $product     = $productRepo->search($criteria, $context)->first();
    //                 if ($product) {
    //                     $productCsvData[] = [
    //                         $product->getId(),
    //                         "https://live.medialink.com/static/media/meeting.027690ec.jpg"
    //                     ];
    //                 }
    //                 //$productCsvData[] = $proArr;

    //                 $rowCount++;
    //             }

    //             createProductCsv($productCsvData, $container);
    //             exit;

    //         } catch (\Exception $e) {
    //             echo 'Error loading file: ', $e->getMessage();
    //         }
    //     }
    // }

    $file = $_FILES['csv_file'] ?? null;
    $imageBaseDir = $kernel->getProjectDir().'/public/my-imports/ROSS/';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $domain = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . ($file['error'] ?? 'no file');
    } else {
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $message = "Invalid file type. Please upload a CSV / XLS file.";
        } else {
            try {
                $reader = IOFactory::createReaderForFile($file['tmp_name']);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();

                $rowCount = 1;
                $header = [];
                $allRows = [];
                $colorGroups = [];
                foreach ($sheet->getRowIterator() as $row) {
                    if (in_array($rowCount, [1,3,4,5])) {
                        $rowCount++;
                        continue;
                    }
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        if ($rowCount === 2) {
                            $header[] = $cell->getValue() ?: "Unknown{$rowCount}";
                        } else {
                            $rowData[] = $cell->getCalculatedValue();
                        }
                    }
                    if ($rowCount === 2) {
                        $rowCount++;
                        continue;
                    }
                    $data = array_combine($header, $rowData);
                    $data = array_combine(array_map('trim', array_keys($data)), $data);
                    if (empty($data['Article Number'])) {
                        $rowCount++;
                        continue;
                    }
                    $colorCode = trim($data['Color Code'] ?? '');
                    if ($colorCode !== '') {
                        $colorGroups[$colorCode][] = $data;
                    }
                    $allRows[] = $data;
                    $rowCount++;
                }

                $productCsvData = [];
                $colorSwatchMapping = [];
                $rossProductSwatches = [];
                // For each color code group, collect images ONCE and assign to all products in that group
                foreach ($colorGroups as $colorCode => $rows) {
                    // Use the first row to determine folder structure
                    $firstRow = $rows[0];
                    $manufacturerCode = trim($firstRow['42 Manufacturer Code'] ?? $firstRow['Manufacturer Code'] ?? '');
                    $brandRaw = trim($firstRow['B & C'] ?? $firstRow['Brand'] ?? '');
                    // Only normalize for B & C, otherwise use as-is
                    if (strtoupper(str_replace(' ', '', $brandRaw)) === 'B&C') {
                        $brand = str_replace([' ', '&'], ['', ''], $brandRaw);
                    } else {
                        $brand = $brandRaw;
                    }
                    $styleRaw = trim($firstRow['Master Article Number'] ?? '');
                    $styleCode = preg_replace('/^M-FR/i', '', $styleRaw);
                    if (!$manufacturerCode || !$brand || !$styleCode) {
                        continue;
                    }
                    $folderName1 = $manufacturerCode . '_' . $brand;
                    $folderName2 = $manufacturerCode . '_' . str_replace(' ', '', $brandRaw);
                    $styleFolder1 = $imageBaseDir . $folderName1 . '/' . $styleCode . '/';
                    $styleFolder2 = $imageBaseDir . $folderName2 . '/' . $styleCode . '/';
                    $styleFolder = '';
                    if (is_dir($styleFolder1)) {
                        $styleFolder = $styleFolder1;
                    } else if (is_dir($styleFolder2)) {
                        $styleFolder = $styleFolder2;
                    } else {
                        continue;
                    }
                    // Collect all image folders for dynamic selection
                    $imageFolders = [
                        'model'   => $styleFolder . 'Webshop Modelshots/',
                        'front'   => $styleFolder . 'Flatshots/Front/',
                        'details' => $styleFolder . 'Flatshots/Details/',
                        'back'    => $styleFolder . 'Flatshots/Back/',
                        'side'    => $styleFolder . 'Flatshots/Side/'
                    ];
                    // Gather all images from all folders
                    $allImages = [];
                    foreach ($imageFolders as $type => $dir) {
                        if (is_dir($dir)) {
                            foreach (glob($dir . '*.jpg') as $img) {
                                $allImages[] = [
                                    'type' => $type,
                                    'path' => $img
                                ];
                            }
                        }
                    }

                    // Prefer images that belong to current color code
                    $colorScopedImages = $allImages;
                    $normalizedColorCode = trim((string)$colorCode);
                    if ($normalizedColorCode !== '') {
                        $colorNeedle = '_' . $normalizedColorCode . '_';
                        $matchedByColor = array_values(array_filter($allImages, static function ($imgInfo) use ($colorNeedle) {
                            $fileName = basename((string)($imgInfo['path'] ?? ''));
                            return stripos($fileName, $colorNeedle) !== false;
                        }));

                        if (!empty($matchedByColor)) {
                            $colorScopedImages = $matchedByColor;
                        }
                    }

                    // Dynamic cover: prefer model, else front, else any
                    $coverImage = '';
                    $coverFolder = '';
                    foreach ($colorScopedImages as $imgInfo) {
                        if ($imgInfo['type'] === 'model') {
                            $coverImage = $imgInfo['path'];
                            $coverFolder = $imageFolders['model'];
                            break;
                        }
                    }
                    if (!$coverImage) {
                        foreach ($colorScopedImages as $imgInfo) {
                            if ($imgInfo['type'] === 'front') {
                                $coverImage = $imgInfo['path'];
                                $coverFolder = $imageFolders['front'];
                                break;
                            }
                        }
                    }
                    if (!$coverImage && !empty($colorScopedImages)) {
                        $coverImage = $colorScopedImages[0]['path'];
                        $coverFolder = dirname($coverImage) . '/';
                    }
                    $colorName = trim((string)($firstRow['Color Name'] ?? $firstRow['40 Color Name'] ?? $firstRow['Color'] ?? $colorCode));
                    // Dynamic gallery: all except cover
                    $galleryImages = [];
                    foreach ($colorScopedImages as $imgInfo) {
                        if ($imgInfo['path'] !== $coverImage) {
                            $galleryImages[] = $imgInfo['path'];
                        }
                    }
                    // Rename logic (remove year, prepend fr_, set color code)
                    $renameImage = function($imgPath) use ($colorCode) {
                        $filename = basename($imgPath);
                        $filename = preg_replace('/-(20)?\d{2,4}(_\d{2})?/', '', $filename); // remove year
                        $filename = preg_replace('/\.(jpg|jpeg|png)$/i', '', $filename);
                        $filename = preg_replace('/^(fr_)+/', '', $filename);
                        $parts = explode('_', $filename);
                        if (count($parts) >= 3) {
                            $parts[2] = $colorCode;
                            $filename = implode('_', $parts);
                        }
                        // Remove trailing '-' after F (e.g., F- -> F)
                        $filename = preg_replace('/(F)-$/', 'F', $filename);
                        $filename = 'fr_' . $filename . '.jpg';
                        return $filename;
                    };
                    // Make URLs (full dynamic subfolder path after /my-imports/ROSS/)
                    $makeRelPath = function($imgPath) use ($imageBaseDir) {
                        // $imgPath: absolute path to image file
                        $relPath = str_replace($imageBaseDir, '', $imgPath);
                        $relPath = ltrim($relPath, '/');
                        return $relPath;
                    };
                    $domainPrefix = getenv('SHOPWARE_DOMAIN') ?: (defined('SHOPWARE_DOMAIN') ? SHOPWARE_DOMAIN : 'http://shopware678.local/');
                    // Cover image URL
                    if ($coverImage) {
                        $coverImageRenamed = $renameImage($coverImage);
                        $coverImageAbs = dirname($coverImage) . '/' . $coverImageRenamed;
                        $coverImageRel = $domainPrefix . 'my-imports/ROSS/' . $makeRelPath($coverImageAbs);
                    } else {
                        $coverImageRel = '';
                    }
                    // Gallery image URLs
                    $galleryImagesRel = '';
                    if (!empty($galleryImages)) {
                        $coverImageRenamed = $renameImage($coverImage);
                        $coverImageAbs = dirname($coverImage) . '/' . $coverImageRenamed;
                        $coverImageRelPath = $makeRelPath($coverImageAbs);
                        $galleryUrls = array_map(function($img) use ($renameImage, $makeRelPath, $domainPrefix) {
                            $renamed = $renameImage($img);
                            $imgAbs = dirname($img) . '/' . $renamed;
                            return $domainPrefix . 'my-imports/ROSS/' . $makeRelPath($imgAbs);
                        }, $galleryImages);
                        // Remove cover image URL and duplicates
                        $galleryUrls = array_unique(array_filter($galleryUrls, function($url) use ($domainPrefix, $coverImageRelPath) {
                            return $url !== $domainPrefix . 'my-imports/ROSS/' . $coverImageRelPath;
                        }));
                        $galleryImagesRel = implode('|', $galleryUrls);
                    }

                    if (!empty($coverImageRel) && !empty($colorName)) {
                        $colorSwatchMapping[$colorName] = $coverImageRel;
                    }
                    // Assign to all products in this color group
                    foreach ($rows as $data) {
                        $grossPrice = floatval($data[1] ?? 0);
                        $productNumber = trim((string)($data['Article Number'] ?? ''));

                        if ($productNumber !== '' && $colorName !== '' && $coverImageRel !== '') {
                            $rossProductSwatches[] = [
                                'product_number' => $productNumber,
                                'color_name' => $colorName,
                                'image_url' => $coverImageRel,
                                'source_path' => $coverImage
                            ];
                        }

                        $productCsvData[] = [
                            'id' => Uuid::randomHex(),
                            'name' =>  trim($data['Web Shop Article Name (Product Page, Listing)'] ?? ''),
                            'stock' => 10,
                            'tax_id' => '0197e3c80947729bbb9c9ca9f3238a05',
                            'price' => $grossPrice,
                            'product_number' => $productNumber,
                            'swatches_image' => $coverImageRel,
                            'cover_image' => $coverImageRel,
                            'gallery_images' => $galleryImagesRel
                        ];
                    }
                }

                $swatchOutputDir = __DIR__ . '/csv-imports/swatches/';
                if (!is_dir($swatchOutputDir)) {
                    if (!@mkdir($swatchOutputDir, 0777, true) && !is_dir($swatchOutputDir)) {
                        error_log("[PRODUCT MEDIA] Failed to create swatch output dir: {$swatchOutputDir}");
                    }
                }

                $swatchCsvPath = createPropertySwatchCsv($colorSwatchMapping, $container);
                if ($swatchCsvPath) {
                    error_log("[PRODUCT MEDIA] Ross swatch CSV generated: {$swatchCsvPath}");
                }

                if (!empty($rossProductSwatches)) {
                    $swatchResult = processRossProductSwatches($rossProductSwatches, $container, $connection);
                    if ($swatchResult) {
                        $assigned = (int)($swatchResult['assigned'] ?? 0);
                        $errors = (int)($swatchResult['errors'] ?? 0);
                        error_log("[PRODUCT MEDIA] Ross product-wise swatches assigned: {$assigned}, errors: {$errors}");
                        if (!empty($swatchResult['messages'])) {
                            $sample = array_slice($swatchResult['messages'], 0, 5);
                            foreach ($sample as $msg) {
                                error_log("[PRODUCT MEDIA] Ross swatch detail: {$msg}");
                            }
                        }
                    }
                }

                if (!empty($productCsvData)) {
                    $csvFilePath = createProductCsv($productCsvData, $container);
                    if ($csvFilePath && file_exists($csvFilePath)) {
                        $jobId = $GLOBALS['MEDIA_JOB_ID'] ?? null;
                        if (!defined('IMPORT_PROCESSOR_MODE') && $jobId) {
                            $worker = PHP_BINARY . ' ' . __DIR__ . '/process_csv_worker.php job_id=' . escapeshellarg($jobId) . ' > /dev/null 2>&1 &';
                            exec($worker);
                        }
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'file_path' => $csvFilePath,
                            'products' => count($productCsvData),
                            'job_id' => $jobId,
                            'auto_started' => !defined('IMPORT_PROCESSOR_MODE') && !empty($jobId)
                        ]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to generate CSV file'
                        ]);
                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'No products found in ROSS CSV'
                    ]);
                }
                exit;
            } catch (\Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error loading file: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
}else if(isset($_POST['vendor']) && $_POST['vendor'] === 'harko'){
    $file = $_FILES['csv_file'] ?? null;
    $imageBaseDir = $kernel->getProjectDir().'/public/my-imports/Hakro/';
    // ✅ Dynamically detect domain
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $domain = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');   
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . ($file['error'] ?? 'no file');
    } else {
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            $message = "Invalid file type. Please upload a CSV / XLS file.";
        } else {
            try {
                $reader = IOFactory::createReaderForFile($file['tmp_name']);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();

                $rowCount = 1;
                $header = [];
                $productCsvData = [];
                $colorSwatchMapping = [];
                $harkoProductSwatches = [];
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    if ($rowCount === 1) {
                        // First row is header
                        foreach ($cellIterator as $cell) {
                            $header[] = $cell->getValue() ?: "Unknown{$rowCount}";
                        }
                        $rowCount++;
                        continue;
                    }

                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getCalculatedValue();
                    }

                    $data = array_combine($header, $rowData);
                    $data = array_combine(
                        array_map('trim', array_keys($data)),
                        $data
                    );
                    
                    if (empty($data['Article Number'])) {
                        $rowCount++;
                        continue;
                    }

                    // --- EXTRACT PRODUCT DATA (same as create_product.php) ---
                    $articleShortCode = trim((string)($data['1 Article Number Short'] ?? ''));
                    $photoShort = trim((string)($data['2 Photo Number Short'] ?? ''));
                    $productNumber = trim((string)($data['Article Number'] ?? ''));
                    
                    // ✅ Product name (from create_product.php logic)
                    $productName = $data['ERP Article Name'] ?? $data['3 Article Name'] ?? $productNumber;
                    
                    // ✅ Stock (default: 10)
                    $stock = 10;
                    
                    // ✅ Price (from "Price 1" - same as create_product.php)
                    $price = $data['Price 1'] ?? 0;
                    if (!is_numeric($price)) {
                        $price = 0;
                    }
                    
                    // ✅ Tax ID (default)
                    $taxId = '0197e3c80947729bbb9c9ca9f3238a05';

                    // --- IMAGE LOGIC (same as before) ---
                   
                    $colorName = trim((string)($data['5. Color Name'] ?? $data['5 Color Name'] ?? $data['5 Color'] ?? $data['Color Name'] ?? $data['Color'] ?? ''));
                    if ($colorName === '' && $productNumber !== '') {
                        $dbColorName = (string)$connection->fetchOne(
                            "SELECT pgot.name
                             FROM product p
                             JOIN product_option po ON po.product_id = p.id
                             JOIN property_group_option pgo ON pgo.id = po.property_group_option_id
                             JOIN property_group_option_translation pgot ON pgot.property_group_option_id = pgo.id
                             WHERE p.product_number = ?
                               AND LOWER(HEX(pgo.property_group_id)) = ?
                             ORDER BY pgot.created_at DESC
                             LIMIT 1",
                            [$productNumber, strtolower('0198135f7a2f7600a44ed9ab388d112a')]
                        );
                        if ($dbColorName !== '') {
                            $colorName = trim($dbColorName);
                        }
                    }
                    if ($colorName === '') {
                        $colorName = 'COLOR-' . ($photoShort !== '' ? $photoShort : $productNumber);
                    }
                    $colorCode = $colorName; 
                    
                    $articleImageDir = rtrim($imageBaseDir, '/') . '/' . $articleShortCode . '/';

                    // Cover: prefer m_model_p_{articleShortCode}_{photoShort}_01.jpg with fallback
                    $coverImage = '';
                    $coverCandidates = [
                        $articleImageDir . 'm_model_p_' . $articleShortCode . '_' . $photoShort . '_01.jpg',
                        $articleImageDir . 'm_model_p_' . $photoShort . '_01.jpg',
                    ];
                    foreach ($coverCandidates as $coverPath) {
                        if (file_exists($coverPath)) {
                            $coverImage = $coverPath;
                            break;
                        }
                    }

                    // Gallery: prefer m_item_p_{articleShortCode}_{photoShort}_0N.jpg with fallback
                    $galleryImages = [];
                    for ($imgCount = 1; $imgCount <= 8; $imgCount++) {
                        $suffix = str_pad((string)$imgCount, 2, '0', STR_PAD_LEFT);
                        $galleryCandidates = [
                            $articleImageDir . 'm_item_p_' . $articleShortCode . '_' . $photoShort . '_' . $suffix . '.jpg',
                            $articleImageDir . 'm_item_p_' . $photoShort . '_' . $suffix . '.jpg',
                        ];
                        foreach ($galleryCandidates as $itemPath) {
                            if (file_exists($itemPath) && !in_array($itemPath, $galleryImages, true)) {
                                $galleryImages[] = $itemPath;
                                break;
                            }
                        }
                    }

                    if (empty($galleryImages) && is_dir($articleImageDir)) {
                        $globCandidates = glob($articleImageDir . 'm_item_p_*_' . $photoShort . '_*.jpg') ?: [];
                        sort($globCandidates);
                        foreach ($globCandidates as $itemPath) {
                            if (!in_array($itemPath, $galleryImages, true)) {
                                $galleryImages[] = $itemPath;
                            }
                        }
                    }

                    // Make all image paths relative to $imageBaseDir
                    $makeRelative = function($path) use ($imageBaseDir, $domain) {
                        if ($path && strpos($path, $imageBaseDir) === 0) {
                            $relativePath = ltrim(substr($path, strlen($imageBaseDir)), '/');
                            // ✅ Prepend domain + /my-imports/Hakro/ before the path
                            return $domain . '/my-imports/Hakro/' . $relativePath;
                        }
                        return $path;
                    };
                    
                    // Swatch image: first gallery image (item_01) with cover fallback
                    $swatchesImage = $galleryImages[0] ?? $coverImage;
                    $swatchesImageRel = $makeRelative($swatchesImage);
                    $coverImageRel = $makeRelative($coverImage);
                    $galleryImagesRel = '';
                    if (!empty($galleryImages)) {
                        $galleryImagesRel = implode('|', array_map($makeRelative, $galleryImages));
                    }
                    if (!empty($swatchesImageRel) && !empty($colorName)) {
                        $colorSwatchMapping[$colorName] = $swatchesImageRel;
                    }
                    if (!empty($swatchesImageRel) && !empty($colorName) && !empty($productNumber)) {
                        $harkoProductSwatches[] = [
                            'product_number' => $productNumber,
                            'color_name' => $colorName,
                            'image_url' => $swatchesImageRel,
                            'source_path' => $swatchesImage
                        ];
                    }

                    // ✅ Create complete product data array (matching createProductCsv expectations)
                    $productCsvData[] = [
                        'id' => Uuid::randomHex(),
                        'name' => $productName,
                        'stock' => $stock,
                        'tax_id' => $taxId,
                        'price' => (float)$price,
                        'product_number' => $productNumber,
                        'swatches_image' => $swatchesImageRel,
                        'cover_image' => $coverImageRel,
                        'gallery_images' => $galleryImagesRel
                    ];

                    $rowCount++;
                }
                $swatchOutputDir = __DIR__ . '/csv-imports/swatches/';
                if (!is_dir($swatchOutputDir)) {
                    if (!@mkdir($swatchOutputDir, 0777, true) && !is_dir($swatchOutputDir)) {
                        error_log("[PRODUCT MEDIA] Failed to create swatch output dir: {$swatchOutputDir}");
                    }
                }

                $swatchCsvPath = createPropertySwatchCsv($colorSwatchMapping, $container);
                if ($swatchCsvPath) {
                    error_log("[PRODUCT MEDIA] Harko swatch CSV generated: {$swatchCsvPath}");
                }

                if (!empty($harkoProductSwatches)) {

                    $swatchResult = processRossProductSwatches($harkoProductSwatches, $container, $connection);
                    if ($swatchResult) {
                        $assigned = (int)($swatchResult['assigned'] ?? 0);
                        $errors = (int)($swatchResult['errors'] ?? 0);
                        error_log("[PRODUCT MEDIA] Harko product-wise swatches assigned: {$assigned}, errors: {$errors}");
                    }
                }
                // ✅ Generate complete CSV with product data + media
                if (!empty($productCsvData)) {
                    // Generate CSV file
                    $csvFilePath = createProductCsv($productCsvData, $container);
                    
                    // Return CSV path for queue processing
                    if ($csvFilePath && file_exists($csvFilePath)) {
                        $jobId = $GLOBALS['MEDIA_JOB_ID'] ?? null;
                        if (!defined('IMPORT_PROCESSOR_MODE') && $jobId) {
                            $worker = PHP_BINARY . ' ' . __DIR__ . '/process_csv_worker.php job_id=' . escapeshellarg($jobId) . ' > /dev/null 2>&1 &';
                            exec($worker);
                        }
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'file_path' => $csvFilePath,
                            'products' => count($productCsvData),
                            'job_id' => $jobId,
                            'auto_started' => !defined('IMPORT_PROCESSOR_MODE') && !empty($jobId)
                        ]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to generate CSV file'
                        ]);
                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'No products found in Harko XLS'
                    ]);
                }
                
                exit;

            } catch (\Exception $e) {
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
else if (isset($_POST['vendor']) && $_POST['vendor'] === 'newwave') {
    $file = $_FILES['csv_file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        die("❌ Upload failed with error code: " . ($file['error'] ?? 'no file'));
    }

    $fileTmpPath = $file['tmp_name'];
    $targetDir = __DIR__ . '/uploads/';
    $targetFile = $targetDir . basename($file['name']);

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Try to read directly from temp file first
    $jsonContent = file_get_contents($fileTmpPath);
    
    // Also save to uploads directory for reference
    if (move_uploaded_file($fileTmpPath, $targetFile)) {
        $jsonContent = file_get_contents($targetFile);
    }
    // If move fails, continue with temp file content
    
    // Check if file is empty
    if (empty($jsonContent)) {
        die('❌ File is empty. Size: ' . filesize($targetFile) . ' bytes. Path: ' . $targetFile);
    }
    
    // Show preview of file content for debugging
    $preview = mb_substr($jsonContent, 0, 200);
    
    $jsonData = json_decode($jsonContent, true);
    
    if ($jsonData === null) {
        $error = json_last_error_msg();
        die('❌ Invalid JSON file. Error: ' . $error . '<br><br>File preview:<br><pre>' . htmlspecialchars($preview) . '...</pre><br><br>Please ensure the file contains valid JSON format.');
    }
    
    // Handle different JSON formats
    $products = [];
    if (is_array($jsonData)) {
        if (isset($jsonData['result']) && is_array($jsonData['result'])) {
            // Format: {"result": [...]}
            $products = $jsonData['result'];
        } elseif (isset($jsonData['productNumber']) && is_string($jsonData['productNumber'])) {
            // Format: single product object {...}
            $products = [$jsonData];
        } elseif (isset($jsonData[0]) && is_array($jsonData[0])) {
            // Format: direct array [...]
            $products = $jsonData;
        } else {
            // Try to extract products from any array in the JSON
            foreach ($jsonData as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    // Check if first item looks like a product
                    $firstItem = is_array($value) ? reset($value) : $value;
                    if (is_array($firstItem) && isset($firstItem['productNumber'])) {
                        $products = $value;
                        break;
                    }
                }
            }
        }
    }
    
    if (empty($products)) {
        die('❌ No valid products found in JSON. Check file structure.');
    }

    //echo "✅ JSON file parsed successfully (" . count($products) . " items)<br>";

    // --- Prepare arrays ---
    $productCsvData   = [];
    $sizeProperyData  = [];
    $colorProperyData = [];
    $newBrands        = [];
    $newBrandsNameArr = [];
    $newCategory      = [];
    $parentIds        = [];
    $parentIdSkuArr   = [];
    $newwaveProductSwatches = [];

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
        40 => '',
        41 => '',
        42 => '' // swatches_image
    ];

    // 🎯 Target product number
    //$targetProductNumber = '0200905';

        foreach ($products as $index => $product) {
            if (!is_array($product)) {
                // Skip non-array items silently
                continue;
            }

            $productNumber = isset($product['productNumber']) && is_string($product['productNumber'])
                ? trim($product['productNumber'])
                : '';

            /*if ($productNumber !== $targetProductNumber) {
                continue;
            }*/

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
                    $skuColor = trim((string)($skuItem['skucolor'] ?? ''));
                    $resolvedSkuColorKey = $skuColor;
                    if (!isset($colorsFound[$resolvedSkuColorKey]) && $resolvedSkuColorKey !== '') {
                        $normalizedSkuColor = ltrim($resolvedSkuColorKey, '0');
                        foreach ($colorsFound as $existingColorCode => $existingColorData) {
                            if (ltrim((string)$existingColorCode, '0') === $normalizedSkuColor) {
                                $resolvedSkuColorKey = (string)$existingColorCode;
                                break;
                            }
                        }
                    }

                    if (!isset($colorsFound[$resolvedSkuColorKey])) {
                        // fallback: try product-level filterColor or variation mapping
                        $colorsFound[$resolvedSkuColorKey] = [
                            'name' => $resolvedSkuColorKey,
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
                    $colorNameForSku = trim((string)($colorsFound[$resolvedSkuColorKey]['name'] ?? $resolvedSkuColorKey));
                    $vRow[23] = $colorNameForSku . "|" . $skuSizeWeb;

                    // keep friendly option names for createPropertyOptions usage later
                    $vRow['color_opt_name'] = $colorNameForSku;
                    $vRow['size_opt_name']  = $skuSizeWeb;

                    // images: use folder-based structure from /my-imports/NEWWAVE/[product]/[item_number]/
                    // Item number is productNumber-colorCode (e.g., 010177-55), NOT the full SKU with size
                    $imgList = [];
                    $productFolder = $kernel->getProjectDir() . '/public/my-imports/NEWWAVE/' . $productNumber;
                    $itemNumber = $productNumber . '-' . $resolvedSkuColorKey; // e.g., 010177-55
                    $itemFolder = $productFolder . '/' . $itemNumber;
                    
                    // Debug logging (remove after testing)
                    error_log("Checking folder: $itemFolder for SKU: $variantSku");
                    
                    // Check if item folder exists (color variant folder, not SKU folder)
                    if (is_dir($itemFolder)) {
                        $itemImages = glob($itemFolder . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
                        error_log("Found " . count($itemImages) . " images in $itemFolder");
                        if (!empty($itemImages)) {
                            // Sort images: identify front by specific patterns
                            $frontImages = [];
                            $otherImages = [];
                            
                            foreach ($itemImages as $imgPath) {
                                $filename = basename($imgPath);
                                // Check if filename contains patterns that indicate FRONT image
                                // Pattern: _F. or _front OR starts with product and ends with _front
                                if (preg_match('/_F\.|_front|front\.jpg/i', $filename)) {
                                    $frontImages[] = $imgPath;
                                } else {
                                    $otherImages[] = $imgPath;
                                }
                            }
                            
                            // Sort each group alphabetically
                            sort($frontImages);
                            sort($otherImages);
                            
                            // Combine: Front first, then others (Back, Left, Right)
                            $sortedImages = array_merge($frontImages, $otherImages);
                            
                            // Convert absolute paths to web-accessible URLs
                            foreach ($sortedImages as $imgPath) {
                                $webPath = str_replace($kernel->getProjectDir() . '/public', '', $imgPath);
                                $imgList[] = $webPath;
                                error_log("Added image: $webPath");
                            }
                        }
                    } else {
                        error_log("Folder does not exist: $itemFolder");
                    }
                    
                    // Fallback: try variation color pictures from API
                    if (empty($imgList) && !empty($colorsFound[$resolvedSkuColorKey]['pictures'])) {
                        $imgList = $colorsFound[$resolvedSkuColorKey]['pictures'];
                        error_log("Using API fallback images for $variantSku");
                    }
                    
                    // Final fallback: parent pictures
                    if (empty($imgList) && !empty($imageUrlsParent)) {
                        $imgList = $imageUrlsParent;
                        error_log("Using parent fallback images for $variantSku");
                    }
                    
                    // Set image fields
                    if (!empty($imgList)) {
                        // Cover image (first image)
                        $vRow[15] = $imgList[0];   // cover_media_url
                        $vRow[16] = $variantSku;   // cover_media_title (REQUIRED)
                        $vRow[17] = $name . ' - ' . $colorNameForSku . ' ' . $skuSizeWeb;  // cover_media_alt (REQUIRED)

                        // Gallery images (all images)
                        $vRow[41] = implode('|', array_unique($imgList));

                        // Swatch image: always prefer FRONT image (_F / front), fallback to first image
                        $frontSwatchImage = '';
                        foreach ($imgList as $candidateImage) {
                            $candidateName = basename((string)$candidateImage);
                            if (preg_match('/_F(\.|_|-|$)|_front|front\./i', $candidateName)) {
                                $frontSwatchImage = $candidateImage;
                                break;
                            }
                        }
                        $vRow[42] = $frontSwatchImage !== '' ? $frontSwatchImage : ($imgList[0] ?? '');

                        $isNumericOnlyColor = (bool)preg_match('/^\d+$/', $colorNameForSku);
                        $isLocalNewwaveSwatch = strpos((string)$vRow[42], '/my-imports/NEWWAVE/') !== false;

                        if (!empty($vRow[42]) && !empty($colorNameForSku) && !$isNumericOnlyColor && $isLocalNewwaveSwatch && !empty($variantSku)) {
                            $newwaveProductSwatches[] = [
                                'product_number' => $variantSku,
                                'color_name' => $colorNameForSku,
                                'image_url' => $vRow[42],
                                'source_path' => ''
                            ];
                        } else {
                            error_log("Skipped Newwave swatch row for {$variantSku}: color={$colorNameForSku}, image={$vRow[42]}");
                        }
                    } else {
                        $vRow[15] = '';
                        $vRow[16] = '';
                        $vRow[17] = '';
                        $vRow[41] = '';
                        $vRow[42] = '';
                    }

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
                $parentRow[16] = $imageUrlsParent[0] ?? '';                       // cover_media_title (REQUIRED)
                $parentRow[17] = $name;                       // cover_media_alt   (REQUIRED)

                // Handle Gallery Images
                // If there are multiple images in the array, join them with a pipe (|)
                $parentRow[41] = implode('|', array_unique($imageUrlsParent)); // Gallery images (unique URLs)
            } else {
                // In case no images are available, leave the cover and gallery image fields blank (or set a default)
                $parentRow[15] = '';   // cover_media_url
                $parentRow[16] = '';   // cover_media_title
                $parentRow[17] = '';   // cover_media_alt

                // Optionally, clear gallery images if there are no cover images
                $parentRow[41] = '';   // Gallery images (empty if no images available)
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

        if (!empty($newwaveProductSwatches)) {
            $newwaveColorSwatchMapping = [];
            foreach ($newwaveProductSwatches as $swatchRow) {
                $swatchColor = trim((string)($swatchRow['color_name'] ?? ''));
                $swatchImage = trim((string)($swatchRow['image_url'] ?? ''));
                if ($swatchColor !== '' && $swatchImage !== '' && !isset($newwaveColorSwatchMapping[$swatchColor])) {
                    $newwaveColorSwatchMapping[$swatchColor] = $swatchImage;
                }
            }

            if (!empty($newwaveColorSwatchMapping)) {
                $swatchCsvPath = createPropertySwatchCsv($newwaveColorSwatchMapping, $container);
                if ($swatchCsvPath) {
                    error_log("[PRODUCT MEDIA] Newwave swatch CSV generated: {$swatchCsvPath}");
                }
            }

            $swatchResult = processRossProductSwatches($newwaveProductSwatches, $container, $connection);
            if ($swatchResult) {
                $assigned = (int)($swatchResult['assigned'] ?? 0);
                $errors = (int)($swatchResult['errors'] ?? 0);
                error_log("[PRODUCT MEDIA] Newwave product-wise swatches assigned: {$assigned}, errors: {$errors}");
            }
        }

        if (!empty($productCsvData)) {
            // Hand-off to your CSV creator
            $csvFilePath = createProductCsv($productCsvData, $container);

            // Return CSV path for queue processing
            if ($csvFilePath && file_exists($csvFilePath)) {
                $jobId = $GLOBALS['MEDIA_JOB_ID'] ?? null;
                if (!defined('IMPORT_PROCESSOR_MODE') && $jobId) {
                    $worker = PHP_BINARY . ' ' . __DIR__ . '/process_csv_worker.php job_id=' . escapeshellarg($jobId) . ' > /dev/null 2>&1 &';
                    exec($worker);
                }
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'file_path' => $csvFilePath,
                    'products' => count($productCsvData),
                    'job_id' => $jobId,
                    'auto_started' => !defined('IMPORT_PROCESSOR_MODE') && !empty($jobId)
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to generate CSV file'
                ]);
            }

        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No products found in Newwave JSON'
            ]);
        }

        exit;

// ===== NEWWAVE FOLDER-BASED MEDIA IMPORT =====
} 
// else if (isset($_POST['vendor']) && $_POST['vendor'] === 'newwave') {
//     // Generate CSV from folder structure: /NEWWAVE/[product_number]/[item_number]/images
//     $baseDir = __DIR__ . '/my-imports/NEWWAVE';
    
//     if (!is_dir($baseDir)) {
//         header('Content-Type: application/json');
//         echo json_encode([
//             'success' => false,
//             'message' => "❌ Newwave folder not found: $baseDir"
//         ]);
//         exit;
//     }

//     echo "📂 Scanning Newwave folder structure...\n";
    
//     $productCsvData = [];
//     $totalFiles = 0;
//     $totalProducts = 0;

//     // Scan product folders (e.g., 1913411, 1916629)
//     $productDirs = scandir($baseDir);
//     $productDirs = array_filter($productDirs, function($item) use ($baseDir) {
//         return $item !== '.' && $item !== '..' && is_dir($baseDir . '/' . $item);
//     });

//     foreach ($productDirs as $productNumber) {
//         $productPath = $baseDir . '/' . $productNumber;
        
//         // Scan variant folders (e.g., 1913411-346900, 1913411-999900)
//         $variantDirs = scandir($productPath);
//         $variantDirs = array_filter($variantDirs, function($item) use ($productPath) {
//             return $item !== '.' && $item !== '..' && is_dir($productPath . '/' . $item);
//         });

//         foreach ($variantDirs as $itemNumber) {
//             $itemPath = $productPath . '/' . $itemNumber;
            
//             // Scan for image files
//             $files = scandir($itemPath);
//             $imageFiles = array_filter($files, function($file) {
//                 $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
//                 return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
//             });

//             // Sort files for consistent ordering
//             sort($imageFiles);

//             if (count($imageFiles) > 0) {
//                 // Build relative paths for images
//                 $coverImagePath = '';
//                 $galleryImagePaths = [];
                
//                 foreach ($imageFiles as $idx => $imageFile) {
//                     $relativePath = 'my-imports/NEWWAVE/' . $productNumber . '/' . $itemNumber . '/' . $imageFile;
                    
//                     if ($idx === 0) {
//                         // First image is cover
//                         $coverImagePath = $relativePath;
//                     } else {
//                         // Rest are gallery
//                         $galleryImagePaths[] = $relativePath;
//                     }
//                 }

//                 // Create product data array matching productDefaultArray structure
//                 $productRow = [
//                     0 => Uuid::randomHex(),              // id
//                     1 => '',                              // parent_id (empty)
//                     2 => $itemNumber,                     // product_number (full SKU)
//                     3 => $isActive,                       // active
//                     4 => 10,                              // stock
//                     5 => 'Product ' . $itemNumber,        // name
//                     6 => '',                              // description
//                     7 => 0,                               // price
//                     8 => 0,                               // net_price
//                     9 => 0,                               // gross_price
//                     10 => 0,                              // price_value
//                     11 => $taxId,                         // tax_id
//                     12 => $taxRate,                       // tax_rate
//                     13 => $taxName,                       // tax_name
//                     14 => '',                             // parent_link
//                     15 => $coverImagePath,                // cover_image_url
//                     16 => 'Product ' . $itemNumber,       // cover_image_title
//                     17 => 'Product ' . $itemNumber,       // cover_image_alt
//                     18 => '',                             // manufacturer_id
//                     19 => '',                             // manufacturer_name
//                     20 => '',                             // category_id
//                     21 => $salesChannelId,                // sales_channel_id
//                     22 => '', 23 => '', 24 => '', 25 => '', 26 => '', 27 => '', 28 => '', 29 => '',
//                     30 => '', 31 => '', 32 => '', 33 => '', 34 => '', 35 => '',
//                     36 => '', 37 => '', 38 => '', 39 => '', 40 => '',
//                     41 => implode('|', $galleryImagePaths),  // gallery_images
//                     42 => ''                              // swatches_image
//                 ];

//                 $productCsvData[] = $productRow;
//                 $totalFiles += count($imageFiles);
//                 $totalProducts++;
//                 echo "✅ Variant: $itemNumber | Images: " . count($imageFiles) . "\n";
//             }
//         }
//     }

//     echo "\n📊 Summary:\n";
//     echo "   Total Variants: $totalProducts\n";
//     echo "   Total Images: $totalFiles\n\n";

//     if (count($productCsvData) > 0) {
//         // Generate CSV using existing createProductCsv function
//         $csvFilePath = createProductCsv($productCsvData, $container);
        
//         // Return JSON response for automatic import queue processing
//         if ($csvFilePath && file_exists($csvFilePath)) {
//             $jobId = $GLOBALS['MEDIA_JOB_ID'] ?? null;
            
//             // Auto-start worker if not in processor mode
//             if (!defined('IMPORT_PROCESSOR_MODE') && $jobId) {
//                 $worker = PHP_BINARY . ' ' . __DIR__ . '/process_csv_worker.php job_id=' . escapeshellarg($jobId) . ' > /dev/null 2>&1 &';
//                 exec($worker);
//             }
            
//             header('Content-Type: application/json');
//             echo json_encode([
//                 'success' => true,
//                 'message' => "✅ Media CSV generated from Newwave folder structure",
//                 'file_path' => $csvFilePath,
//                 'total_products' => $totalProducts,
//                 'total_images' => $totalFiles,
//                 'job_id' => $jobId,
//                 'auto_started' => !defined('IMPORT_PROCESSOR_MODE') && !empty($jobId)
//             ]);
//         } else {
//             header('Content-Type: application/json');
//             echo json_encode([
//                 'success' => false,
//                 'message' => 'Failed to generate CSV file from folder structure'
//             ]);
//         }
//     } else {
//         header('Content-Type: application/json');
//         echo json_encode([
//             'success' => false,
//             'message' => '❌ No images found in Newwave folder structure'
//         ]);
//     }

//     exit;
// }

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


// function createProductCsv($csvData, $container){
//     //print_r($csvData);die;
//     $date       =  date("dmy");
//     $vendoer    = $_POST['vendor'];
//     $csvName    = $vendoer."_media_import_{$date}.csv";
//     $manufacturerList   =   getManufacturers($container);
//     $colorOptions       =   getPropertyOptions('Color', $container);
//     $sizeOptions        =   getPropertyOptions('Size', $container);    
//     $productCsvHeader   =   [
//         'product_number',
//         'swatches_image',
//         'cover_image',
//         'gallery_images'
//     ];

//     header('Content-Type: text/csv; charset=utf-8');
//     header('Content-Disposition: attachment; filename="' . $csvName . '"');

//     $proFile = fopen('php://output', 'w');
//     fputcsv($proFile, $productCsvHeader, ";");
//     foreach ($csvData as $csvDatavalue) {
//         // Add product_number (2), then media columns: swatches_image (42), cover_image (15), gallery_images (41)
//         $mediaRow = [
//             $csvDatavalue[0] ?? '',    // product_number
//             $csvDatavalue[1] ?? '',   // swatches_image
//             $csvDatavalue[2] ?? '',   // cover_image
//             $csvDatavalue[3] ?? ''    // gallery_images
//         ];
//         fputcsv($proFile, $mediaRow, ";");
//     }

// fclose($proFile);
// exit;
// }


function createPropertySwatchCsv($colorSwatchData, $container) {
    $date = date("dmy");
    $vendor = $_POST['vendor'] ?? 'media';
    $csvName = $vendor . "_color_swatches_{$date}.csv";
    
    // ✅ Property option CSV structure for swatch images
    $propertyHeader = [
        'id',           // property option ID (will be matched by name)
        'name',         // color name (to match existing property option)
        'groupId',      // property group ID (Color group)
        'media'         // swatch image URL
    ];

    $outputDir = __DIR__ . '/csv-imports/swatches/';
    if (!is_dir($outputDir)) {
        if (!@mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
            throw new \RuntimeException('Unable to create swatch output directory: ' . $outputDir);
        }
    }

    if (!is_writable($outputDir)) {
        throw new \RuntimeException('Swatch output directory is not writable: ' . $outputDir);
    }

    $outputPath = $outputDir . $csvName;
    $propFile = fopen($outputPath, 'w');
    if ($propFile === false) {
        throw new \RuntimeException('Unable to open swatch CSV for writing: ' . $outputPath);
    }
    fputcsv($propFile, $propertyHeader, ";");
    
    // ✅ Color property group ID
    $colorPropertyGrpId = '0198135f7a2f7600a44ed9ab388d112a';
    
    // ✅ Get existing color options to match IDs
    $context = Context::createDefaultContext();
    $propertyRepo = $container->get('property_group_option.repository');
    $criteria = new Criteria();
    $criteria->addFilter(new EqualsFilter('groupId', $colorPropertyGrpId));
    $existingColors = $propertyRepo->search($criteria, $context);
    
    $colorMap = [];
    foreach ($existingColors as $color) {
        $colorMap[$color->getName()] = $color->getId();
    }
    
    foreach ($colorSwatchData as $colorName => $swatchImage) {
        // ✅ Use existing ID if found, otherwise generate new
        $propertyId = $colorMap[$colorName] ?? Uuid::randomHex();
        
        $propertyRow = [
            $propertyId,            // id
            $colorName,             // name
            $colorPropertyGrpId,    // groupId
            $swatchImage            // media (swatch image URL)
        ];
        
        fputcsv($propFile, $propertyRow, ";");
    }
    
    fclose($propFile);
    return $outputPath;
}

// ✅ Updated main function to generate BOTH CSVs

// ✅ Replace createProductCsv() function (line 989-1091):

function createProductCsv($csvData, $container){
    $date = date("dmy");
    $vendor = $_POST['vendor'] ?? 'media';
    $csvName = $vendor . "_product_media_{$date}.csv";
    $context = Context::createDefaultContext();
    $productRepo = $container->get('product.repository');
    $productMediaRepo = $container->get('product_media.repository');
    $successCount = 0;
    // ===================== CREATE JOB ENTRY IN vendor_import_jobs =====================
    $categoryMapping = [];
    $jobId = 'JOB_' . strtoupper($vendor) . '_' . date('YmdHis') . '_' . substr(md5(uniqid()), 0, 6);
    $GLOBALS['MEDIA_JOB_ID'] = $jobId;
    $originalFileName = $csvName;
    $totalRows = count($csvData);
    $outputDir = __DIR__ . '/csv-imports/images/';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    $outputPath = $outputDir . $csvName;
    try {
        require_once __DIR__ . '/db_config.php';
        $pdo = Database::getConnection();
        $insertJobSql = "INSERT INTO vendor_import_jobs (job_id, vendor_name, import_type, file_name, file_path, batch_size, total_rows, processed_rows, error_rows, status, category_mapping, created_at, started_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($insertJobSql);
        $stmt->execute([
            $jobId,
            $vendor,
            'images',
            basename($originalFileName),
            $outputPath,
            50, // default batch_size
            $totalRows,
            0, // processed_rows
            0, // error_rows
            'pending', // status
            json_encode($categoryMapping)
        ]);
        error_log("Job created in vendor_import_jobs with job_id: $jobId");
    } catch (Exception $e) {
        error_log("Failed to create job entry: " . $e->getMessage());
    }
    // ===================== END JOB ENTRY BLOCK =====================
    // ✅ CSV Header - NO position column
    $productCsvHeader = [
        'productNumber',
        'name',
        'stock',
        'taxId',
        'price.DEFAULT.gross',
        'price.DEFAULT.net',
        'price.DEFAULT.currencyId',
        'cover',
        'media'
    ];
    $isCliMode = (php_sapi_name() === 'cli' || defined('STDIN') || defined('IMPORT_PROCESSOR_MODE'));
    if ($isCliMode) {
        $proFile = fopen($outputPath, 'w');
        if (!defined('IMPORT_PROCESSOR_MODE')) {
            error_log("Generated product media CSV: " . $outputPath);
        }
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $csvName . '"');
        $proFile = fopen('php://output', 'w');
    }
    fputcsv($proFile, $productCsvHeader, ";");
    foreach ($csvData as $csvDatavalue) {
        $productNumber = $csvDatavalue['product_number'] ?? ($csvDatavalue[2] ?? '');
        $productName = $csvDatavalue['name'] ?? ($csvDatavalue[5] ?? '');
        $stock = $csvDatavalue['stock'] ?? ($csvDatavalue[4] ?? 10);
        $taxId = $csvDatavalue['tax_id'] ?? ($csvDatavalue[11] ?? '0197e3c80947729bbb9c9ca9f3238a05');
        
        $coverImage = $csvDatavalue['cover_image'] ?? ($csvDatavalue[15] ?? '');
        $galleryImages = $csvDatavalue['gallery_images'] ?? ($csvDatavalue[41] ?? '');
        
        // ✅ Build media URLs - EXCLUDE cover image from media column
        $allMediaUrls = [];
        
        // ✅ Gallery images ONLY (skip cover image completely)
        if (!empty($galleryImages)) {
            $galleryArray = explode('|', $galleryImages);
            foreach ($galleryArray as $img) {
                $img = trim($img);
                // ✅ Skip if empty OR if it's the same as cover image
                if (!empty($img) && $img !== $coverImage) {
                    $allMediaUrls[] = $img;
                }
            }
        }
        
        // ✅ Price calculation
        $grossPrice = $csvDatavalue['price'] ?? ($csvDatavalue[7] ?? 0);
        $grossPrice = (float)$grossPrice;
        $taxRate = $csvDatavalue['tax_rate'] ?? ($csvDatavalue[12] ?? 19);
        $taxRate = (float)$taxRate;
        
        $netPrice = 0;
        if ($grossPrice > 0 && $taxRate >= 0) {
            $netPrice = $grossPrice / (1 + ($taxRate / 100));
            $netPrice = round($netPrice, 2);
        }
        
        // ✅ Write CSV row - media has ONLY gallery items (NO cover)
        $productRow = [
            $productNumber,
            $productName,
            $stock,
            $taxId,
            $grossPrice,
            $netPrice,
            '2fbb5fe2e29a4d70aa5854ce7ce3e20b', // EUR
            $coverImage,                          // ✅ Cover separate
            implode('|', $allMediaUrls)           // ✅ Media = gallery only (NO cover)
        ];
        
        fputcsv($proFile, $productRow, ";");
        $successCount++;
    }
    
    fclose($proFile);
    
    // ===================== AUTO-ADD VISIBILITY TO SALES CHANNEL =====================
    // All newly created products should be visible on the sales channel automatically
    try {
        require_once __DIR__ . '/db_config.php';
        $pdo = Database::getConnection();
        $salesChannelId = '0197e3dc1566708987331d818f8e1867';
        
        // Extract product numbers and add visibility for each
        foreach ($csvData as $row) {
            $productNumber = $row[2] ?? null; // product_number at index 2
            if (empty($productNumber)) continue;
            
            // Get product ID from database
            $getProductSql = "SELECT id, version_id FROM product WHERE product_number = ? LIMIT 1";
            $getStmt = $pdo->prepare($getProductSql);
            $getStmt->execute([$productNumber]);
            $product = $getStmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($product) {
                $productId = $product['id']; // Already hex string
                $versionId = $product['version_id']; // Already hex string
                
                // Check if visibility already exists
                $checkSql = "SELECT id FROM product_visibility WHERE product_id = UNHEX(?) AND sales_channel_id = UNHEX(?)";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$productId, $salesChannelId]);
                
                if ($checkStmt->rowCount() === 0) {
                    // Add visibility to sales channel
                    $insertVisSql = "INSERT INTO product_visibility (id, product_id, product_version_id, sales_channel_id, visibility, created_at) 
                                    VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), 30, NOW())";
                    $visStmt = $pdo->prepare($insertVisSql);
                    $visStmt->execute([
                        str_replace('-', '', Uuid::randomHex()),
                        $productId,
                        $versionId,
                        $salesChannelId
                    ]);
                }
            }
        }
    } catch (\Exception $e) {
        error_log("Failed to add product visibility: " . $e->getMessage());
    }
    
    // Always return the file path for automatic import
    return $outputPath;
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

/**
 * Process swatch images - calls dedicated swatch image handler
 */
function processSwatchImages(array $swatchData, string $vendor, $container, $connection) {
    try {
        if (empty($swatchData)) {
            return null;
        }

        $logDir = __DIR__ . '/import-logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/swatch_import.log';
        $firstColor = array_key_first($swatchData);
        file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . "] START vendor={$vendor} colors=" . count($swatchData) . " first_color=" . ($firstColor ?? '-') . "\n",
            FILE_APPEND
        );

        // Use SwatchImageProcessor (existing, tested)
        if (!defined('SWATCH_QUIET')) {
            define('SWATCH_QUIET', true);
        }
        require_once __DIR__ . '/swatch_image_processor.php';
        $context = Context::createDefaultContext();
        $processor = new SwatchImageProcessor($container, $connection, $context);
        $result = $processor->processSatches($swatchData, $vendor);
        $updated = $result['updated'] ?? 0;
        $errors = !empty($result['errors']) ? count($result['errors']) : 0;
        file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . "] END vendor={$vendor} updated={$updated} errors={$errors}\n",
            FILE_APPEND
        );
        return $result;
    } catch (\Exception $e) {
        error_log("[PRODUCT MEDIA] Swatch processing error: " . $e->getMessage());
        return null;
    }
}

function normalizeSwatchColorKey(string $value): string {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    if ($value === '') {
        return '';
    }

    $value = strtr($value, [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    ]);

    return (string)preg_replace('/[^a-z0-9]+/', '', $value);
}

function resolveColorOptionIdsForSwatch($connection, string $colorGroupId, string $colorName): array {
    $exactRows = $connection->fetchAllAssociative(
        "SELECT DISTINCT LOWER(HEX(pgo.id)) AS option_id
         FROM property_group_option pgo
         JOIN property_group_option_translation pgot ON pgot.property_group_option_id = pgo.id
         WHERE LOWER(HEX(pgo.property_group_id)) = ?
           AND LOWER(TRIM(pgot.name)) = LOWER(TRIM(?))",
        [strtolower($colorGroupId), $colorName]
    );
    if (!empty($exactRows)) {
        return array_values(array_filter(array_unique(array_map(
            static fn(array $row) => strtolower((string)($row['option_id'] ?? '')),
            $exactRows
        ))));
    }

    static $cache = [];
    $cacheKey = strtolower($colorGroupId);
    if (!isset($cache[$cacheKey])) {
        $rows = $connection->fetchAllAssociative(
            "SELECT DISTINCT LOWER(HEX(pgo.id)) AS option_id, TRIM(pgot.name) AS option_name
             FROM property_group_option pgo
             JOIN property_group_option_translation pgot ON pgot.property_group_option_id = pgo.id
             WHERE LOWER(HEX(pgo.property_group_id)) = ?",
            [$cacheKey]
        );

        $normalized = [];
        foreach ($rows as $row) {
            $name = (string)($row['option_name'] ?? '');
            $id = strtolower((string)($row['option_id'] ?? ''));
            if ($name === '' || $id === '') {
                continue;
            }
            $key = normalizeSwatchColorKey($name);
            if ($key === '') {
                continue;
            }
            if (!isset($normalized[$key])) {
                $normalized[$key] = [];
            }
            if (!in_array($id, $normalized[$key], true)) {
                $normalized[$key][] = $id;
            }
        }
        $cache[$cacheKey] = $normalized;
    }

    $normalizedColor = normalizeSwatchColorKey($colorName);
    if ($normalizedColor === '') {
        return [];
    }

    $optionMap = $cache[$cacheKey];
    if (!empty($optionMap[$normalizedColor])) {
        return $optionMap[$normalizedColor];
    }

    $prefixed = 's' . $normalizedColor;
    if (!empty($optionMap[$prefixed])) {
        return $optionMap[$prefixed];
    }

    $bestKey = null;
    $bestDistance = 99;
    foreach ($optionMap as $key => $ids) {
        if ($key === '') {
            continue;
        }
        $distance = levenshtein($normalizedColor, $key);
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $bestKey = $key;
        }
    }

    if ($bestKey !== null && $bestDistance <= 1 && !empty($optionMap[$bestKey])) {
        return $optionMap[$bestKey];
    }

    return [];
}

function processRossProductSwatches(array $rows, $container, $connection) {
    $result = [
        'success' => true,
        'processed' => 0,
        'assigned' => 0,
        'errors' => 0,
        'messages' => []
    ];

    if (empty($rows)) {
        return $result;
    }

    $context = Context::createDefaultContext();
    $mediaRepository = $container->get('media.repository');
    $fileSaver = $container->get(\Shopware\Core\Content\Media\File\FileSaver::class);
    $projectDir = rtrim((string)$container->getParameter('kernel.project_dir'), '/');
    $colorGroupId = '0198135f7a2f7600a44ed9ab388d112a';
    $liveVersion = \Shopware\Core\Defaults::LIVE_VERSION;

    $deduped = [];
    foreach ($rows as $row) {
        $productNumber = trim((string)($row['product_number'] ?? ''));
        $colorName = trim((string)($row['color_name'] ?? ''));
        $imageUrl = trim((string)($row['image_url'] ?? ''));
        $sourcePath = trim((string)($row['source_path'] ?? ''));
        if ($productNumber === '' || $colorName === '' || $imageUrl === '') {
            continue;
        }
        $dedupeKey = strtolower($productNumber . '|' . $colorName . '|' . $imageUrl . '|' . $sourcePath);
        $deduped[$dedupeKey] = [
            'product_number' => $productNumber,
            'color_name' => $colorName,
            'image_url' => $imageUrl,
            'source_path' => $sourcePath
        ];
    }

    foreach (array_values($deduped) as $row) {
        $result['processed']++;
        $productNumber = $row['product_number'];
        $colorName = $row['color_name'];
        $imageUrl = $row['image_url'];
        $rowSourcePath = trim((string)($row['source_path'] ?? ''));

        try {
            $productData = $connection->fetchAssociative(
                "SELECT LOWER(HEX(id)) AS product_id, LOWER(HEX(parent_id)) AS parent_id
                 FROM product
                 WHERE product_number = ?
                 LIMIT 1",
                [$productNumber]
            );
            if (!$productData) {
                $result['errors']++;
                $result['messages'][] = "Product not found: {$productNumber}";
                continue;
            }

            $parentId = !empty($productData['parent_id']) ? $productData['parent_id'] : $productData['product_id'];

                        $optionIds = resolveColorOptionIdsForSwatch($connection, $colorGroupId, $colorName);

                        $variantOptionIds = $connection->fetchFirstColumn(
                            "SELECT DISTINCT LOWER(HEX(po.property_group_option_id)) AS option_id
                             FROM product p
                             JOIN product_option po ON po.product_id = p.id
                             JOIN property_group_option pgo ON pgo.id = po.property_group_option_id
                             WHERE p.product_number = ?
                               AND LOWER(HEX(pgo.property_group_id)) = ?",
                            [$productNumber, strtolower($colorGroupId)]
                        );
                        $variantOptionIds = array_values(array_filter(array_map(
                            static fn($id) => strtolower((string)$id),
                            is_array($variantOptionIds) ? $variantOptionIds : []
                        )));

                        $optionIds = array_values(array_unique(array_merge($variantOptionIds, $optionIds)));

                        if (empty($optionIds)) {
                $result['errors']++;
                $result['messages'][] = "Color option not found: {$colorName} ({$productNumber})";
                continue;
            }

            $sourcePath = '';
            if ($rowSourcePath !== '' && file_exists($rowSourcePath)) {
                $sourcePath = $rowSourcePath;
            } else {
                $sourcePath = resolveRossSwatchSourcePath($imageUrl, $projectDir);
            }
            if (!$sourcePath || !file_exists($sourcePath)) {
                $result['errors']++;
                $result['messages'][] = "Swatch image not found: {$imageUrl}";
                continue;
            }

            $fileExtension = strtolower((string)pathinfo($sourcePath, PATHINFO_EXTENSION));
            if ($fileExtension === '') {
                $fileExtension = 'jpg';
            }
            $mimeType = @mime_content_type($sourcePath);
            if (!$mimeType) {
                $mimeType = 'image/jpeg';
            }

            $mediaId = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
            $folderId = $connection->fetchOne(
                "SELECT LOWER(HEX(mf.id))
                 FROM media_folder mf
                 JOIN media_default_folder mdf ON mdf.id = mf.default_folder_id
                 WHERE mdf.entity = 'product'
                 LIMIT 1"
            );

            $mediaRepository->create([[
                'id' => $mediaId,
                'mediaFolderId' => $folderId ?: null,
                'private' => false,
                'name' => 'swatch-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $colorName)) . '-' . strtolower($productNumber)
            ]], $context);

            $mediaFile = new \Shopware\Core\Content\Media\File\MediaFile(
                $sourcePath,
                $mimeType,
                $fileExtension,
                filesize($sourcePath)
            );
            $destination = 'swatch-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $colorName))
                . '-' . strtolower($productNumber)
                . '-' . substr(md5($imageUrl), 0, 8)
                . '-' . substr(md5(uniqid((string)microtime(true), true)), 0, 6);
            $fileSaver->persistFileToMedia($mediaFile, $destination, $mediaId, $context);

            foreach ($optionIds as $candidateOptionId) {
                $connection->executeStatement(
                    "UPDATE property_group_option
                     SET media_id = UNHEX(?), updated_at = NOW(3)
                     WHERE id = UNHEX(?)",
                    [$mediaId, $candidateOptionId]
                );
            }

                        $existingSettings = [];
                        foreach ($optionIds as $candidateOptionId) {
                            $rowSetting = $connection->fetchAssociative(
                                "SELECT LOWER(HEX(id)) AS id
                                 FROM product_configurator_setting
                                 WHERE product_id = UNHEX(?)
                                   AND property_group_option_id = UNHEX(?)
                                 LIMIT 1",
                                [$parentId, $candidateOptionId]
                            );
                            if (!empty($rowSetting['id'])) {
                                $existingSettings[$candidateOptionId] = strtolower($rowSetting['id']);
                            }
                        }

                        $position = (int)$connection->fetchOne(
                            "SELECT COALESCE(MAX(position), -1) + 1
                             FROM product_configurator_setting
                             WHERE product_id = UNHEX(?)",
                            [$parentId]
                        );

                        foreach ($optionIds as $candidateOptionId) {
                            if (!empty($existingSettings[$candidateOptionId])) {
                                $connection->executeStatement(
                                    "UPDATE product_configurator_setting
                                     SET media_id = UNHEX(?), updated_at = NOW(3)
                                     WHERE id = UNHEX(?)",
                                    [$mediaId, $existingSettings[$candidateOptionId]]
                                );
                                continue;
                            }

                            $newSettingId = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
                            $connection->executeStatement(
                                "INSERT INTO product_configurator_setting
                                (id, version_id, product_id, product_version_id, property_group_option_id, position, media_id, created_at)
                                VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), ?, UNHEX(?), NOW(3))",
                                [$newSettingId, $liveVersion, $parentId, $liveVersion, $candidateOptionId, $position, $mediaId]
                            );
                            $position++;
                        }

            $result['assigned']++;
        } catch (\Throwable $e) {
            $result['errors']++;
            $result['messages'][] = "{$productNumber} / {$colorName}: " . $e->getMessage();
        }
    }

    if ($result['errors'] > 0) {
        $result['success'] = false;
    }

    return $result;
}

function resolveRossSwatchSourcePath(string $imageUrl, string $projectDir): ?string {
    $imageUrl = trim($imageUrl);
    if ($imageUrl === '') {
        return null;
    }

    if (file_exists($imageUrl)) {
        return $imageUrl;
    }

    if (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0) {
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!empty($path)) {
            $local = $projectDir . '/public' . $path;
            if (file_exists($local)) {
                return $local;
            }
        }
        return null;
    }

    if (strpos($imageUrl, '/') === 0) {
        $local = $projectDir . '/public' . $imageUrl;
        if (file_exists($local)) {
            return $local;
        }
    }

    return null;
}
