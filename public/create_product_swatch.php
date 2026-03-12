<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    $file = $_FILES['csv_file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . ($file['error'] ?? 'no file');
    } else {
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            $message = "Invalid file type. Please upload a CSV / XLS file.";
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet       = $spreadsheet->getActiveSheet();

                $rowCount        = 1;
                $header          = [];
                $productCsvData  = [];

                foreach ($sheet->getRowIterator() as $row) {
                    // skip unwanted rows
                    if (in_array($rowCount, [1,3,4,5])) {
                        $rowCount++;
                        continue;
                    }

                    $rowData      = [];
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

                    if (empty($data['Article Number'])) {
                        $rowCount++;
                        continue;
                    }
                    // Get productId from Shopware by productNumber (Article Number)
                    $context        =   Context::createDefaultContext();
                    $criteria = new Criteria();
                    $criteria->addFilter(new EqualsFilter('productNumber', $data['Article Number']));
                    $productRepo = $container->get('product.repository');
                    $product     = $productRepo->search($criteria, $context)->first();
                    if ($product) {
                        $productCsvData[] = [
                            $product->getId(),
                            "https://live.medialink.com/static/media/meeting.027690ec.jpg"
                        ];
                    }
                    //$productCsvData[] = $proArr;

                    $rowCount++;
                }

                createProductCsv($productCsvData, $container);
                exit;

            } catch (\Exception $e) {
                echo 'Error loading file: ', $e->getMessage();
            }
        }
    }
}else if(isset($_POST['vendor']) && $_POST['vendor'] === 'harko'){
    $file = $_FILES['csv_file'] ?? null;
    $imageBaseDir = $kernel->getProjectDir().'/public/my-imports/Hakro/';
    $colorSwatchMapping = [];
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
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();

                $rowCount = 1;
                $header = [];
                $productCsvData = [];
                 $colorSwatchMapping = [];
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
                    $articleShortCode = $data['1 Article Number Short'];
                    $photoShort = $data['2 Photo Number Short'] ?? '';
                    $productNumber = $data['Article Number'];
                    
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
                   
                    $colorName = $data['4 Color'] ?? '';
                    $colorCode = $colorName; 
                    // if (!empty($swatchesImageRel) && !empty($colorName)) {
                    //     $colorSwatchMapping[$colorName] = $swatchesImageRel;
                    // }
                    
                    // Swatches: m_item_p_{photoShort}_01.jpg
                    $swatchesImage = '';
                    if ($colorCode !== '') {
                        $swatchPath = $imageBaseDir . $articleShortCode . '/m_item_p_' . $photoShort .'_01.jpg';
                        if (file_exists($swatchPath)) {
                            $swatchesImage = $swatchPath;
                        }
                    } else {
                        $swatchPath = $imageBaseDir . $articleShortCode . '/m_item_p_' . $photoShort . '_01.jpg';
                        if (file_exists($swatchPath)) {
                            $swatchesImage = $swatchPath;
                        }
                    }

                    // Cover: m_model_p_{photoShort}_01.jpg
                    $coverImage = '';
                    $coverPath = $imageBaseDir . $articleShortCode . '/m_model_p_' . $photoShort . '_01.jpg';
                    if (file_exists($coverPath)) {
                        $coverImage = $coverPath;
                    }

                    // Gallery: m_item_p_{photoShort}_01.jpg and _02/_03/_04
                    $galleryImages = [];
                    $galleryPath1 = $imageBaseDir . $articleShortCode . '/m_item_p_' . $photoShort . '_01.jpg';
                    if (file_exists($galleryPath1)) {
                        $galleryImages[] = $galleryPath1;
                    }
                    
                    for ($imgCount = 2; $imgCount < 5; $imgCount++) {
                        $itemPath = $imageBaseDir . $articleShortCode . '/m_item_p_' . $photoShort . '_0' . $imgCount . '.jpg';
                        if (file_exists($itemPath)) {
                            $galleryImages[] = $itemPath;
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
                    
                    $swatchesImageRel = $makeRelative($swatchesImage);
                    $coverImageRel = $makeRelative($coverImage);
                    $galleryImagesRel = '';
                    if (!empty($galleryImages)) {
                        $galleryImagesRel = implode('|', array_map($makeRelative, $galleryImages));
                    }
                    if (!empty($swatchesImageRel) && !empty($colorName)) {
                        
                        $colorSwatchMapping[$colorName] = $swatchesImageRel;
                    }
                    //print_r($colorSwatchMapping);die;
                    // ✅ Create complete product data array (matching createProductCsv expectations)
                    $productCsvData[] = [
                        'id' => Uuid::randomHex(),
                        'name' => $productName,
                        'stock' => $stock,
                        'tax_id' => $taxId,
                        'price' => (float)$price,
                        'product_number' => $productNumber,
                        'color_name' => $colorName,  // ✅ Add color name
                        'swatches_image' => $swatchesImageRel,
                        'cover_image' => $coverImageRel,
                        'gallery_images' => $galleryImagesRel
                    ];

                    $rowCount++;
                }
                // ✅ SAVE color swatch mapping to session
                $_SESSION['harko_color_swatches'] = $colorSwatchMapping;
                $_SESSION['harko_product_data'] = $productCsvData;
                //print_r($_SESSION['harko_color_swatches']);die;
                // ✅ Check which CSV to generate
                //if (isset($_GET['csv_type']) && $_GET['csv_type'] === 'swatches') {
                    // Generate swatch CSV from session
                    $colorSwatchMapping = $_SESSION['harko_color_swatches'] ?? [];
                    
                    if (!empty($colorSwatchMapping)) {
                        createPropertySwatchCsv($colorSwatchMapping, $container);
                    } else {
                        echo "⚠️ No color swatch data found in session";
                    }
                    exit;
               // }
                
                // ✅ Default: Generate product media CSV
                // if (!empty($productCsvData)) {
                //     createProductCsv($productCsvData, $container);
                    
                //     // ✅ Don't exit - show button to download swatch CSV
                //     echo "<html><body>";
                //     echo "<h3>✅ Product Media CSV Downloaded Successfully!</h3>";
                //     echo "<p>📊 Total products: " . count($productCsvData) . "</p>";
                //     echo "<p>🎨 Total unique colors: " . count($colorSwatchMapping) . "</p>";
                    
                //     echo "<hr>";
                //     echo "<h3>🎨 Step 2: Generate Color Swatch CSV</h3>";
                //     echo "<p>Click the button below to download the swatch CSV:</p>";
                    
                //     $currentUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
                //     $swatchUrl = $currentUrl . '?csv_type=swatches';
                    
                //     echo "<a href='{$swatchUrl}' style='background:#3498db;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;display:inline-block;'>⬇️ Download Swatch CSV</a>";
                    
                //     echo "<hr>";
                //     echo "<h4>📋 Collected Colors:</h4>";
                //     echo "<ul>";
                //     foreach ($colorSwatchMapping as $colorName => $swatchUrl) {
                //         echo "<li><strong>{$colorName}</strong> → <small>" . basename($swatchUrl) . "</small></li>";
                //     }
                //     echo "</ul>";
                    
                //     echo "</body></html>";
                //     exit;
                // } else {
                //     echo "⚠️ No product data found";
                // }
                
                exit;

            } catch (\Exception $e) {
                echo '❌ Error loading file: ', $e->getMessage();
            }
        }
    }
} else if (isset($_POST['vendor']) && $_POST['vendor'] === 'newwave') {
    $file = $_FILES['csv_file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        die("❌ Upload failed with error code: " . ($file['error'] ?? 'no file'));
    }

    $fileTmpPath = $file['tmp_name'];
    $targetDir = __DIR__ . '/uploads/';
    $targetFile = $targetDir . basename($file['name']);

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    move_uploaded_file($fileTmpPath, $targetFile);

    // Decode JSON
    $jsonData = json_decode(file_get_contents($targetFile), true);
    if (empty($jsonData['result']) || !is_array($jsonData['result'])) {
        die('❌ Invalid JSON format. Missing "result" array.');
    }

    //echo "✅ JSON file parsed successfully (" . count($jsonData['result']) . " items)<br>";

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
        40 => '',
        41 => '',
        42 => '' // swatches_image
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
                    $usedVariantCover = []; // keep track of which color has already had a cover image
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
                        $vRow[16] = $colorsFound[$skuColor]['name'].'|'.$imgList[0];         // cover_media_title (REQUIRED)
                        $vRow[17] = $name;         // cover_media_alt   (REQUIRED)

                        // Gallery images
                        $vRow[41] = implode('|', array_unique($imgList));
                        // Swatches image: first image for color
                        $vRow[42] = $imgList[0];
                    } else {
                        $vRow[15] = '';
                        $vRow[16] = '';
                        $vRow[17] = '';
                        $vRow[41] = '';
                        $vRow[42] = '';
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

        if (!empty($productCsvData)) {
            // Hand-off to your CSV creator
            createProductCsv($productCsvData, $container);

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





// ✅ Replace createPropertySwatchCsv() function with direct API upload:

function createPropertySwatchCsv($colorSwatchData, $container) {
    echo "<h2>🎨 Direct Swatch Image Upload (No CSV)</h2>";
    echo "<hr>";
    
    $context = Context::createDefaultContext();
    $propertyRepo = $container->get('property_group_option.repository');
    $mediaRepo = $container->get('media.repository');
    
    // ✅ Get Color property group ID
    $colorPropertyGrpId = getPropertyGroupId('Color', $container);
    
    if (!$colorPropertyGrpId) {
        $colorPropertyGrpId = '0198135f7a2f7600a44ed9ab388d112a';
        echo "⚠️ Using fallback Color group ID<br>";
    } else {
        echo "✅ Found Color property group: {$colorPropertyGrpId}<br>";
    }
    
    // ✅ Get existing color options
    $criteria = new Criteria();
    $criteria->addFilter(new EqualsFilter('groupId', $colorPropertyGrpId));
    $existingColors = $propertyRepo->search($criteria, $context);
    
    $colorMap = [];
    foreach ($existingColors as $color) {
        $colorMap[$color->getName()] = $color->getId();
    }
    
    echo "📊 Found " . count($colorMap) . " existing color options<br>";
    echo "🎨 Processing " . count($colorSwatchData) . " swatch images<br><br>";
    
    $successCount = 0;
    $errorCount = 0;
    $processedColors = [];
    
    foreach ($colorSwatchData as $colorName => $swatchImageUrl) {
        // Skip if already processed or empty
        if (empty($colorName) || in_array($colorName, $processedColors)) {
            continue;
        }
        
        echo "<strong>🎨 Processing: {$colorName}</strong><br>";
        
        try {
            // ✅ Step 1: Get or create property option
            if (isset($colorMap[$colorName])) {
                $propertyOptionId = $colorMap[$colorName];
                echo "  └─ Found existing option: {$propertyOptionId}<br>";
            } else {
                // Create new property option
                $propertyOptionId = Uuid::randomHex();
                $propertyRepo->create([
                    [
                        'id' => $propertyOptionId,
                        'name' => $colorName,
                        'groupId' => $colorPropertyGrpId
                    ]
                ], $context);
                echo "  └─ Created new option: {$propertyOptionId}<br>";
                $colorMap[$colorName] = $propertyOptionId;
            }
            
            // ✅ Step 2: Download image from URL
            echo "  └─ Downloading image: " . basename($swatchImageUrl) . "... ";
            
            $imageContent = @file_get_contents($swatchImageUrl);
            
            if ($imageContent === false) {
                echo "<span style='color:red;'>❌ Download failed</span><br>";
                $errorCount++;
                continue;
            }
            
            echo "<span style='color:green;'>✅ Downloaded (" . strlen($imageContent) . " bytes)</span><br>";
            
            // ✅ Step 3: Create media entry
            $mediaId = Uuid::randomHex();
            $filename = pathinfo(parse_url($swatchImageUrl, PHP_URL_PATH), PATHINFO_BASENAME);
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            
            // Save to Shopware media folder
            $mediaFolderPath = $container->getParameter('kernel.project_dir') . '/public/media/';
            if (!is_dir($mediaFolderPath)) {
                mkdir($mediaFolderPath, 0755, true);
            }
            
            $timestamp = time();
            $uniqueFilename = 'swatch_' . $colorName . '_' . $timestamp . '.' . $fileExtension;
            $savePath = $mediaFolderPath . $uniqueFilename;
            
            if (file_put_contents($savePath, $imageContent) === false) {
                echo "  └─ <span style='color:red;'>❌ Save failed</span><br>";
                $errorCount++;
                continue;
            }
            
            echo "  └─ <span style='color:green;'>✅ Saved: {$uniqueFilename}</span><br>";
            
            // ✅ Step 4: Create media entry in database
            $mediaRepo->create([
                [
                    'id' => $mediaId,
                    'private' => false
                ]
            ], $context);
            
            echo "  └─ <span style='color:green;'>✅ Created media entry: {$mediaId}</span><br>";
            
            // ✅ Step 5: Link media to property option
            $propertyRepo->update([
                [
                    'id' => $propertyOptionId,
                    'mediaId' => $mediaId
                ]
            ], $context);
            
            echo "  └─ <span style='color:green;'>✅ Linked to property option</span><br>";
            echo "<br>";
            die('gggggg');
            $successCount++;
            $processedColors[] = $colorName;
            
        } catch (\Exception $e) {
            echo "  └─ <span style='color:red;'>❌ Error: " . $e->getMessage() . "</span><br><br>";
            $errorCount++;
        }
    }
    
    // ✅ Clear cache
    try {
        $cacheDir = $container->getParameter('kernel.cache_dir');
        shell_exec("rm -rf {$cacheDir}/*");
        echo "<p style='color:green;'>🧹 <strong>Cache cleared!</strong></p>";
    } catch (\Exception $e) {
        echo "<p style='color:orange;'>⚠️ Cache clear failed</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎉 Swatch Upload Completed!</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr style='background:#27ae60;color:white;'><th>Metric</th><th>Value</th></tr>";
    echo "<tr><td><strong>✅ Success</strong></td><td>{$successCount} colors</td></tr>";
    echo "<tr><td><strong>❌ Errors</strong></td><td>{$errorCount} colors</td></tr>";
    echo "<tr><td><strong>📋 Processed</strong></td><td>" . implode(', ', $processedColors) . "</td></tr>";
    echo "</table>";
    
    exit;
}

// ✅ Add this helper function (if not already present):

/**
 * Get Property Group ID by name
 */
function getPropertyGroupId(string $groupName, $container) {
    $context = Context::createDefaultContext();
    $propertyGroupRepo = $container->get('property_group.repository');
    
    $criteria = new Criteria();
    $criteria->addFilter(new EqualsFilter('name', $groupName));
    
    $result = $propertyGroupRepo->search($criteria, $context)->first();
    
    if ($result) {
        return $result->getId();
    }
    
    return null;
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
