<?php

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});
/**
 * Import Processor - Handles file uploads and initiates queue processing
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configuration
define('UPLOAD_DIR', __DIR__ . '/csv-imports/');
define('LOG_DIR', __DIR__ . '/import-logs/');
define('QUEUE_DIR', __DIR__ . '/import-queue/');

require_once __DIR__ . '/db_config.php';

class ImportProcessor {
    private $jobId;
    private $importType;
    private $batchSize;
    private $categoryMapping;
    private $vendorName;
    private $db;
    
    public function __construct() {
        $this->jobId = uniqid('job_', true);
        $this->db = Database::getConnection();
    }
    
    public function processRequest() {
        try {
            file_put_contents("/tmp/import_debug.log", "\n\n=== REQUEST START " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
            file_put_contents("/tmp/import_debug.log", "POST: " . json_encode($_POST) . "\n", FILE_APPEND);
            file_put_contents("/tmp/import_debug.log", "FILES: " . json_encode(array_keys($_FILES)) . "\n", FILE_APPEND);
            
            // Validate request
            if (!isset($_POST['import_type'])) {
                throw new Exception('Import type not specified');
            }
            
            if (!isset($_FILES['csv_file'])) {
                throw new Exception('No file uploaded');
            }
            
            $this->importType = $_POST['import_type'];
            $this->batchSize = isset($_POST['batch_size']) ? (int)$_POST['batch_size'] : 25;
            $this->vendorName = isset($_POST['vendor_name']) && !empty($_POST['vendor_name']) ? trim($_POST['vendor_name']) : null;
            
            // Process category mapping
            $this->categoryMapping = $this->processCategoryMapping();
            
            // Upload and process file
            file_put_contents("/tmp/import_debug.log", "Before handleFileUpload\n", FILE_APPEND);
            $uploadedFile = $this->handleFileUpload();
            file_put_contents("/tmp/import_debug.log", "After handleFileUpload: " . $uploadedFile . "\n", FILE_APPEND);
            $originalFileName = $_FILES['csv_file']['name'];
            file_put_contents("/tmp/import_debug.log", "Original filename: " . $originalFileName . "\n", FILE_APPEND);
            
            // Direct processing - Generate vendor product CSV immediately
            if ($this->importType === 'product' && $this->vendorName) {
                $result = $this->generateVendorProductCsv($uploadedFile);
                
                // Check if result is an error (array with success => false)
                if (is_array($result) && isset($result['success']) && !$result['success']) {
                    return $result;  // Return error response
                }
                
                // Update tracking with generated CSV info
                $this->updateImportTracking($result);
                
                // Get job_id from database (latest job for this vendor)
                $jobId = null;
                $csvPath = is_string($result) && file_exists($result) ? $result : '';
                if ($csvPath) {
                    try {
                        $sql = "SELECT job_id, total_rows FROM vendor_import_jobs WHERE file_path = ? ORDER BY created_at DESC LIMIT 1";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$csvPath]);
                        $jobData = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($jobData) {
                            $jobId = $jobData['job_id'];
                        }
                    } catch (\Exception $e) {
                        error_log("Failed to get job_id: " . $e->getMessage());
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'CSV generated successfully! Starting product import...',
                    'status' => 'pending',
                    'csv_file' => is_string($result) && file_exists($result) ? basename($result) : 'Generated',
                    'csv_path' => $csvPath,
                    'job_id' => $jobId,
                    'vendor_name' => $this->vendorName,
                    'direct_processing' => true
                ];
            }
            
            // Tier price import handling - Generate CSV then create queue for processing
            if ($this->importType === 'tierprice') {
                file_put_contents("/tmp/import_debug.log", "\n--- TIER PRICE IMPORT DETECTED ---\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "Uploaded file: " . $uploadedFile . "\n", FILE_APPEND);
                
                $result = $this->generateTierPriceCsv($uploadedFile);
                file_put_contents("/tmp/import_debug.log", "generateTierPriceCsv result: " . ($result ? $result : 'NULL') . "\n", FILE_APPEND);
                
                // Check if result is an error (array with success => false)
                if (is_array($result) && isset($result['success']) && !$result['success']) {
                    return $result;  // Return error response
                }
                
                // Always save job to database (even if CSV generation fails for debugging)
                $csvPath = (!empty($result) && is_string($result) && file_exists($result)) ? $result : $uploadedFile;
                $this->saveTierPriceToDatabase(basename($uploadedFile), $csvPath);
                file_put_contents("/tmp/import_debug.log", "Saved to database: " . $this->jobId . "\n", FILE_APPEND);
                
                // If CSV generation successful, create queue for processing
                if (!empty($result) && is_string($result) && file_exists($result)) {
                    // Create queue for tier price processing
                    $queueData = $this->createTierPriceQueue($result);
                    
                    return [
                        'success' => true,
                        'message' => 'Tier price CSV generated! Starting import...',
                        'status' => 'pending',
                        'csv_file' => basename($result),
                        'csv_path' => $result,
                        'job_id' => $this->jobId,
                        'vendor_name' => $this->vendorName,
                        'import_type' => 'tierprice'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to generate tier price CSV - Job saved for debugging',
                    'job_id' => $this->jobId,
                    'csv_file' => basename($uploadedFile),
                    'csv_path' => $uploadedFile,
                    'result' => $result
                ];
            }
            
            // Images import handling - Generate CSV using create_product_media.php
            if ($this->importType === 'images' && $this->vendorName) {
                file_put_contents("/tmp/import_debug.log", "\n--- IMAGES IMPORT DETECTED ---\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "Import Type: " . $this->importType . "\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "Vendor Name: " . $this->vendorName . "\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "Uploaded File: " . $uploadedFile . "\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "File exists: " . (file_exists($uploadedFile) ? 'yes' : 'NO') . "\n", FILE_APPEND);
                
                error_log("========== IMAGES IMPORT START (CSV GENERATION) ==========");
                error_log("Import Type: " . $this->importType);
                error_log("Vendor Name: " . $this->vendorName);
                error_log("Uploaded File: " . $uploadedFile . " | Exists: " . (file_exists($uploadedFile) ? 'yes' : 'NO'));
                
                // Save to database
                $this->saveImageImportToDatabase(basename($uploadedFile), $uploadedFile);
                file_put_contents("/tmp/import_debug.log", "Database saved\n", FILE_APPEND);
                
                // Generate CSV using create_product_media.php
                error_log("Calling generateProductMediaCsv()...");
                file_put_contents("/tmp/import_debug.log", "Calling generateProductMediaCsv()...\n", FILE_APPEND);
                $csvPath = $this->generateProductMediaCsv($uploadedFile);
                error_log("generateProductMediaCsv() returned: " . ($csvPath ? $csvPath : 'NULL'));
                file_put_contents("/tmp/import_debug.log", "generateProductMediaCsv() returned: " . ($csvPath ? $csvPath : 'NULL') . "\n", FILE_APPEND);
                
                if ($csvPath && file_exists($csvPath)) {
                    // Create queue for image processing
                    $queueData = $this->createImageQueue($csvPath);
                    
                    // Trigger image processing via HTTP request (fire and forget)
                    error_log("Triggering image worker via HTTP...");
                    $this->triggerImageWorkerHttp($this->jobId);
                    
                    return [
                        'success' => true,
                        'message' => 'Product media CSV generated successfully! Images are being imported in background...',
                        'status' => 'processing',
                        'csv_file' => basename($csvPath),
                        'csv_path' => $csvPath,
                        'job_id' => $this->jobId,
                        'vendor_name' => $this->vendorName,
                        'import_type' => 'images'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to generate product media CSV',
                        'job_id' => $this->jobId
                    ];
                }
            }
            
            // Legacy queue processing for other import types
            $queueData = $this->createQueue($uploadedFile);
            
            // Save to database
            $this->saveToDatabase($originalFileName, $uploadedFile, count($queueData['batches']));
            
            // Initialize job status
            $this->initializeJobStatus();
            
            // Start background processing
            $this->startBackgroundProcessing();
            
            return [
                'success' => true,
                'job_id' => $this->jobId,
                'message' => 'Import job created successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function processCategoryMapping() {
        $mapping = [];
        
        if (isset($_POST['vendorCategories']) && isset($_POST['shopware_category'])) {
            $vendorCategories = $_POST['vendorCategories'];
            $shopwareCategories = $_POST['shopware_category'];
            
            for ($i = 0; $i < count($vendorCategories); $i++) {
                if (!empty($vendorCategories[$i]) && !empty($shopwareCategories[$i])) {
                    $mapping[trim($vendorCategories[$i])] = trim($shopwareCategories[$i]);
                }
            }
        }
        
        return $mapping;
    }
    
    private function handleFileUpload() {
        file_put_contents("/tmp/import_debug.log", "  handleFileUpload START\n", FILE_APPEND);
        
        $file = $_FILES['csv_file'];
        file_put_contents("/tmp/import_debug.log", "  File error: " . $file['error'] . "\n", FILE_APPEND);
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            file_put_contents("/tmp/import_debug.log", "  ERROR: File upload error: " . $file['error'] . "\n", FILE_APPEND);
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        file_put_contents("/tmp/import_debug.log", "  Extension: " . $extension . "\n", FILE_APPEND);
        
        $allowedExtensions = ['csv', 'xls', 'xlsx', 'json'];
        if (!in_array($extension, $allowedExtensions)) {
            file_put_contents("/tmp/import_debug.log", "  ERROR: Invalid extension\n", FILE_APPEND);
            throw new Exception('Only CSV, XLS, XLSX, and JSON files are allowed');
        }
        
        // Create target directory
        $targetDir = UPLOAD_DIR . $this->importType . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        file_put_contents("/tmp/import_debug.log", "  Target dir: " . $targetDir . "\n", FILE_APPEND);
        
        // Generate unique filename - keep original extension for direct processing
        $timestamp = date('Y-m-d_H-i-s');
        
        // For direct vendor processing, keep original file format
        if (($this->importType === 'product' || $this->importType === 'images' || $this->importType === 'tierprice') && $this->vendorName) {
            $filename = $this->importType . '_' . $timestamp . '_' . $this->jobId . '.' . $extension;
            $targetPath = $targetDir . $filename;
            file_put_contents("/tmp/import_debug.log", "  Target path: " . $targetPath . "\n", FILE_APPEND);
            
            // Simply move uploaded file without conversion
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                file_put_contents("/tmp/import_debug.log", "  ERROR: move_uploaded_file failed\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "  Tmp name: " . $file['tmp_name'] . "\n", FILE_APPEND);
                file_put_contents("/tmp/import_debug.log", "  Tmp exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'NO') . "\n", FILE_APPEND);
                
                // Fallback: copy instead of move_uploaded_file
                if (file_exists($file['tmp_name']) && copy($file['tmp_name'], $targetPath)) {
                    file_put_contents("/tmp/import_debug.log", "  Fallback: copy() succeeded\n", FILE_APPEND);
                    return $targetPath;
                }
                
                throw new Exception('Failed to save uploaded file');
            }
            
            file_put_contents("/tmp/import_debug.log", "  File moved successfully\n", FILE_APPEND);
            return $targetPath;
        }
        
        // For queue processing, convert to CSV
        $filename = $this->importType . '_' . $timestamp . '_' . $this->jobId . '.csv';
        $targetPath = $targetDir . $filename;
        
        // Convert Excel files to CSV
        if (in_array($extension, ['xls', 'xlsx'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                
                // Create CSV file
                $csvHandle = fopen($targetPath, 'w');
                if (!$csvHandle) {
                    throw new Exception('Cannot create CSV file');
                }
                
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getCalculatedValue();
                    }
                    
                    fputcsv($csvHandle, $rowData);
                }
                
                fclose($csvHandle);
                
            } catch (\Exception $e) {
                throw new Exception('Error converting Excel to CSV: ' . $e->getMessage());
            }
        } elseif ($extension === 'json') {
            // Convert JSON to CSV
            try {
                $jsonContent = file_get_contents($file['tmp_name']);
                $jsonData = json_decode($jsonContent, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON file: ' . json_last_error_msg());
                }
                
                // Create CSV file
                $csvHandle = fopen($targetPath, 'w');
                if (!$csvHandle) {
                    throw new Exception('Cannot create CSV file');
                }
                
                // Handle array of objects (most common format)
                if (isset($jsonData['result']) && is_array($jsonData['result'])) {
                    // Newwave format: {result: [...]}
                    $data = $jsonData['result'];
                } elseif (is_array($jsonData) && isset($jsonData[0])) {
                    // Direct array format
                    $data = $jsonData;
                } else {
                    throw new Exception('Unsupported JSON format');
                }
                
                // Extract headers from first item
                if (!empty($data)) {
                    $firstItem = $data[0];
                    $headers = $this->flattenJsonKeys($firstItem);
                    fputcsv($csvHandle, $headers);
                    
                    // Write data rows
                    foreach ($data as $item) {
                        $row = $this->flattenJsonValues($item, $headers);
                        fputcsv($csvHandle, $row);
                    }
                }
                
                fclose($csvHandle);
                
            } catch (\Exception $e) {
                throw new Exception('Error converting JSON to CSV: ' . $e->getMessage());
            }
        } else {
            // For CSV files, just move them
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file');
            }
        }
        
        return $targetPath;
    }
    
    private function createQueue($csvFile) {
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception('Cannot open CSV file');
        }
        
        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception('Invalid CSV file - no header found');
        }
        
        // Create queue data
        $queueData = [
            'job_id' => $this->jobId,
            'import_type' => $this->importType,
            'batch_size' => $this->batchSize,
            'vendor_name' => $this->vendorName,
            'category_mapping' => $this->categoryMapping,
            'csv_file' => $csvFile,
            'header' => $header,
            'batches' => []
        ];
        
        // Read data and create batches
        $batchNumber = 0;
        $currentBatch = [];
        $rowNumber = 1; // Start from 1 (after header)
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                $currentBatch[] = array_combine($header, $row);
                
                if (count($currentBatch) >= $this->batchSize) {
                    $queueData['batches'][] = [
                        'batch_number' => $batchNumber++,
                        'rows' => $currentBatch,
                        'status' => 'pending'
                    ];
                    $currentBatch = [];
                }
            }
            $rowNumber++;
        }
        
        // Add remaining rows
        if (!empty($currentBatch)) {
            $queueData['batches'][] = [
                'batch_number' => $batchNumber,
                'rows' => $currentBatch,
                'status' => 'pending'
            ];
        }
        
        fclose($handle);
        
        // Save queue file
        $queueFile = QUEUE_DIR . $this->jobId . '.json';
        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));
        
        return $queueData;
    }
    
    private function saveToDatabase($originalFileName, $filePath, $totalBatches) {
        try {
            $sql = "INSERT INTO vendor_import_jobs 
                    (job_id, vendor_name, import_type, file_name, file_path, batch_size, 
                     total_rows, status, category_mapping, created_at, started_at) 
                    VALUES 
                    (:job_id, :vendor_name, :import_type, :file_name, :file_path, :batch_size, 
                     :total_rows, :status, :category_mapping, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            
            // Calculate total rows
            $queueFile = QUEUE_DIR . $this->jobId . '.json';
            $queueData = json_decode(file_get_contents($queueFile), true);
            $totalRows = 0;
            if (is_array($queueData) && isset($queueData['batches']) && is_array($queueData['batches'])) {
                foreach ($queueData['batches'] as $batch) {
                    $totalRows += count($batch['rows']);
                }
            }
            
            $stmt->execute([
                ':job_id' => $this->jobId,
                ':vendor_name' => $this->vendorName,
                ':import_type' => $this->importType,
                ':file_name' => $originalFileName,
                ':file_path' => $filePath,
                ':batch_size' => $this->batchSize,
                ':total_rows' => $totalRows,
                ':status' => 'processing',
                ':category_mapping' => !empty($this->categoryMapping) ? json_encode($this->categoryMapping) : null
            ]);
            
        } catch (PDOException $e) {
            error_log("Failed to save to database: " . $e->getMessage());
            // Don't throw exception, continue with file-based processing
        }
    }
    
    private function initializeJobStatus() {
        $statusFile = QUEUE_DIR . $this->jobId . '_status.json';
        
        $status = [
            'job_id' => $this->jobId,
            'import_type' => $this->importType,
            'status' => 'processing',
            'total' => 0,
            'processed' => 0,
            'errors' => 0,
            'started_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'logs' => []
        ];
        
        // Count total rows
        $queueFile = QUEUE_DIR . $this->jobId . '.json';
        $queueData = json_decode(file_get_contents($queueFile), true);
        if (is_array($queueData) && isset($queueData['batches']) && is_array($queueData['batches'])) {
            foreach ($queueData['batches'] as $batch) {
                $status['total'] += count($batch['rows']);
            }
        }
        
        file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));
        
        // Create log file
        $logFile = LOG_DIR . $this->jobId . '.log';
        file_put_contents($logFile, "Import job started: " . date('Y-m-d H:i:s') . "\n");
    }
    
    private function startBackgroundProcessing() {
        // Execute queue worker in background
        $command = "php " . __DIR__ . "/queue_worker.php " . escapeshellarg($this->jobId) . " > /dev/null 2>&1 &";
        exec($command);
    }
    
    private function flattenJsonKeys($item, $prefix = '') {
        $keys = [];
        foreach ($item as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value) && !empty($value) && !isset($value[0])) {
                // Nested object
                $keys = array_merge($keys, $this->flattenJsonKeys($value, $newKey));
            } else {
                $keys[] = $newKey;
            }
        }
        return $keys;
    }
    
    private function generateVendorProductCsv($csvFile) {
        // Increase memory limit for large Excel files
        ini_set('memory_limit', '2G');
        set_time_limit(600); // 10 minutes
        
        // Set POST data for create_product.php
        $_POST['vendor'] = $this->vendorName; // Already lowercase from form
        $_POST['temp_file'] = $csvFile;
        $_POST['vendorCategories'] = array_keys($this->categoryMapping);
        $_POST['shopware_category'] = array_values($this->categoryMapping);
        
        // Save import details to database
        $this->saveImportDetails($csvFile);
        
        // Log for debugging
        error_log("Vendor CSV Generation Started");
        error_log("Vendor: " . $_POST['vendor']);
        error_log("File: " . $csvFile);
        error_log("File exists: " . (file_exists($csvFile) ? 'Yes' : 'No'));
        
        // Define constant to prevent inline UI in create_product.php
        define('IMPORT_PROCESSOR_MODE', true);
        
        // Capture output from create_product.php using output buffering
        ob_start();
        ob_implicit_flush(false); // Ensure no implicit flushing
        try {
            include __DIR__ . '/create_product.php';
        } catch (\Exception $e) {
            ob_end_clean();
            error_log("Exception in create_product.php: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
        $output = ob_get_clean();
        
        error_log("create_product.php output length: " . strlen($output));
        error_log("create_product.php output: " . trim($output));
        
        // Extract CSV file path - it should be echoed as a file path
        $output = trim($output);
        
        // CRITICAL: Check if output contains BOM or invisible characters
        if (!empty($output) && strlen($output) > 0) {
            // Remove any BOM
            if (substr($output, 0, 3) === pack('CCC', 0xef, 0xbb, 0xbf)) {
                $output = substr($output, 3);
            }
            $output = trim($output);
        }
        
        // Check for error messages (lines starting with ❌ or containing "Error:")
        if (!empty($output) && (strpos($output, '❌') === 0 || strpos($output, 'Error:') !== false || preg_match('/^[A-Za-z]+:\s/', $output))) {
            error_log("Error detected in output: " . substr($output, 0, 200));
            return ['success' => false, 'message' => 'Error: ' . substr($output, 0, 300)];
        }
        
        // Check if output is a file path (contains csv-imports/product/)
        if (!empty($output) && strpos($output, 'csv-imports/product/') !== false) {
            // If it's a relative path like "csv-imports/product/...", convert to full path
            if (strpos($output, '/var/www') === false && file_exists(__DIR__ . '/' . $output)) {
                $vendorCsvPath = __DIR__ . '/' . $output;
            } elseif (file_exists($output)) {
                // Already a full path
                $vendorCsvPath = $output;
            } else {
                // Try to extract the filename and look for it
                if (preg_match('/csv-imports\/product\/[^\s<>"\']+\.csv/', $output, $matches)) {
                    $vendorCsvPath = __DIR__ . '/' . $matches[0];
                } else {
                    error_log("Could not determine CSV path from: " . $output);
                    return ['success' => false, 'message' => 'Error: Could not generate vendor CSV. ' . substr($output, 0, 200)];
                }
            }
            
            if (file_exists($vendorCsvPath)) {
                error_log("CSV file found: " . $vendorCsvPath);
                return $vendorCsvPath;
            } else {
                error_log("CSV file not found at path: " . $vendorCsvPath);
                return ['success' => false, 'message' => 'Error: CSV file was not created at expected location.'];
            }
        }
        
        error_log("No valid CSV path found in output: " . substr($output, 0, 200));
        return ['success' => false, 'message' => 'Error: Failed to generate vendor CSV file. Response: ' . substr($output, 0, 200)];
    }
    
    private function saveImportDetails($uploadedFile) {
        try {
            $sql = "INSERT INTO vendor_import_tracking 
                    (job_id, vendor_name, import_type, uploaded_file, original_filename, 
                     file_size, category_mappings, created_at, status) 
                    VALUES 
                    (:job_id, :vendor, :import_type, :uploaded_file, :original_filename, 
                     :file_size, :mappings, NOW(), 'processing')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':job_id' => $this->jobId,
                ':vendor' => $this->vendorName,
                ':import_type' => $this->importType,
                ':uploaded_file' => $uploadedFile,
                ':original_filename' => $_FILES['csv_file']['name'] ?? basename($uploadedFile),
                ':file_size' => file_exists($uploadedFile) ? filesize($uploadedFile) : 0,
                ':mappings' => json_encode($this->categoryMapping)
            ]);
            
            error_log("Import details saved to database for job: " . $this->jobId);
        } catch (\Exception $e) {
            error_log("Failed to save import details: " . $e->getMessage());
        }
    }
    
    private function updateImportTracking($generatedCsv) {
        try {
            $status = (is_string($generatedCsv) && file_exists($generatedCsv)) ? 'completed' : 'failed';
            $csvPath = (is_string($generatedCsv) && file_exists($generatedCsv)) ? $generatedCsv : null;
            
            $sql = "UPDATE vendor_import_tracking 
                    SET generated_csv = :csv_path,
                        status = :status,
                        completed_at = NOW()
                    WHERE job_id = :job_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':csv_path' => $csvPath,
                ':status' => $status,
                ':job_id' => $this->jobId
            ]);
            
            error_log("Import tracking updated for job: " . $this->jobId . " - Status: " . $status);
        } catch (\Exception $e) {
            error_log("Failed to update import tracking: " . $e->getMessage());
        }
    }
    
    private function flattenJsonValues($item, $headers) {
        $values = [];
        foreach ($headers as $header) {
            $keys = explode('.', $header);
            $value = $item;
            foreach ($keys as $key) {
                if (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    $value = '';
                    break;
                }
            }
            // Convert arrays to JSON string
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $values[] = $value;
        }
        return $values;
    }
    
    private function generateTierPriceCsv($xlsFile) {
        // Set POST data for create_tierproduct_price.php
        $_POST['vendor'] = $this->vendorName;
        $_FILES['csv_file'] = [
            'tmp_name' => $xlsFile,
            'name' => basename($xlsFile),
            'error' => UPLOAD_ERR_OK
        ];
        
        // Save import details to database
        $this->saveImportDetails($xlsFile);
        
        // Log for debugging
        error_log("Tier Price CSV Generation Started");
        error_log("Vendor: " . $this->vendorName);
        error_log("File: " . $xlsFile);
        error_log("File exists: " . (file_exists($xlsFile) ? 'Yes' : 'No'));
        file_put_contents("/tmp/import_debug.log", "generateTierPriceCsv START: vendor={$this->vendorName}, file={$xlsFile}\n", FILE_APPEND);
        
        // Define constant to prevent inline UI in create_tierproduct_price.php
        define('IMPORT_PROCESSOR_MODE', true);
        
        // Create global variable for CSV path
        $GLOBALS['TIER_PRICE_CSV_FILE'] = null;
        
        // Capture output from create_tierproduct_price.php
        ob_start();
        include __DIR__ . '/create_tierproduct_price.php';
        $output = ob_get_clean();
        
        error_log("create_tierproduct_price.php output length: " . strlen($output));
        error_log("Output preview: " . substr($output, 0, 500));
        file_put_contents("/tmp/import_debug.log", "generateTierPriceCsv output length: " . strlen($output) . "\n", FILE_APPEND);
        file_put_contents("/tmp/import_debug.log", "generateTierPriceCsv output preview: " . substr($output, 0, 200) . "\n", FILE_APPEND);
        
        // Check if CSV file path was set by create_tierproduct_price.php
        if (!empty($GLOBALS['TIER_PRICE_CSV_FILE']) && file_exists($GLOBALS['TIER_PRICE_CSV_FILE'])) {
            file_put_contents("/tmp/import_debug.log", "CSV file found in GLOBALS: " . $GLOBALS['TIER_PRICE_CSV_FILE'] . "\n", FILE_APPEND);
            error_log("Tier price CSV found: " . $GLOBALS['TIER_PRICE_CSV_FILE']);
            return $GLOBALS['TIER_PRICE_CSV_FILE'];
        }
        
        // Try to parse JSON response first
        $jsonData = json_decode($output, true);
        file_put_contents("/tmp/import_debug.log", "JSON decode: " . ($jsonData ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        
        if ($jsonData && isset($jsonData['file_path'])) {
            file_put_contents("/tmp/import_debug.log", "JSON file_path: " . $jsonData['file_path'] . " (exists: " . (file_exists($jsonData['file_path']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
            if (file_exists($jsonData['file_path'])) {
                error_log("Tier price CSV found from JSON: " . $jsonData['file_path']);
                file_put_contents("/tmp/import_debug.log", "Returning file_path from JSON: " . $jsonData['file_path'] . "\n", FILE_APPEND);
                return $jsonData['file_path'];
            }
        }
        
        // Fallback: Extract CSV file path from output if generated
        $csvPattern = '/csv-imports\/tierprice\/[^"\'<>\s]+\.csv/';
        if (preg_match($csvPattern, $output, $matches)) {
            $tierCsvPath = __DIR__ . '/' . $matches[0];
            if (file_exists($tierCsvPath)) {
                error_log("Tier price CSV found from pattern: " . $tierCsvPath);
                return $tierCsvPath;
            }
        }
        
        error_log("No tier price CSV file found in output");
        return null;
    }
    
    private function generateProductMediaCsv($xlsFile) {
        file_put_contents("/tmp/import_debug.log", "  generateProductMediaCsv START\n", FILE_APPEND);
        
        // Set POST data for create_product_media.php
        $_POST['vendor'] = $this->vendorName;
        $_FILES['csv_file'] = [
            'tmp_name' => $xlsFile,
            'name' => basename($xlsFile),
            'error' => UPLOAD_ERR_OK
        ];
        
        file_put_contents("/tmp/import_debug.log", "  POST vendor set: " . $_POST['vendor'] . "\n", FILE_APPEND);
        
        // Log for debugging
        error_log("Product Media CSV Generation Started");
        file_put_contents("/tmp/import_debug.log", "  Vendor: " . $this->vendorName . "\n", FILE_APPEND);
        file_put_contents("/tmp/import_debug.log", "  File: " . $xlsFile . "\n", FILE_APPEND);
        file_put_contents("/tmp/import_debug.log", "  File exists: " . (file_exists($xlsFile) ? 'yes' : 'NO') . "\n", FILE_APPEND);
        
        // Define constant to prevent inline UI in create_product_media.php
        define('IMPORT_PROCESSOR_MODE', true);
        $GLOBALS['MEDIA_JOB_ID'] = $this->jobId;
        
        // Capture output from create_product_media.php
        ob_start();
        file_put_contents("/tmp/import_debug.log", "  Including create_product_media.php...\n", FILE_APPEND);
        
        try {
            include __DIR__ . '/create_product_media.php';
        } catch (\Exception $e) {
            file_put_contents("/tmp/import_debug.log", "  EXCEPTION in create_product_media.php: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        $output = ob_get_clean();
        
        file_put_contents("/tmp/import_debug.log", "  Output length: " . strlen($output) . "\n", FILE_APPEND);
        error_log("create_product_media.php output length: " . strlen($output));
        
        // Parse JSON response
        $jsonData = json_decode($output, true);
        file_put_contents("/tmp/import_debug.log", "  JSON decode successful: " . ($jsonData ? 'yes' : 'NO') . "\n", FILE_APPEND);
        
        if ($jsonData && isset($jsonData['file_path']) && file_exists($jsonData['file_path'])) {
            file_put_contents("/tmp/import_debug.log", "  CSV found at: " . $jsonData['file_path'] . "\n", FILE_APPEND);
            error_log("Product media CSV found: " . $jsonData['file_path']);
            return $jsonData['file_path'];
        }
        
        file_put_contents("/tmp/import_debug.log", "  No product media CSV found in output\n", FILE_APPEND);
        error_log("No product media CSV file found in output");
        return null;
    }
    
    private function createTierPriceQueue($csvFile) {
        // Read CSV to count rows
        $totalRows = 0;
        if (($handle = fopen($csvFile, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                $totalRows++;
            }
            fclose($handle);
            $totalRows--; // Exclude header
        }
        
        // Create queue data for tier price processing
        $queueData = [
            'job_id' => $this->jobId,
            'import_type' => 'tierprice',
            'vendor_name' => $this->vendorName,
            'original_file' => $csvFile,
            'total_rows' => $totalRows,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Save queue file
        $queueFile = QUEUE_DIR . $this->jobId . '.json';
        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));
        
        // Create status file
        $statusData = [
            'job_id' => $this->jobId,
            'import_type' => 'tierprice',
            'vendor_name' => $this->vendorName,
            'status' => 'pending',
            'total' => $totalRows,
            'processed' => 0,
            'errors' => 0,
            'started_at' => date('Y-m-d H:i:s')
        ];
        
        $statusFile = QUEUE_DIR . $this->jobId . '_status.json';
        file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
        
        return $queueData;
    }
    
    private function createImageQueue($csvFile) {
        // Read CSV to count rows
        $totalRows = 0;
        if (($handle = fopen($csvFile, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                $totalRows++;
            }
            fclose($handle);
            $totalRows--; // Exclude header
        }
        
        // Create queue data for image processing
        $queueData = [
            'job_id' => $this->jobId,
            'import_type' => 'images',
            'vendor_name' => $this->vendorName,
            'original_file' => $csvFile,
            'total_rows' => $totalRows,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Save queue file
        $queueFile = QUEUE_DIR . $this->jobId . '.json';
        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));
        
        // Create status file
        $statusData = [
            'job_id' => $this->jobId,
            'import_type' => 'images',
            'vendor_name' => $this->vendorName,
            'status' => 'pending',
            'total' => $totalRows,
            'processed' => 0,
            'errors' => 0,
            'started_at' => date('Y-m-d H:i:s')
        ];
        
        $statusFile = QUEUE_DIR . $this->jobId . '_status.json';
        file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
        
        return $queueData;
    }
    
    private function saveTierPriceToDatabase($originalFileName, $csvFile) {
        try {
            // Validate CSV file exists
            if (!file_exists($csvFile)) {
                file_put_contents("/tmp/import_debug.log", "ERROR saveTierPriceToDatabase: File does not exist: " . $csvFile . "\n", FILE_APPEND);
                error_log("Tier price job failed: CSV file does not exist: " . $csvFile);
                return false;
            }
            
            // Count rows in generated CSV
            $totalRows = 0;
            if (($handle = fopen($csvFile, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    $totalRows++;
                }
                fclose($handle);
                $totalRows--; // Exclude header
            }
            
            file_put_contents("/tmp/import_debug.log", "saveTierPriceToDatabase: Saving job_id=" . $this->jobId . ", rows=" . $totalRows . ", file=" . $csvFile . "\n", FILE_APPEND);
            
            $sql = "INSERT INTO vendor_import_jobs 
                    (job_id, vendor_name, import_type, file_name, file_path, 
                     total_rows, processed_rows, error_rows, status, created_at) 
                    VALUES 
                    (:job_id, :vendor, :import_type, :file_name, :file_path, 
                     :total_rows, 0, 0, 'pending', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':job_id' => $this->jobId,
                ':vendor' => $this->vendorName,
                ':import_type' => 'tierprice',
                ':file_name' => $originalFileName,
                ':file_path' => $csvFile,
                ':total_rows' => $totalRows
            ]);
            
            file_put_contents("/tmp/import_debug.log", "saveTierPriceToDatabase: Insert result=" . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            error_log("Tier price job saved to database: " . $this->jobId . " (rows: " . $totalRows . ")");
            return true;
        } catch (\Exception $e) {
            file_put_contents("/tmp/import_debug.log", "ERROR saveTierPriceToDatabase: " . $e->getMessage() . "\n", FILE_APPEND);
            error_log("Failed to save tier price job: " . $e->getMessage());
            return false;
        }
    }
    
    private function saveImageImportToDatabase($originalFileName, $csvFile) {
        try {
            // Count rows in generated CSV
            $totalRows = 0;
            if (($handle = fopen($csvFile, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    $totalRows++;
                }
                fclose($handle);
                $totalRows--; // Exclude header
            }
            
            $sql = "INSERT INTO vendor_import_jobs 
                    (job_id, vendor_name, import_type, file_name, file_path, 
                     total_rows, processed_rows, error_rows, status, created_at) 
                    VALUES 
                    (:job_id, :vendor, :import_type, :file_name, :file_path, 
                     :total_rows, 0, 0, 'pending', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':job_id' => $this->jobId,
                ':vendor' => $this->vendorName,
                ':import_type' => 'images',
                ':file_name' => $originalFileName,
                ':file_path' => $csvFile,
                ':total_rows' => $totalRows
            ]);
            
            error_log("Image import job saved to database: " . $this->jobId);
            
            // No longer need to trigger background worker - processing happens synchronously
            
        } catch (\Exception $e) {
            error_log("Failed to save image import job: " . $e->getMessage());
        }
    }

    private function startImageWorker(string $jobId): bool {
        error_log("=== startImageWorker called for job: $jobId ===");
        
        $logFile = sys_get_temp_dir() . '/worker_' . $jobId . '.log';
        $pidFile = sys_get_temp_dir() . '/worker_' . $jobId . '.pid';
        
        // Use process_csv_worker.php for image imports (it handles images via processImageImport function)
        $cmd = 'nohup ' . PHP_BINARY . ' ' . __DIR__ . '/process_csv_worker.php job_id=' . escapeshellarg($jobId) . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $! > ' . escapeshellarg($pidFile);
        
        error_log("Command: " . $cmd);
        
        $started = false;

        // Try shell_exec first (most reliable for background processes)
        if (function_exists('shell_exec')) {
            try {
                @shell_exec($cmd);
                sleep(1); // Wait a moment for process to start
                $started = file_exists($pidFile) || file_exists($logFile);
                error_log("Image worker started with shell_exec (pidFile exists=" . (file_exists($pidFile) ? 'yes' : 'no') . ", logFile exists=" . (file_exists($logFile) ? 'yes' : 'no') . ")");
            } catch (\Exception $e) {
                error_log("shell_exec failed: " . $e->getMessage());
            }
        }

        // Fallback to exec
        if (!$started && function_exists('exec')) {
            try {
                @exec($cmd, $output, $code);
                sleep(1);
                $started = file_exists($pidFile) || file_exists($logFile);
                error_log("Image worker started with exec (code=$code, pidFile exists=" . (file_exists($pidFile) ? 'yes' : 'no') . ")");
            } catch (\Exception $e) {
                error_log("exec failed: " . $e->getMessage());
            }
        }

        // If background process failed, process immediately in shutdown
        if (!$started) {
            error_log("Background worker failed to start, using shutdown function");
            register_shutdown_function(function () use ($jobId) {
                error_log("Processing images via shutdown function for job: $jobId");
                try {
                    $_GET['job_id'] = $jobId;
                    include __DIR__ . '/process_csv_worker.php';
                } catch (\Exception $e) {
                    error_log("Error in shutdown image worker: " . $e->getMessage());
                }
            });
        } else {
            error_log("Background worker started successfully for job: $jobId");
        }

        return true;
    }

    private function fireAndForgetWorkerHttp(string $jobId): bool {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $port = $isHttps ? 443 : 80;
        $transport = $isHttps ? 'ssl://' : '';
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/import_processor.php'), '/');
        $url = $path . '/process_csv_worker.php?job_id=' . rawurlencode($jobId);

        $fp = @fsockopen($transport . $host, $port, $errno, $errstr, 0.5);
        if (!$fp) {
            return false;
        }

        $req = "GET $url HTTP/1.1\r\n";
        $req .= "Host: $host\r\n";
        $req .= "Connection: Close\r\n\r\n";
        fwrite($fp, $req);
        fclose($fp);

        return true;
    }

    private function queueJobForProcessing(string $jobId): void {
        $queueFile = sys_get_temp_dir() . '/import_queue_' . $jobId . '.json';
        $queueData = [
            'job_id' => $jobId,
            'queued_at' => date('Y-m-d H:i:s'),
            'type' => 'image_import'
        ];
        @file_put_contents($queueFile, json_encode($queueData));
        error_log("Job queued for processing: $jobId");
    }

    private function processImagesDirectly($uploadedFile) {
        // Process images directly from uploaded file (XLS/CSV)
        error_log("=== processImagesDirectly START for job: " . $this->jobId . " ===");
        error_log("Uploaded File: $uploadedFile | Exists: " . (file_exists($uploadedFile) ? 'yes' : 'NO'));
        
        $result = [
            'success' => false,
            'error' => '',
            'products_processed' => 0,
            'images_created' => 0
        ];
        
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            // Check if file exists
            if (!file_exists($uploadedFile)) {
                $result['error'] = 'Uploaded file not found: ' . $uploadedFile;
                error_log("ERROR: " . $result['error']);
                return $result;
            }
            
            // Load spreadsheet (works for XLS, XLSX, CSV)
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile);
            $sheet = $spreadsheet->getActiveSheet();
            
            error_log("Spreadsheet loaded successfully");
            
            $container = $this->getShopwareContainer();
            error_log("Container obtained: " . ($container ? 'yes' : 'FAILED'));
            
            if (!$container) {
                $result['error'] = 'Cannot get Shopware container';
                error_log("ERROR: " . $result['error']);
                return $result;
            }

            // Get PDO connection from Doctrine
            $dbConnection = $container->get(\Doctrine\DBAL\Connection::class);
            $pdo = $dbConnection->getNativeConnection();
            
            $mediaRepo = $container->get('media.repository');
            $fileSaver = $container->get(\Shopware\Core\Content\Media\File\FileSaver::class);
            $context = \Shopware\Core\Framework\Context::createDefaultContext();

            // Get product media folder
            $folderId = $pdo->query("
                SELECT LOWER(HEX(mf.id)) 
                FROM media_folder mf
                JOIN media_default_folder mdf ON mf.default_folder_id = mdf.id
                WHERE mdf.entity = 'product'
                LIMIT 1
            ")->fetchColumn();

            if (!$folderId) {
                $result['error'] = 'Product media folder not found';
                error_log("ERROR: " . $result['error']);
                return $result;
            }
            
            error_log("Media folder found: " . $folderId);

            // Process each row in the spreadsheet
            $rowCount = 0;
            $processed = 0;
            $created = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowCount++;
                
                // Skip header row (row 1)
                if ($rowCount === 1) {
                    continue;
                }

                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }

                // Expected columns: 0=productNumber, 7=coverImage, 8=galleryImages
                $productNumber = trim($rowData[0] ?? '');
                $coverUrl = trim($rowData[7] ?? '');
                $galleryUrls = trim($rowData[8] ?? '');

                if (!$productNumber) {
                    continue;
                }
                
                error_log("Processing product: " . $productNumber);

                // Get product ID
                $stmt = $pdo->prepare("SELECT HEX(id) pid FROM product WHERE product_number = ?");
                $stmt->execute([$productNumber]);
                $product = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$product) {
                    error_log("  Product not found in database: " . $productNumber);
                    continue;
                }

                $productId = $product['pid'];
                error_log("  Product found: " . $productId);

                // Delete old media for this product first
                $this->cleanupProductMedia($productId, $productNumber, $pdo);

                $mediaItems = [];
                $coverProductMediaId = null;

                // Process cover image
                if ($coverUrl) {
                    $imagePath = $this->resolveImagePath($coverUrl);
                    error_log("  Cover URL: " . $coverUrl . " -> " . $imagePath);
                    
                    if ($imagePath && file_exists($imagePath)) {
                        try {
                            $mediaId = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
                            $mediaRepo->create([[
                                'id' => $mediaId,
                                'mediaFolderId' => $folderId,
                                'private' => false,
                                'name' => pathinfo($imagePath, \PATHINFO_FILENAME)
                            ]], $context);

                            $file = new \Shopware\Core\Content\Media\File\MediaFile(
                                $imagePath,
                                mime_content_type($imagePath),
                                pathinfo($imagePath, \PATHINFO_EXTENSION),
                                filesize($imagePath)
                            );

                            $destination = pathinfo($imagePath, \PATHINFO_FILENAME) . '-' . strtolower($productNumber) . '-cover';
                            $fileSaver->persistFileToMedia($file, $destination, $mediaId, $context);

                            $productMediaId = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
                            $mediaItems[] = [
                                'id' => $productMediaId,
                                'mediaId' => $mediaId,
                                'position' => 0
                            ];
                            $coverProductMediaId = $productMediaId;
                            $created++;
                            error_log("  ✓ Cover image uploaded: " . $destination);
                        } catch (\Exception $e) {
                            error_log("  ERROR uploading cover image: " . $e->getMessage());
                        }
                    } else {
                        error_log("  Cover file not found: " . $imagePath);
                    }
                }

                // Process gallery images
                if ($galleryUrls) {
                    $urls = explode('|', $galleryUrls);
                    foreach ($urls as $pos => $url) {
                        $url = trim($url);
                        if (!$url) continue;
                        
                        $imagePath = $this->resolveImagePath($url);
                        error_log("  Gallery URL: " . $url . " -> " . $imagePath);
                        
                        if ($imagePath && file_exists($imagePath)) {
                            try {
                                $mediaId = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
                                $mediaRepo->create([[
                                    'id' => $mediaId,
                                    'mediaFolderId' => $folderId,
                                    'private' => false,
                                    'name' => pathinfo($imagePath, \PATHINFO_FILENAME)
                                ]], $context);

                                $file = new \Shopware\Core\Content\Media\File\MediaFile(
                                    $imagePath,
                                    mime_content_type($imagePath),
                                    pathinfo($imagePath, \PATHINFO_EXTENSION),
                                    filesize($imagePath)
                                );

                                $destination = pathinfo($imagePath, \PATHINFO_FILENAME) . '-' . strtolower($productNumber) . '-g' . ($pos + 1);
                                $fileSaver->persistFileToMedia($file, $destination, $mediaId, $context);

                                $mediaItems[] = [
                                    'id' => \Shopware\Core\Framework\Uuid\Uuid::randomHex(),
                                    'mediaId' => $mediaId,
                                    'position' => $pos + 1
                                ];
                                $created++;
                                error_log("  ✓ Gallery image uploaded: " . $destination);
                            } catch (\Exception $e) {
                                error_log("  ERROR uploading gallery image: " . $e->getMessage());
                            }
                        } else {
                            error_log("  Gallery file not found: " . $imagePath);
                        }
                    }
                }

                // Link media to product
                if (!empty($mediaItems)) {
                    try {
                        foreach ($mediaItems as $item) {
                            $stmt = $pdo->prepare(
                                "INSERT INTO product_media (id, version_id, position, product_id, product_version_id, media_id, created_at)
                                 VALUES (UNHEX(?), UNHEX(?), ?, UNHEX(?), UNHEX(?), UNHEX(?), NOW(3))"
                            );
                            $stmt->execute([
                                $item['id'],
                                \Shopware\Core\Defaults::LIVE_VERSION,
                                (int)$item['position'],
                                $productId,
                                \Shopware\Core\Defaults::LIVE_VERSION,
                                $item['mediaId']
                            ]);
                        }

                        if ($coverProductMediaId) {
                            $stmt = $pdo->prepare("UPDATE product SET product_media_id = UNHEX(?) WHERE id = UNHEX(?)");
                            $stmt->execute([$coverProductMediaId, $productId]);
                            error_log("  ✓ Product cover set");
                        }
                        
                        $processed++;
                    } catch (\Exception $e) {
                        error_log("  ERROR linking media to product: " . $e->getMessage());
                    }
                }
            }

            // Update job status to completed
            $stmt = $pdo->prepare("UPDATE vendor_import_jobs SET processed_rows = ?, error_rows = 0, status = 'completed', completed_at = NOW() WHERE job_id = ?");
            $stmt->execute([$processed, $this->jobId]);

            error_log("=== processImagesDirectly COMPLETE ===");
            error_log("Direct image processing complete: $processed products, $created images");
            
            $result['success'] = true;
            $result['products_processed'] = $processed;
            $result['images_created'] = $created;
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("=== processImagesDirectly EXCEPTION ===");
            error_log("Direct image processing error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $result['error'] = $e->getMessage();
            return $result;
        }
    }
    
    private function resolveImagePath($url) {
        // Handle URLs like http://shopware678.local/my-imports/Hakro/0105/m_model_p_0105_001_01.jpg
        // Or direct file paths
        
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            // Convert URL to file path
            // Extract the path part after domain
            $parsed = parse_url($url);
            if (isset($parsed['path'])) {
                $filePath = __DIR__ . $parsed['path'];
                if (file_exists($filePath)) {
                    return $filePath;
                }
            }
        } else if (strpos($url, '/') === 0) {
            // Already a file path
            $filePath = __DIR__ . $url;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        
        return null;
    }

    private function cleanupProductMedia($productId, $productNumber, $pdo): void {
        // Get all media for this product
        $sql = "SELECT HEX(m.id) as media_id, m.path FROM media m JOIN product_media pm ON pm.media_id = m.id WHERE pm.product_id = UNHEX(?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        $mediaFiles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Delete physical files
        foreach ($mediaFiles as $media) {
            $filePath = __DIR__ . '/' . $media['path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Reset product cover and delete media records
        $pdo->prepare("UPDATE product SET product_media_id = NULL WHERE id = UNHEX(?)")->execute([$productId]);
        $pdo->prepare("DELETE FROM product_media WHERE product_id = UNHEX(?)")->execute([$productId]);

        if (count($mediaFiles) > 0) {
            $mediaIds = array_map(function($m) { return $m['media_id']; }, $mediaFiles);
            $placeholders = implode(',', array_fill(0, count($mediaIds), 'UNHEX(?)'));
            $pdo->prepare("DELETE FROM media WHERE id IN ($placeholders)")->execute($mediaIds);
        }
    }

    private function triggerImageWorkerHttp(string $jobId): void {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url = $scheme . '://' . $host . '/trigger_image_import.php?job_id=' . urlencode($jobId);
        
        error_log("Triggering image worker at: $url");
        
        // Fire and forget HTTP request
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_exec($ch);
            curl_close($ch);
        } else {
            // Fallback to file_get_contents with timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => 1,
                    'ignore_errors' => true
                ]
            ]);
            @file_get_contents($url, false, $context);
        }
        
        error_log("Image worker triggered successfully");
    }

    private function processImagesDirectlyInline($csvPath) {
        error_log("=== processImagesDirectlyInline START ===");
        
        // Set the job_id for the worker
        $_GET['job_id'] = $this->jobId;
        
        // Buffer output to prevent early response
        ob_start();
        
        // Include and execute the worker immediately
        try {
            require __DIR__ . '/process_csv_worker.php';
            error_log("Image worker executed successfully");
        } catch (\Exception $e) {
            error_log("Error executing image worker: " . $e->getMessage());
        }
        
        // Capture and discard worker output
        $workerOutput = ob_get_clean();
        error_log("Worker output captured: " . strlen($workerOutput) . " bytes");
    }

    private function getShopwareContainer() {
        try {
            $classLoader = require __DIR__ . '/../vendor/autoload.php';
            $dotenv = new \Symfony\Component\Dotenv\Dotenv();
            $dotenv->loadEnv(__DIR__ . '/../.env.local');

            $appEnv = $_ENV['APP_ENV'] ?? 'dev';
            $debug = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

            $kernel = \Shopware\Core\Framework\Adapter\Kernel\KernelFactory::create(
                environment: $appEnv,
                debug: $debug,
                classLoader: $classLoader
            );
            $kernel->boot();
            return $kernel->getContainer();
        } catch (\Exception $e) {
            error_log("Failed to get container: " . $e->getMessage());
            return null;
        }
    }
}

// Process request
$processor = new ImportProcessor();
$result = $processor->processRequest();
echo json_encode($result);
