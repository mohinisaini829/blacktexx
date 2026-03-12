<?php
/**
 * CSV Header Reader - Ajax endpoint to read CSV headers
 */

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if (!isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

// Validate file type
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['csv', 'xls', 'xlsx', 'json'];
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Only CSV, XLS, XLSX, and JSON files are allowed']);
    exit;
}

try {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('Cannot open CSV file');
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    fclose($handle);
    
    if (empty($headers)) {
        throw new Exception('CSV file is empty or has no headers');
    }
    
    // Find category-related columns
    $categoryColumns = [];
    foreach ($headers as $index => $header) {
        $headerTrimmed = trim($header);
        $headerLower = strtolower($headerTrimmed);
        
        // Check for category-related columns (vendor-specific patterns)
        // Exact matches
        if ($headerLower === 'category' ||
            $headerLower === 'categories' ||
            $headerLower === 'category shop' ||
            $headerLower === 'article shop group' ||
            $headerTrimmed === 'Article Shop Group' ||
            $headerTrimmed === 'Category Shop') {
            $categoryColumns[] = [
                'index' => $index,
                'name' => $headerTrimmed
            ];
            continue;
        }
        
        // Partial matches
        if (strpos($headerLower, 'categor') !== false || 
            strpos($headerLower, 'shop group') !== false ||
            strpos($headerLower, 'category') !== false) {
            $categoryColumns[] = [
                'index' => $index,
                'name' => $headerTrimmed
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'headers' => $headers,
        'categoryColumns' => $categoryColumns
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
