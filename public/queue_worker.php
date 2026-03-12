<?php
/**
 * Queue Worker - Processes import batches in background using create_product.php logic
 * Usage: php queue_worker.php <job_id>
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;

define('QUEUE_DIR', __DIR__ . '/import-queue/');
define('LOG_DIR', __DIR__ . '/import-logs/');

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
$container = $kernel->getContainer();

require_once __DIR__ . '/db_config.php';

// ==================== QUEUE WORKER CLASS ====================

class QueueWorker {
    private $jobId;
    private $queueData;
    private $status;
    private $logFile;
    private $db;
    private $container;
    
    public function __construct($jobId, $container) {
        $this->jobId = $jobId;
        $this->logFile = LOG_DIR . $jobId . '.log';
        $this->db = Database::getConnection();
        $this->container = $container;
        
        // Load queue data
        $queueFile = QUEUE_DIR . $jobId . '.json';
        if (!file_exists($queueFile)) {
            throw new Exception('Queue file not found');
        }
        $this->queueData = json_decode(file_get_contents($queueFile), true);
        
        // Load status
        $this->loadStatus();
    }
    
    private function loadStatus() {
        $statusFile = QUEUE_DIR . $this->jobId . '_status.json';
        $this->status = json_decode(file_get_contents($statusFile), true);
    }
    
    private function saveStatus() {
        $this->status['updated_at'] = date('Y-m-d H:i:s');
        $statusFile = QUEUE_DIR . $this->jobId . '_status.json';
        file_put_contents($statusFile, json_encode($this->status, JSON_PRETTY_PRINT));
        
        // Also update database
        try {
            $sql = "UPDATE vendor_import_jobs 
                    SET processed_rows = :processed,
                        error_rows = :errors,
                        status = :status
                    WHERE job_id = :job_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':processed' => $this->status['processed'],
                ':errors' => $this->status['errors'],
                ':status' => $this->status['status'],
                ':job_id' => $this->jobId
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update database: " . $e->getMessage());
        }
    }
    
    private function log($message, $type = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "$timestamp - [$type] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    public function process() {
        $this->log("Starting queue processing for job: " . $this->jobId);
        
        $importType = $this->queueData['import_type'];
        $vendorName = strtolower($this->queueData['vendor_name'] ?? '');
        $originalFile = $this->queueData['original_file'] ?? '';
        $categoryMapping = $this->queueData['category_mapping'] ?? [];
        
        $this->log("Import Type: $importType, Vendor: $vendorName");
        $this->log("Original File: $originalFile");
        $this->log("Category Mappings: " . count($categoryMapping));
        
        // Set $_POST to simulate form submission
        // create_product.php expects full path in temp_file, not just basename
        $uploadedFilePath = __DIR__ . '/csv-imports/' . $importType . '/' . basename($originalFile);
        
        $_POST['vendor'] = $vendorName;
        $_POST['vendorCategories'] = array_keys($categoryMapping);
        $_POST['shopware_category'] = array_values($categoryMapping);
        $_POST['temp_file'] = $uploadedFilePath;
        
        $this->log("File path for processing: " . $uploadedFilePath);
        
        try {
            if ($importType === 'product') {
                $this->processProductImport();
            } elseif ($importType === 'images') {
                $this->processImageImport();
            } elseif ($importType === 'tierprice') {
                $this->processTierPriceImport();
            }
            
            $this->status['status'] = 'completed';
            $this->status['completed_at'] = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            $this->log("ERROR: Processing failed - " . $e->getMessage(), 'ERROR');
            $this->status['status'] = 'failed';
            $this->status['errors']++;
        }
        
        $this->saveStatus();
        
        // Update database completion time
        try {
            $sql = "UPDATE vendor_import_jobs 
                    SET completed_at = NOW()
                    WHERE job_id = :job_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':job_id' => $this->jobId]);
        } catch (PDOException $e) {
            error_log("Failed to update completion time: " . $e->getMessage());
        }
        
        $this->log("Queue processing completed", 'SUCCESS');
    }
    
    private function processProductImport() {
        $this->log("Processing product import using create_product.php logic");
        
        // Call the actual create_product.php - it uses $_POST which we set above
        ob_start();
        include __DIR__ . '/create_product.php';
        $output = ob_get_clean();
        
        $this->log("Product import output: " . substr($output, 0, 500));
        
        // Find the generated CSV file (latest one)
        $csvDir = __DIR__ . '/csv-imports/product/';
        $csvFile = null;
        if (is_dir($csvDir)) {
            $files = glob($csvDir . '*.csv');
            if (!empty($files)) {
                usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                $csvFile = $files[0];
            }
        }
        
        // Fix product names after import
        if ($csvFile && file_exists($csvFile)) {
            $this->log("Fixing product names from CSV: " . basename($csvFile));
            define('CSV_FILE_PATH', $csvFile);
            ob_start();
            include __DIR__ . '/update_product_names_from_csv.php';
            $fixOutput = ob_get_clean();
            $this->log("Name fix output: " . substr($fixOutput, 0, 300));
        } else {
            $this->log("Warning: CSV file not found for name fixing");
        }
        
        // Clear cache after import
        $this->log("Clearing cache...");
        exec('cd ' . __DIR__ . '/../ && bin/console cache:clear 2>&1', $cacheOutput, $cacheReturn);
        if ($cacheReturn === 0) {
            $this->log("Cache cleared successfully");
        }
        
        $this->status['processed'] = 100; // Update based on actual count
    }
    
    private function processImageImport() {
        $this->log("Processing image import using create_product_media.php logic");
        
        ob_start();
        include __DIR__ . '/create_product_media.php';
        $output = ob_get_clean();
        
        $this->log("Image import output: " . substr($output, 0, 500));
        $this->status['processed'] = 100;
    }
    
    private function processTierPriceImport() {
        $this->log("Processing tier price import from generated CSV");
        
        // Get the CSV file path from queue data
        $csvFile = $this->queueData['original_file'] ?? '';
        
        if (!file_exists($csvFile)) {
            $this->log("CSV file not found: " . $csvFile, 'ERROR');
            throw new Exception("Tier price CSV file not found");
        }
        
        $this->log("Reading tier price CSV: " . $csvFile);
        
        // Get product repository for ID validation
        $productRepository = $this->container->get('product.repository');
        $context = Context::createDefaultContext();
        $connection = $this->container->get(Connection::class);
        
        $processed = 0;
        $errors = 0;
        $inserted = 0;
        
        // Read CSV and process tier prices
        if (($handle = fopen($csvFile, 'r')) !== false) {
            // Skip header
            $header = fgetcsv($handle, 1000, ';');
            $this->log("CSV Header: " . implode(', ', $header));
            
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                try {
                    // CSV columns: id, product_id, rule_id, price_net, price_gross, quantity_start, quantity_end
                    $tierPriceId = $data[0] ?? null;
                    $productId = $data[1] ?? null;
                    $ruleId = $data[2] ?? null;
                    $priceNet = floatval($data[3] ?? 0);
                    $priceGross = floatval($data[4] ?? 0);
                    $quantityStart = intval($data[5] ?? 1);
                    $quantityEnd = !empty($data[6]) ? intval($data[6]) : null;
                    
                    if (empty($productId) || empty($ruleId)) {
                        $this->log("Skipping row - missing product_id or rule_id", 'WARNING');
                        $errors++;
                        continue;
                    }
                    
                    // Insert tier price into database
                    $sql = "INSERT INTO product_price (id, product_id, product_version_id, rule_id, 
                                                 price, quantity_start, quantity_end, created_at) 
                            VALUES (:id, :product_id, :version_id, :rule_id, 
                                    :price, :quantity_start, :quantity_end, NOW()) 
                            ON DUPLICATE KEY UPDATE 
                                price = VALUES(price), 
                                quantity_start = VALUES(quantity_start), 
                                quantity_end = VALUES(quantity_end)";
                    \n                    $price = json_encode([[\n                        'currencyId' => '0197e3dc156670898733ddc8b5f01093',\n                        'net' => $priceNet,\n                        'gross' => $priceGross,\n                        'linked' => false\n                    ]]);\n                    \n                    $stmt = $connection->prepare($sql);\n                    $stmt->executeStatement([\n                        'id' => hex2bin($tierPriceId),\n                        'product_id' => hex2bin($productId),\n                        'version_id' => hex2bin('0fa91ce3e96a4bc2be4bd9ce752c3425'),\n                        'rule_id' => hex2bin($ruleId),\n                        'price' => $price,\n                        'quantity_start' => $quantityStart,\n                        'quantity_end' => $quantityEnd\n                    ]);\n                    \n                    $inserted++;\n                    $processed++;\n                    \n                    if ($processed % 50 == 0) {\n                        $this->status['processed'] = $processed;\n                        $this->status['errors'] = $errors;\n                        $this->saveStatus();\n                        $this->log(\"Progress: $processed processed, $inserted inserted, $errors errors\");\n                    }\n                    \n                } catch (\\Exception $e) {\n                    $this->log(\"Error processing tier price: \" . $e->getMessage(), 'ERROR');\n                    $errors++;\n                }\n            }\n            \n            fclose($handle);\n        }\n        \n        $this->status['processed'] = $processed;\n        $this->status['errors'] = $errors;\n        $this->log(\"Tier price import completed: $processed processed, $inserted inserted, $errors errors\");\n    }
}

// ==================== MAIN EXECUTION ====================

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

if (!isset($argv[1])) {
    die("Usage: php queue_worker.php <job_id>\n");
}

$jobId = $argv[1];

try {
    $worker = new QueueWorker($jobId, $container);
    $worker->process();
    echo "✅ Queue processing completed successfully\n";
} catch (Exception $e) {
    echo "❌ Queue processing failed: " . $e->getMessage() . "\n";
    exit(1);
}
