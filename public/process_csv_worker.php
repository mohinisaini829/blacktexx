<?php
/**
 * CSV Worker - Processes CSV rows and creates products with duplicate check
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('memory_limit', '4G');
set_time_limit(0);

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
ob_implicit_flush(true);

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Defaults;

// Initialize Shopware kernel
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env.local');

$classLoader = require __DIR__ . '/../vendor/autoload.php';
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$debug = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

$pluginLoader = null;
if (EnvironmentHelper::getVariable('COMPOSER_PLUGIN_LOADER', false)) {
    $pluginLoader = new ComposerPluginLoader($classLoader, null);
}

$kernel = KernelFactory::create(
    environment: $appEnv,
    debug: $debug,
    classLoader: $classLoader,
    pluginLoader: $pluginLoader
);

$kernel->boot();


$context = Context::createDefaultContext();
$context = new Context(
    $context->getSource(),
    [],
    Defaults::CURRENCY,
    [Defaults::LANGUAGE_SYSTEM],
    Defaults::LIVE_VERSION
);
$container = $kernel->getContainer();
$connection = $container->get(Connection::class);
$context = Context::createDefaultContext();

// === HELPER FUNCTIONS ===
function sendProgress($processed, $created, $skipped, $errors, $total = 0) {
    echo json_encode([
        'type' => 'progress',
        'processed' => $processed,
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors,
        'total' => $total
    ]) . "\n";
    flush();
}

function sendLog($message, $level = 'info') {
    echo json_encode([
        'type' => 'log',
        'message' => $message,
        'level' => $level
    ]) . "\n";
    flush();
}

// === END HELPER FUNCTIONS ===

// Support job_id from CLI (argv) as well as $_GET
$jobId = $_GET['job_id'] ?? null;
if (!$jobId && isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, 'job_id=') === 0) {
            $jobId = substr($arg, 7);
            break;
        }
    }
}
if (!$jobId) {
    sendLog('No job_id provided', 'error');
    exit;
}

// Get job details
$pdo = $connection->getNativeConnection();
$sql = "SELECT * FROM vendor_import_jobs WHERE job_id = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    sendLog('Job not found', 'error');
    exit;
}

$csvFile = $job['file_path'];
if (!file_exists($csvFile)) {
    sendLog('CSV file not found: ' . $csvFile, 'error');
    exit;
}

$rawImportType = (string)($job['import_type'] ?? 'product');
$importType = strtolower(trim($rawImportType));
$batchSize = (int)($job['batch_size'] ?? 50);
$totalRows = (int)$job['total_rows'];

sendLog("Import Type (raw): $rawImportType", 'info');
sendLog("Import Type (normalized): $importType", 'info');
sendLog("CSV File: $csvFile", 'info');
sendLog("Total Rows: $totalRows", 'info');

$processed = 0;
$created = 0;
$skipped = 0;
$errors = 0;

// Get repositories
$productRepo = $container->get('product.repository');
$mediaRepo = $container->get('media.repository');
$fileSaver = $container->get(FileSaver::class);

sendLog('Starting CSV processing...', 'info');

// Handle tier price import differently
if ($importType === 'tierprice') {
    processTierPriceImport($csvFile, $connection, $productRepo, $context, $jobId, $pdo);
    exit;
}

// Handle images import - THIS SHOULD MATCH
if (in_array($importType, ['images', 'image', 'media', 'product_media'], true)) {
    processImageImport($csvFile, $connection, $productRepo, $context, $jobId, $pdo);
    exit;
}

// Open CSV file for product import
$handle = fopen($csvFile, 'r');
if (!$handle) {
    sendLog('Cannot open CSV file', 'error');
    exit;
}

// Read header
$header = fgetcsv($handle, 0, ';');
if (!$header) {
    sendLog('Invalid CSV - no header found', 'error');
    exit;
}

sendLog('CSV Header loaded with ' . count($header) . ' columns', 'info');

// Process rows in batches
$batch = [];
$rowNumber = 0;

// Track imported product numbers for media cleanup
$importedProductNumbers = [];

// First pass: collapse duplicate product_number rows (last occurrence wins)
$rowsByProductNumber = [];
$duplicateRowsInCsv = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $rowNumber++;

    try {
        $data = array_combine($header, $row);
        if ($data === false) {
            $errors++;
            sendLog("Row $rowNumber: Invalid CSV row format", 'error');
            sendProgress($processed, $created, $skipped, $errors);
            continue;
        }

        $productNumber = trim((string)($data['product_number'] ?? ''));
        if ($productNumber === '') {
            $skipped++;
            sendLog("Row $rowNumber: Skipped - No product number", 'skip');
            sendProgress($processed, $created, $skipped, $errors);
            continue;
        }

        if (isset($rowsByProductNumber[$productNumber])) {
            $duplicateRowsInCsv++;
        }

        $rowsByProductNumber[$productNumber] = [
            'rowNumber' => $rowNumber,
            'data' => $data,
        ];
    } catch (\Exception $e) {
        $errors++;
        sendLog("Row $rowNumber: Error while reading CSV - " . $e->getMessage(), 'error');
        sendProgress($processed, $created, $skipped, $errors);
    }
}

if ($duplicateRowsInCsv > 0) {
    sendLog("Duplicate rows detected in CSV: $duplicateRowsInCsv (last occurrence used per product_number)", 'warning');
}

// Parent-first processing to keep parent/child relation stable
$parentEntries = [];
$childEntries = [];
foreach ($rowsByProductNumber as $entry) {
    $parentIdValue = trim((string)($entry['data']['parent_id'] ?? ''));
    if ($parentIdValue === '') {
        $parentEntries[] = $entry;
    } else {
        $childEntries[] = $entry;
    }
}

$entriesToProcess = array_merge($parentEntries, $childEntries);
sendLog('Unique products to process: ' . count($entriesToProcess), 'info');

foreach ($entriesToProcess as $entry) {
    $rowNumber = (int)$entry['rowNumber'];
    $data = $entry['data'];

    try {
        $parentIdValue = trim((string)($data['parent_id'] ?? ''));
        $isParentRow = ($parentIdValue === '');

        if (!empty($data['size_spec_pdf_url']) && $isParentRow) {
            $pdfMediaId = resolveOrCreateProsheetMediaId(
                (string)$data['size_spec_pdf_url'],
                $mediaRepo,
                $fileSaver,
                $context,
                $pdo
            );
            if (!empty($pdfMediaId)) {
                $data['products_additional_data_prosheet'] = $pdfMediaId;
                sendLog("Row $rowNumber: Prosheet media resolved for {$data['product_number']} => {$pdfMediaId}", 'info');
            } else {
                sendLog("Row $rowNumber: Prosheet media NOT resolved for {$data['product_number']} (URL: {$data['size_spec_pdf_url']})", 'warning');
            }
        } elseif (!empty($data['size_spec_pdf_url']) && !$isParentRow) {
            sendLog("Row $rowNumber: Prosheet skipped for child row {$data['product_number']} (parent_id: {$parentIdValue})", 'info');
        }

        $productNumber = trim((string)$data['product_number']);
        $importedProductNumbers[] = $productNumber;

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $criteria->setLimit(1);
        $existingProduct = $productRepo->search($criteria, $context)->first();

        if ($existingProduct) {
            try {
                $productRepo->delete([
                    ['id' => $existingProduct->getId()]
                ], $context);
                sendLog("Row $rowNumber: Existing product deleted for recreate - $productNumber", 'warning');
            } catch (\Exception $deleteException) {
                $errors++;
                $processed++;
                sendLog("Row $rowNumber: Failed to delete existing product $productNumber - " . $deleteException->getMessage(), 'error');
                sendProgress($processed, $created, $skipped, $errors);
                continue;
            }
        }

        $productData = prepareProductData($data);
        $batch[] = $productData;

        if (count($batch) >= $batchSize) {
            $result = processBatch($batch, $productRepo, $context);
            $created += $result['created'];
            $errors += $result['errors'];
            $processed += count($batch);

            sendLog("Batch processed: {$result['created']} created, {$result['errors']} errors", 'success');
            sendProgress($processed, $created, $skipped, $errors);

            $batch = [];
            gc_collect_cycles();
        }
    } catch (\Exception $e) {
        $errors++;
        $processed++;
        sendLog("Row $rowNumber: Error - " . $e->getMessage(), 'error');
        sendProgress($processed, $created, $skipped, $errors);
    }
}

// Process remaining batch
if (!empty($batch)) {
    $result = processBatch($batch, $productRepo, $context);
    $created += $result['created'];
    $errors += $result['errors'];
    $processed += count($batch);
    
    sendLog("Final batch processed: {$result['created']} created, {$result['errors']} errors", 'success');
    sendProgress($processed, $created, $skipped, $errors);
}

fclose($handle);

// === MEDIA CLEANUP FOR IMPORTED PRODUCTS ===
sendLog('Starting media cleanup for imported products...', 'info');
foreach ($importedProductNumbers as $productNumber) {
    // Get product ID
    $stmt = $pdo->prepare("SELECT HEX(id) pid FROM product WHERE product_number = ?");
    $stmt->execute([$productNumber]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) continue;
    $productId = $product['pid'];

    // Get all media for this product
    $sql = "SELECT HEX(m.id) as media_id, m.path FROM media m JOIN product_media pm ON pm.media_id = m.id WHERE pm.product_id = UNHEX(?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mediaFiles as $media) {
        $filePath = __DIR__ . '/' . $media['path'];
        if (file_exists($filePath)) {
            // Delete product_media record
            $deletePmSql = "DELETE FROM product_media WHERE product_id = UNHEX(?) AND media_id = UNHEX(?)";
            $deletePmStmt = $pdo->prepare($deletePmSql);
            $deletePmStmt->execute([$productId, $media['media_id']]);
            // Optionally, also delete from media table (if not used elsewhere)
            $deleteMediaSql = "DELETE FROM media WHERE id = UNHEX(?)";
            $deleteMediaStmt = $pdo->prepare($deleteMediaSql);
            $deleteMediaStmt->execute([$media['media_id']]);
            sendLog("Deleted media for product $productNumber: " . $media['path'], 'info');
        }
    }
}
sendLog('Media cleanup complete.', 'info');

// Update job status
$updateSql = "UPDATE vendor_import_jobs 
              SET processed_rows = ?, 
                  error_rows = ?, 
                  status = 'completed', 
                  completed_at = NOW() 
              WHERE job_id = ?";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([$created, $errors, $jobId]);

sendLog("Import completed! Total: $processed, Created: $created, Skipped: $skipped, Errors: $errors", 'success');

echo json_encode(['type' => 'complete', 'processed' => $processed, 'created' => $created, 'skipped' => $skipped, 'errors' => $errors]) . "\n";
flush();

// Run the child name fix script automatically after import
sendLog('Running child name fix script...', 'info');
ob_start();

//include __DIR__ . '/fix_hemd_105_products.php';
$childNameOutput = ob_get_clean();
sendLog('Child name fix completed: ' . trim($childNameOutput), 'info');

// Run dynamic variant name fix from CSV automatically after import
sendLog('Running variant name fix from CSV...', 'info');

// First run simple fix (updates names directly from CSV)
ob_start();
$csvPath = $csvFile;
try {
    include __DIR__ . '/fix_variants_simple.php';
    $simpleFixOutput = ob_get_clean();
    if (!empty(trim($simpleFixOutput))) {
        sendLog('Simple variant fix output: ' . trim($simpleFixOutput), 'info');
    }
} catch (\Exception $e) {
    $simpleFixOutput = ob_get_clean();
    sendLog('Simple variant fix error: ' . $e->getMessage(), 'error');
}

// Then run advanced fix (adds color/size to names if optionIds available)
ob_start();
$csvPath = $csvFile;
try {
    include __DIR__ . '/fix_variants_from_csv.php';
    $variantFixOutput = ob_get_clean();
    if (!empty(trim($variantFixOutput))) {
        sendLog('Advanced variant fix output: ' . trim($variantFixOutput), 'info');
    }
} catch (\Exception $e) {
    $variantFixOutput = ob_get_clean();
    sendLog('Advanced variant fix error: ' . $e->getMessage(), 'warning');
}

// Run Newwave-specific variant name fix
sendLog('Running Newwave variant name fix...', 'info');
ob_start();
include __DIR__ . '/fix_newwave_variant_names.php';
$newwaveFixOutput = ob_get_clean();
sendLog('Newwave variant name fix completed: ' . trim($newwaveFixOutput), 'info');

// Import variant options from CSV automatically
sendLog('Importing variant options from CSV...', 'info');
try {
    importVariantOptionsFromCsv($csvFile, $pdo);
} catch (\Exception $e) {
    sendLog('Variant options import error: ' . $e->getMessage(), 'warning');
}

// Create missing configurator settings for parent products
sendLog('Creating configurator settings for parent products...', 'info');
try {
    ob_start();
    include __DIR__ . '/fix_configurator_settings.php';
    $configuratorOutput = ob_get_clean();
    // Extract just the summary line
    if (preg_match('/Total configurator settings created: (\d+)/', $configuratorOutput, $matches)) {
        sendLog('Configurator settings created: ' . $matches[1], 'success');
    }
} catch (\Exception $e) {
    sendLog('Configurator settings creation error: ' . $e->getMessage(), 'warning');
}

// Clean up wrong variant name patterns (e.g., "- 10 - 105")
sendLog('Cleaning up wrong variant name patterns...', 'info');
try {
    ob_start();
    include __DIR__ . '/cleanup_wrong_names.php';
    $cleanupOutput = ob_get_clean();
    sendLog('Name cleanup completed: ' . trim($cleanupOutput), 'info');
} catch (\Exception $e) {
    sendLog('Name cleanup error: ' . $e->getMessage(), 'warning');
}

// ==================== END OF MAIN SCRIPT ==================== 

function cleanupOldProductMedia($productId, $productNumber, $pdo) {
    try {
        // Get all media IDs for this product (for physical file deletion)
        $sql = "SELECT HEX(m.id) as media_id, m.path 
                FROM media m 
                JOIN product_media pm ON pm.media_id = m.id 
                WHERE pm.product_id = UNHEX(?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        $mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deletedFiles = 0;
        $deletedRecords = 0;
        
        // Delete physical files
        foreach ($mediaFiles as $media) {
            $filePath = __DIR__ . '/' . $media['path'];
            if (file_exists($filePath)) {
                unlink($filePath);
                $deletedFiles++;
                
                // Also try to delete empty parent directories
                $dir = dirname($filePath);
                @rmdir($dir); // timestamp folder
                @rmdir(dirname($dir)); // hash3 folder
            }
        }
        
        // Reset product cover image
        $updateSql = "UPDATE product SET product_media_id = NULL WHERE id = UNHEX(?)";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$productId]);
        
        // Delete product_media records
        $deletePmSql = "DELETE FROM product_media WHERE product_id = UNHEX(?)";
        $deletePmStmt = $pdo->prepare($deletePmSql);
        $deletePmStmt->execute([$productId]);
        $deletedRecords = $deletePmStmt->rowCount();
        
        // Delete media records (cascade will handle product_media if any left)
        if (count($mediaFiles) > 0) {
            $mediaIds = array_map(function($m) { return $m['media_id']; }, $mediaFiles);
            $placeholders = implode(',', array_fill(0, count($mediaIds), 'UNHEX(?)'));
            $deleteMediaSql = "DELETE FROM media WHERE id IN ($placeholders)";
            $deleteMediaStmt = $pdo->prepare($deleteMediaSql);
            $deleteMediaStmt->execute($mediaIds);
        }
        
        if ($deletedRecords > 0 || $deletedFiles > 0) {
            sendLog("Product $productNumber: Cleaned up $deletedRecords old records, $deletedFiles files", 'info');
        }
        
    } catch (Exception $e) {
        sendLog("Product $productNumber: Cleanup warning - " . $e->getMessage(), 'warning');
        // Don't throw - continue with import even if cleanup fails
    }
}

function processImageImport($csvFile, $connection, $productRepo, $context, $jobId, $pdo)
{
    sendLog('processImageImport CALLED', 'debug');
    global $container;
    if (!$container) {
        sendLog('Container not available. Image import aborted.', 'error');
        return;
    }
    $mediaRepo = $container->get('media.repository');
    $fileSaver = $container->get(FileSaver::class);

    sendLog('Processing Image Import (DAL mode)...', 'info');
    try {
        $stmt = $pdo->prepare("UPDATE vendor_import_jobs SET status = 'processing', started_at = NOW() WHERE job_id = ?");
        $stmt->execute([$jobId]);
    } catch (Throwable $e) {
        sendLog("Job status update failed: " . $e->getMessage(), 'warning');
    }

    // Detect file type and load appropriately
    $fileExtension = strtolower(pathinfo($csvFile, PATHINFO_EXTENSION));
    $processed = $created = $errors = 0;
    
    // For XLS/XLSX files, use PhpSpreadsheet
    if (in_array($fileExtension, ['xls', 'xlsx'])) {
        sendLog("Loading spreadsheet file: " . basename($csvFile), 'info');
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($csvFile);
            $sheet = $spreadsheet->getActiveSheet();
            
            $rowNumber = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
                
                // Skip header row
                if ($rowNumber === 1) {
                    // Log header for debugging
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getCalculatedValue();
                    }
                    sendLog("Header row has " . count($rowData) . " columns", 'info');
                    for ($i = 0; $i < min(80, count($rowData)); $i++) {
                        sendLog("  Col[$i]: " . substr((string)$rowData[$i], 0, 50), 'info');
                    }
                    continue;
                }
                
                // Only debug first few data rows
                if ($rowNumber <= 3) {
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getCalculatedValue();
                    }
                    sendLog("Row $rowNumber data:", 'info');
                    for ($i = 0; $i < min(80, count($rowData)); $i++) {
                        sendLog("  Col[$i]: " . substr((string)$rowData[$i], 0, 50), 'info');
                    }
                }
                
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }
                
                if (empty($rowData)) {
                    continue;
                }

                try {
                    // CSV columns for image import:
                    // 0: productNumber, 1: name, 2: stock, 3: taxId, 4: price.gross, 5: price.net, 6: currencyId, 7: cover, 8: media
                    $productNumber = trim((string)($rowData[0] ?? ''));  // Col[0]: Product Number
                    $coverUrl      = trim((string)($rowData[7] ?? ''));  // Col[7]: Cover image
                    $galleryUrls   = trim((string)($rowData[8] ?? ''));  // Col[8]: Gallery images

                    if (!$productNumber) {
                        $processed++;
                        continue;
                    }

                    // Get product ID
                    $stmt = $pdo->prepare("SELECT HEX(id) pid FROM product WHERE product_number = ?");
                    $stmt->execute([$productNumber]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$product) {
                        sendLog("Product not found: $productNumber", 'warning');
                        $errors++;
                        continue;
                    }

                    $productId = $product['pid'];

                    // Call cleanupOldProductMedia before uploading new images
                    cleanupOldProductMedia($productId, $productNumber, $pdo);

                    // Get product media folder
                    $folderId = $pdo->query("
                        SELECT LOWER(HEX(mf.id)) 
                        FROM media_folder mf
                        JOIN media_default_folder mdf ON mf.default_folder_id = mdf.id
                        WHERE mdf.entity = 'product'
                        LIMIT 1
                    ")->fetchColumn();

                    if (!$folderId || !Uuid::isValid($folderId)) {
                        sendLog("No valid media folder found for product entity.", 'error');
                        continue;
                    }

                    $mediaItems = [];
                    $coverProductMediaId = null;
                    $coverMediaId = null;

                    // Add timestamp to make filenames unique
                    $timestamp = time();

                    // ===== COVER IMAGE =====
                    if ($coverUrl) {
                        $path = realpath(__DIR__ . '/' . ltrim(parse_url($coverUrl, PHP_URL_PATH), '/'));
                        if ($path && file_exists($path)) {
                            $mediaId = Uuid::randomHex();

                            $mediaRepo->create([[
                                'id' => $mediaId,
                                'mediaFolderId' => $folderId,
                                'private' => false,
                                'name' => pathinfo($path, PATHINFO_FILENAME)
                            ]], $context);

                            $file = new MediaFile(
                                $path,
                                mime_content_type($path),
                                pathinfo($path, PATHINFO_EXTENSION),
                                filesize($path)
                            );

                            $baseName = pathinfo($path, PATHINFO_FILENAME);
                            $destination = $baseName . '-' . strtolower($productNumber) . '-' . $timestamp . '-cover';
                            $fileSaver->persistFileToMedia($file, $destination, $mediaId, $context);

                            $productMediaId = Uuid::randomHex();
                            $mediaItems[] = [
                                'id' => $productMediaId,
                                'mediaId' => $mediaId,
                                'position' => 0
                            ];
                            $coverProductMediaId = $productMediaId;
                            $coverMediaId = $mediaId;

                            $created++;
                        } else {
                            sendLog("Cover image not found: $path", 'error');
                        }
                    }

                    // ===== GALLERY IMAGES =====
                    if ($galleryUrls) {
                        $urls = explode('|', $galleryUrls);
                        foreach ($urls as $pos => $url) {
                            $url = trim($url);
                            if (!$url) continue;
                            $path = realpath(__DIR__ . '/' . ltrim(parse_url($url, PHP_URL_PATH), '/'));
                            if ($path && file_exists($path)) {
                                $mediaId = Uuid::randomHex();

                                $mediaRepo->create([[
                                    'id' => $mediaId,
                                    'mediaFolderId' => $folderId,
                                    'private' => false,
                                    'name' => pathinfo($path, PATHINFO_FILENAME)
                                ]], $context);

                                $file = new MediaFile(
                                    $path,
                                    mime_content_type($path),
                                    pathinfo($path, PATHINFO_EXTENSION),
                                    filesize($path)
                                );

                                $baseName = pathinfo($path, PATHINFO_FILENAME);
                                $destination = $baseName . '-' . strtolower($productNumber) . '-' . $timestamp . '-g' . ($pos + 1);
                                $fileSaver->persistFileToMedia($file, $destination, $mediaId, $context);

                                $mediaItems[] = [
                                    'id' => Uuid::randomHex(),
                                    'mediaId' => $mediaId,
                                    'position' => $pos + 1
                                ];

                                $created++;
                            } else {
                                sendLog("Gallery image not found: $path", 'error');
                            }
                        }
                    }

                    // ===== LINK MEDIA TO PRODUCT =====
                    if (!empty($mediaItems)) {
                        foreach ($mediaItems as $item) {
                            try {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO product_media (id, version_id, position, product_id, product_version_id, media_id, created_at)
                                     VALUES (UNHEX(?), UNHEX(?), ?, UNHEX(?), UNHEX(?), UNHEX(?), NOW(3))"
                                );
                                $stmt->execute([
                                    $item['id'],
                                    Defaults::LIVE_VERSION,
                                    (int)$item['position'],
                                    $productId,
                                    Defaults::LIVE_VERSION,
                                    $item['mediaId']
                                ]);
                            } catch (Throwable $e) {
                                sendLog("Product media insert failed for $productNumber: " . $e->getMessage(), 'warning');
                            }
                        }

                        if ($coverProductMediaId) {
                            try {
                                $stmt = $pdo->prepare("UPDATE product SET product_media_id = UNHEX(?) WHERE id = UNHEX(?)");
                                $stmt->execute([$coverProductMediaId, $productId]);
                                sendLog("Cover set for product $productNumber", 'info');
                            } catch (Throwable $e) {
                                sendLog("Cover set failed for $productNumber: " . $e->getMessage(), 'warning');
                            }
                        }
                    }

                    $processed++;

                } catch (\Throwable $e) {
                    sendLog("Row error: " . $e->getMessage(), 'error');
                    $errors++;
                }
            }
        } catch (\Throwable $e) {
            sendLog("Spreadsheet processing error: " . $e->getMessage(), 'error');
        }
    } else {
        // Handle CSV files
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            sendLog('Cannot open CSV file', 'error');
            return;
        }

        $header = fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            try {
                // CSV columns for image import:
                // 0: productNumber, 1: name, 2: stock, 3: taxId, 4: price.gross, 5: price.net, 6: currencyId, 7: cover, 8: media
                $productNumber = trim((string)($row[0] ?? ''));  // Col[0]: Product Number
                $coverUrl      = trim((string)($row[7] ?? ''));  // Col[7]: Cover image
                $galleryUrls   = trim((string)($row[8] ?? ''));  // Col[8]: Gallery images

                if (!$productNumber) {
                    $processed++;
                    continue;
                }

                // Get product ID
                $stmt = $pdo->prepare("SELECT HEX(id) pid FROM product WHERE product_number = ?");
                $stmt->execute([$productNumber]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    sendLog("Product not found: $productNumber", 'warning');
                    $errors++;
                    continue;
                }

                $productId = $product['pid'];

                // Call cleanupOldProductMedia before uploading new images
                cleanupOldProductMedia($productId, $productNumber, $pdo);

                // Get product media folder
                $folderId = $pdo->query("
                    SELECT LOWER(HEX(mf.id)) 
                    FROM media_folder mf
                    JOIN media_default_folder mdf ON mf.default_folder_id = mdf.id
                    WHERE mdf.entity = 'product'
                    LIMIT 1
                ")->fetchColumn();

                if (!$folderId || !Uuid::isValid($folderId)) {
                    sendLog("No valid media folder found for product entity.", 'error');
                    continue;
                }

                $mediaItems = [];
                $coverProductMediaId = null;
                $coverMediaId = null;

                // Add timestamp to make filenames unique
                $timestamp = time();

                // ===== COVER IMAGE =====
                if ($coverUrl) {
                    $path = realpath(__DIR__ . '/' . ltrim(parse_url($coverUrl, PHP_URL_PATH), '/'));
                    if ($path && file_exists($path)) {
                        $mediaId = Uuid::randomHex();

                        $mediaRepo->create([[
                            'id' => $mediaId,
                            'mediaFolderId' => $folderId,
                            'private' => false,
                            'name' => pathinfo($path, PATHINFO_FILENAME)
                        ]], $context);

                        $file = new MediaFile(
                            $path,
                            mime_content_type($path),
                            pathinfo($path, PATHINFO_EXTENSION),
                            filesize($path)
                        );

                        $baseName = pathinfo($path, PATHINFO_FILENAME);
                        $destination = $baseName . '-' . strtolower($productNumber) . '-' . $timestamp . '-cover';
                        $fileSaver->persistFileToMedia($file, $destination, $mediaId, $context);

                        $productMediaId = Uuid::randomHex();
                        $mediaItems[] = [
                            'id' => $productMediaId,
                            'mediaId' => $mediaId,
                            'position' => 0
                        ];
                        $coverProductMediaId = $productMediaId;
                        $coverMediaId = $mediaId;

                        $created++;
                    } else {
                        sendLog("Cover image not found: $path", 'error');
                    }
                }

                // ===== GALLERY IMAGES =====
                if ($galleryUrls) {
                    $urls = explode('|', $galleryUrls);
                    foreach ($urls as $pos => $url) {
                        $url = trim($url);
                        if (!$url) continue;
                        $path = realpath(__DIR__ . '/' . ltrim(parse_url($url, PHP_URL_PATH), '/'));
                        if ($path && file_exists($path)) {
                            $mediaId = Uuid::randomHex();

                            $mediaRepo->create([[
                                'id' => $mediaId,
                                'mediaFolderId' => $folderId,
                                'private' => false,
                                'name' => pathinfo($path, PATHINFO_FILENAME)
                            ]], $context);

                            $file = new MediaFile(
                                $path,
                                mime_content_type($path),
                                pathinfo($path, PATHINFO_EXTENSION),
                                filesize($path)
                            );

                            $baseName = pathinfo($path, PATHINFO_FILENAME);
                            $destination = $baseName . '-' . strtolower($productNumber) . '-' . $timestamp . '-g' . ($pos + 1);
                            $fileSaver->persistFileToMedia($file, $destination, $mediaId, $context);

                            $mediaItems[] = [
                                'id' => Uuid::randomHex(),
                                'mediaId' => $mediaId,
                                'position' => $pos + 1
                            ];

                            $created++;
                        } else {
                            sendLog("Gallery image not found: $path", 'error');
                        }
                    }
                }

                // ===== LINK MEDIA TO PRODUCT =====
                if (!empty($mediaItems)) {
                    foreach ($mediaItems as $item) {
                        try {
                            $stmt = $pdo->prepare(
                                "INSERT INTO product_media (id, version_id, position, product_id, product_version_id, media_id, created_at)
                                 VALUES (UNHEX(?), UNHEX(?), ?, UNHEX(?), UNHEX(?), UNHEX(?), NOW(3))"
                            );
                            $stmt->execute([
                                $item['id'],
                                Defaults::LIVE_VERSION,
                                (int)$item['position'],
                                $productId,
                                Defaults::LIVE_VERSION,
                                $item['mediaId']
                            ]);
                        } catch (Throwable $e) {
                            sendLog("Product media insert failed for $productNumber: " . $e->getMessage(), 'warning');
                        }
                    }

                    if ($coverProductMediaId) {
                        try {
                            $stmt = $pdo->prepare("UPDATE product SET product_media_id = UNHEX(?) WHERE id = UNHEX(?)");
                            $stmt->execute([$coverProductMediaId, $productId]);
                            sendLog("Cover set for product $productNumber", 'info');
                        } catch (Throwable $e) {
                            sendLog("Cover set failed for $productNumber: " . $e->getMessage(), 'warning');
                        }
                    }
                }

                $processed++;

            } catch (\Throwable $e) {
                sendLog("Row error: " . $e->getMessage(), 'error');
                $errors++;
            }
        }

        fclose($handle);
    }
    try {
        $stmt = $pdo->prepare("UPDATE vendor_import_jobs SET processed_rows = ?, error_rows = ?, status = 'completed', completed_at = NOW() WHERE job_id = ?");
        $stmt->execute([$processed, $errors, $jobId]);
    } catch (Throwable $e) {
        sendLog("Job completion update failed: " . $e->getMessage(), 'warning');
    }
    sendLog("Image Import Done: $processed products, $created images", 'success');
}





function checkProductExists($productNumber, $productRepo, $context) {
    $criteria = new Criteria();
    $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
    $criteria->setLimit(1);
    
    $result = $productRepo->search($criteria, $context);
    return $result->getTotal() > 0;
}

function prepareProductData($data) {
    // Generate new UUID if not provided or invalid
    $productId = !empty($data['id']) && Uuid::isValid($data['id']) 
        ? $data['id'] 
        : Uuid::randomHex();
    
    $name = trim($data['translations.DEFAULT.name'] ?? 'Unnamed Product');
    
    // Get default language ID (English)
    $languageId = '2fbb5fe2e29a4d70aa5854ce7ce3e20b';
    
    $product = [
        'id' => $productId,
        'productNumber' => trim($data['product_number']),
        'stock' => (int)($data['stock'] ?? 10),
        'active' => filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'taxId' => $data['tax_id'] ?? null,
        'translations' => [
            $languageId => ['name' => $name]
        ],
        'price' => [
            [
                'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                'gross' => (float)($data['price_gross'] ?? 0),
                'net' => (float)($data['price_net'] ?? 0),
                'linked' => false
            ]
        ]
    ];
    
    // Add optional fields
    if (!empty($data['description'])) {
        $product['description'] = $data['description'];
    }
    
    if (!empty($data['parent_id']) && Uuid::isValid($data['parent_id'])) {
        $product['parentId'] = $data['parent_id'];
    }
    
    if (!empty($data['manufacturer_id']) && Uuid::isValid($data['manufacturer_id'])) {
        $product['manufacturerId'] = $data['manufacturer_id'];
    }
    
    // Categories
    if (!empty($data['shopware_category_id']) && Uuid::isValid($data['shopware_category_id'])) {
        $product['categories'] = [
            ['id' => $data['shopware_category_id']]
        ];
    }
    
    // Custom fields
    $customFields = [];
    $customFieldMap = [
        'material' => ['products_additional_data_material'],
        'gender' => ['products_additional_data_gender'],
        'sleeve_length' => ['products_additional_data_armlength'],
        'article_number_short' => ['short_article_number'],
        'model_name' => ['products_additional_data_modelcode'],
        'item_in_box' => ['products_additional_data_iteminbox'],
        'item_in_bag' => ['products_additional_data_iteminbag'],
        'country' => ['products_additional_data_country'],
        'washing_temp' => ['products_additional_data_washtemp'],
        'supplier' => ['supplier'],
        'cut' => ['products_additional_data_fit', 'cut'],
        'febric_weight' => ['products_additional_data_areaweight'],
        'article_code' => ['products_additional_data_colorcode'],
        'gtin' => ['products_additional_data_gtin'],
        'supplier_article' => ['products_additional_data_supgln']
    ];
    
    foreach ($customFieldMap as $csvKey => $fieldNames) {
        if (!empty($data[$csvKey])) {
            foreach ((array)$fieldNames as $fieldName) {
                $customFields[$fieldName] = $data[$csvKey];
            }
        }
    }

    if (!empty($data['size_spec_pdf_url'])) {
        $customFields['products_additional_data_productsheet'] = (string)$data['size_spec_pdf_url'];
    }

    if (!empty($data['products_additional_data_prosheet']) && Uuid::isValid($data['products_additional_data_prosheet'])) {
        $customFields['products_additional_data_prosheet'] = $data['products_additional_data_prosheet'];
    }
    
    $customFields = sanitizeCustomFieldTypes($customFields);

    if (!empty($customFields)) {
        $product['customFields'] = $customFields;
    }
    
    return $product;
}

function processBatch($batch, $productRepo, $context) {
    $created = 0;
    $errors = 0;
    
    try {
        $productRepo->create($batch, $context);
        $created = count($batch);
    } catch (\Exception $e) {
        // Batch failed - try individual creates to see which ones fail
        $errorMsg = 'Batch creation failed: ' . $e->getMessage();
        error_log($errorMsg);
        sendLog($errorMsg, 'error');
        
        // Try to create each product individually
        foreach ($batch as $idx => $product) {
            try {
                $productRepo->create([$product], $context);
                $created++;
                sendLog("Individual create succeeded for product: " . ($product['productNumber'] ?? 'N/A'), 'info');
            } catch (\Exception $individualError) {
                $errors++;
                sendLog("Individual create failed for product [$idx] " . ($product['productNumber'] ?? 'N/A') . ": " . $individualError->getMessage(), 'error');
            }
        }
    }
    
    return [
        'created' => $created,
        'errors' => $errors
    ];
}

function sanitizeCustomFieldTypes(array $customFields): array {
    if (isset($customFields['products_additional_data_iteminbag'])) {
        $value = trim((string)$customFields['products_additional_data_iteminbag']);
        if ($value === '' || !is_numeric($value)) {
            unset($customFields['products_additional_data_iteminbag']);
        } else {
            $customFields['products_additional_data_iteminbag'] = (int)$value;
        }
    }

    if (isset($customFields['products_additional_data_iteminbox'])) {
        $customFields['products_additional_data_iteminbox'] = (string)$customFields['products_additional_data_iteminbox'];
    }

    return $customFields;
}

function applyProsheetCustomFieldUpdates(array $products, $productRepo, $context): void
{
    $updates = [];
    foreach ($products as $product) {
        $productId = $product['id'] ?? null;
        $productNumber = $product['productNumber'] ?? 'N/A';
        $customFields = $product['customFields'] ?? [];
        $prosheetMediaId = $customFields['products_additional_data_prosheet'] ?? null;

        if (empty($productId) || empty($prosheetMediaId) || !Uuid::isValid((string)$prosheetMediaId)) {
            continue;
        }

        $updates[] = [
            'id' => $productId,
            'customFields' => [
                'products_additional_data_prosheet' => $prosheetMediaId,
            ],
        ];

        sendLog("Post-create queue: Will set prosheet custom field for {$productNumber} ({$productId}) => {$prosheetMediaId}", 'info');
    }

    if (empty($updates)) {
        return;
    }

    try {
        $productRepo->update($updates, $context);
        sendLog('Post-create prosheet custom field update success for ' . count($updates) . ' product(s)', 'success');
    } catch (\Exception $e) {
        sendLog('Prosheet custom field post-create update failed: ' . $e->getMessage(), 'warning');
    }
}

function processTierPriceImport($csvFile, $connection, $productRepo, $context, $jobId, $pdo) {
    sendLog('Processing tier price import', 'info');
    
    $processed = 0;
    $created = 0;
    $errors = 0;
    
    // Open CSV
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        sendLog('Cannot open tier price CSV file', 'error');
        return;
    }
    
    // Read header
    $header = fgetcsv($handle, 0, ';');
    if (!$header) {
        sendLog('Invalid CSV - no header found', 'error');
        fclose($handle);
        return;
    }
    
    sendLog('CSV Header: ' . implode(', ', $header), 'info');
    
    // Expected columns: id, product_id, rule_id, price_net, price_gross, quantity_start, quantity_end
    $totalRows = 0;
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $totalRows++;
    }
    rewind($handle);
    fgetcsv($handle, 0, ';'); // Skip header again
    
    sendLog("Total tier price rows: $totalRows", 'info');
    sendProgress($processed, $created, 0, $errors, $totalRows);
    
    $rowNumber = 0;
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $rowNumber++;
        
        try {
            // CSV columns: id, product_id, rule_id, price_net, price_gross, quantity_start, quantity_end
            $tierPriceId = $row[0] ?? null;
            $productId = $row[1] ?? null;
            $ruleId = $row[2] ?? null;
            $priceNet = floatval($row[3] ?? 0);
            $priceGross = floatval($row[4] ?? 0);
            $quantityStart = intval($row[5] ?? 1);
            $quantityEnd = !empty($row[6]) ? intval($row[6]) : null;
            
            if (empty($productId) || empty($ruleId)) {
                sendLog("Row $rowNumber: Skipped - missing product_id or rule_id", 'skip');
                $errors++;
                $processed++;
                continue;
            }
            
            // Insert tier price
            $sql = "INSERT INTO product_price (id, version_id, product_id, product_version_id, rule_id, 
                                         price, quantity_start, quantity_end, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                        price = VALUES(price), 
                        quantity_start = VALUES(quantity_start), 
                        quantity_end = VALUES(quantity_end)";
            
            $price = json_encode([[
                'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                'net' => $priceNet,
                'gross' => $priceGross,
                'linked' => false
            ]]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                hex2bin($tierPriceId),
                hex2bin('0fa91ce3e96a4bc2be4bd9ce752c3425'), // version_id
                hex2bin($productId),
                hex2bin('0fa91ce3e96a4bc2be4bd9ce752c3425'), // product_version_id
                hex2bin($ruleId),
                $price,
                $quantityStart,
                $quantityEnd
            ]);
            
            $created++;
            $processed++;
            
            if ($processed % 50 == 0) {
                sendLog("Progress: $processed/$totalRows processed, $created created, $errors errors", 'info');
                sendProgress($processed, $created, 0, $errors, $totalRows);
            }
            
        } catch (\Exception $e) {
            sendLog("Row $rowNumber: Error - " . $e->getMessage(), 'error');
            $errors++;
            $processed++;
        }
    }
    
    fclose($handle);
    
    // Update job status
    $updateSql = "UPDATE vendor_import_jobs 
                  SET processed_rows = ?, 
                      error_rows = ?, 
                      status = 'completed', 
                      completed_at = NOW() 
                  WHERE job_id = ?";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([$created, $errors, $jobId]);
    
    sendLog("Tier price import completed! Processed: $processed, Created: $created, Errors: $errors", 'success');
    sendProgress($processed, $created, 0, $errors, $totalRows);
    echo json_encode(['type' => 'complete', 'processed' => $processed, 'created' => $created, 'skipped' => 0, 'errors' => $errors]) . "\n";
    flush();
}

function resolveOrCreateProsheetMediaId(string $pdfUrl, $mediaRepo, FileSaver $fileSaver, Context $context, PDO $pdo): ?string
{
    static $cache = [];

    $pdfUrl = trim($pdfUrl);
    if ($pdfUrl === '') {
        return null;
    }

    if (isset($cache[$pdfUrl])) {
        sendLog('Prosheet media cache hit for URL: ' . $pdfUrl . ' => ' . $cache[$pdfUrl], 'info');
        return $cache[$pdfUrl];
    }

    $pathPart = parse_url($pdfUrl, PHP_URL_PATH) ?: $pdfUrl;
    $localPath = realpath(__DIR__ . '/' . ltrim((string)$pathPart, '/'));
    if ($localPath === false || !is_file($localPath)) {
        sendLog('Prosheet file not found: ' . $pdfUrl, 'warning');
        return null;
    }

    sendLog('Prosheet file found: ' . $localPath, 'info');

    $fileName = pathinfo($localPath, PATHINFO_FILENAME);
    $fileExt = strtolower((string)pathinfo($localPath, PATHINFO_EXTENSION));
    if ($fileExt !== 'pdf') {
        return null;
    }

    $mediaFolderId = $pdo->query("SELECT LOWER(HEX(mf.id)) 
                        FROM media_folder mf
                        JOIN media_default_folder mdf ON mf.default_folder_id = mdf.id
                        WHERE mdf.entity = 'product'
                        LIMIT 1")->fetchColumn();

    if (!$mediaFolderId || !Uuid::isValid((string)$mediaFolderId)) {
        sendLog('No product media folder found for prosheet import', 'warning');
        return null;
    }

    $destination = $fileName . '-prosheet';

    try {
        $existingStmt = $pdo->prepare("SELECT LOWER(HEX(id)) AS media_id FROM media WHERE file_name = ? AND file_extension = 'pdf' LIMIT 1");
        $existingStmt->execute([$destination]);
        $existingMediaId = $existingStmt->fetchColumn();
        if (!empty($existingMediaId) && Uuid::isValid((string)$existingMediaId)) {
            $cache[$pdfUrl] = (string)$existingMediaId;
            sendLog('Prosheet media already exists, reuse: ' . $existingMediaId . ' for ' . $pdfUrl, 'info');
            return (string)$existingMediaId;
        }

        $mediaId = Uuid::randomHex();
        $mediaRepo->create([[
            'id' => $mediaId,
            'mediaFolderId' => $mediaFolderId,
            'private' => false,
            'name' => $fileName,
        ]], $context);

        $mimeType = mime_content_type($localPath) ?: 'application/pdf';
        $mediaFile = new MediaFile(
            $localPath,
            $mimeType,
            $fileExt,
            filesize($localPath)
        );

        $fileSaver->persistFileToMedia($mediaFile, $destination, $mediaId, $context);

        $cache[$pdfUrl] = $mediaId;
        sendLog('Prosheet media created: ' . $mediaId . ' for ' . $pdfUrl, 'success');
        return $mediaId;
    } catch (\Exception $e) {
        try {
            $existingStmt = $pdo->prepare("SELECT LOWER(HEX(id)) AS media_id FROM media WHERE file_name = ? AND file_extension = 'pdf' LIMIT 1");
            $existingStmt->execute([$destination]);
            $existingMediaId = $existingStmt->fetchColumn();
            if (!empty($existingMediaId) && Uuid::isValid((string)$existingMediaId)) {
                $cache[$pdfUrl] = (string)$existingMediaId;
                sendLog('Prosheet media recovered after create error, reuse: ' . $existingMediaId . ' for ' . $pdfUrl, 'warning');
                return (string)$existingMediaId;
            }
        } catch (\Exception $lookupException) {
            sendLog('Prosheet media fallback lookup failed: ' . $lookupException->getMessage(), 'warning');
        }

        sendLog('Prosheet media create failed for ' . $pdfUrl . ': ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Import variant options from CSV and create product_option entries
 */
function importVariantOptionsFromCsv($csvFile, $pdo) {
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        return;
    }
    
    // Read header
    $header = fgetcsv($handle, 0, ';');
    if (!$header) {
        fclose($handle);
        return;
    }
    
    $headerMap = array_flip($header);
    
    if (!isset($headerMap['product_number']) || !isset($headerMap['optionIds']) || !isset($headerMap['parent_id'])) {
        fclose($handle);
        return;
    }
    
    $productNumberIdx = $headerMap['product_number'];
    $optionIdsIdx = $headerMap['optionIds'];
    $parentIdIdx = $headerMap['parent_id'];
    
    $updated = 0;
    $rowNum = 1;
    
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $rowNum++;
        
        $productNumber = trim($row[$productNumberIdx] ?? '');
        $optionIds = trim($row[$optionIdsIdx] ?? '');
        $parentId = trim($row[$parentIdIdx] ?? '');
        
        // Skip if no parent_id (parent products) or no optionIds
        if (empty($parentId) || empty($optionIds)) {
            continue;
        }
        
        try {
            // Find product by product_number
            $findStmt = $pdo->prepare("SELECT id FROM product WHERE product_number = ?");
            $findStmt->execute([$productNumber]);
            $product = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                continue;
            }
            
            $productId = $product['id'];
            
            // Split optionIds (format: "id1|id2|...")
            $optionIdsList = array_filter(array_map('trim', explode('|', $optionIds)));
            
            if (empty($optionIdsList)) {
                continue;
            }
            
            // Delete existing product_option entries for this product
            $pdo->prepare("DELETE FROM product_option WHERE product_id = ?")->execute([$productId]);
            
            // Insert new product_option entries
            $insertStmt = $pdo->prepare("
                INSERT INTO product_option (product_id, product_version_id, property_group_option_id)
                VALUES (?, UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425'), ?)
            ");
            
            foreach ($optionIdsList as $optionId) {
                try {
                    $insertStmt->execute([$productId, hex2bin($optionId)]);
                    $updated++;
                } catch (\Exception $e) {
                    // Skip invalid option IDs
                }
            }
            
        } catch (\Exception $e) {
            // Skip errors and continue
        }
    }
    
    fclose($handle);
    
    if ($updated > 0) {
        sendLog("Variant options imported: $updated options added", 'success');
    }
}
