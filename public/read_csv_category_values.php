<?php
/**
 * CSV Category Values Reader - Extract unique category values from CSV
 */

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

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

function rowHasCategoryHeader(array $row): bool
{
    foreach ($row as $header) {
        $headerTrimmed = trim((string)$header);
        if ($headerTrimmed === '') {
            continue;
        }
        $headerLower = strtolower($headerTrimmed);

        if ($headerLower === 'category' ||
            $headerLower === 'categories' ||
            $headerLower === 'category shop' ||
            $headerLower === 'artikel shop gruppe' ||
            $headerLower === 'article shop group' ||
            $headerLower === 'kategorie shop' ||
            $headerTrimmed === 'Article Shop Group' ||
            $headerTrimmed === 'Category Shop' ||
            $headerTrimmed === 'Kategorie Shop') {
            return true;
        }

        if (strpos($headerLower, 'categor') !== false ||
            strpos($headerLower, 'shop group') !== false ||
            strpos($headerLower, 'kategorie') !== false) {
            return true;
        }
    }

    return false;
}

function findHeaderRowIndex(array $rows, int $maxScanRows = 5): int
{
    $limit = min($maxScanRows, count($rows));
    for ($i = 0; $i < $limit; $i++) {
        if (rowHasCategoryHeader($rows[$i])) {
            return $i;
        }
    }
    return -1;
}

function detectCsvDelimiter(array $lines): string
{
    $delimiters = [',', ';', "\t"];
    $bestDelimiter = ',';
    $bestScore = -1;

    $sampleLines = array_slice($lines, 0, 5);
    foreach ($delimiters as $delimiter) {
        $score = 0;
        foreach ($sampleLines as $line) {
            $score += max(1, count(str_getcsv($line, $delimiter)));
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestDelimiter = $delimiter;
        }
    }

    return $bestDelimiter;
}

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
    // Handle different file types
    $headers = [];
    $rows = [];
    
    // For JSON files (newwave), extract categories from embedded data structure
    if ($extension === 'json') {
        try {
            $jsonContent = file_get_contents($file['tmp_name']);
            $jsonData = json_decode($jsonContent, true);
            
            $uniqueCategories = [];
            
            // Extract categories from productCategory nested structure
            if (!empty($jsonData['result']) && is_array($jsonData['result'])) {
                foreach ($jsonData['result'] as $product) {
                    if (!is_array($product)) continue;
                    
                    // Extract category from nested structure
                    if (!empty($product['productCategory']) && is_array($product['productCategory'])) {
                        $firstCategory = $product['productCategory'][0];
                        $categoryName = '';
                        
                        // Try English translation first
                        if (isset($firstCategory['translation']['en'])) {
                            $categoryName = trim((string)$firstCategory['translation']['en']);
                        } 
                        // Fallback to key field
                        elseif (isset($firstCategory['key'])) {
                            $categoryName = trim((string)$firstCategory['key']);
                        }
                        // Fallback to German translation
                        elseif (isset($firstCategory['translation']['de'])) {
                            $categoryName = trim((string)$firstCategory['translation']['de']);
                        }
                        // Fallback to any available translation
                        elseif (is_array($firstCategory['translation'])) {
                            $translations = array_filter($firstCategory['translation'], function($v) {
                                return is_string($v) && !empty($v);
                            });
                            if (!empty($translations)) {
                                $categoryName = trim((string)current($translations));
                            }
                        }
                        
                        // Add to unique categories if not empty and not already added
                        if (!empty($categoryName) && !in_array($categoryName, $uniqueCategories)) {
                            $uniqueCategories[] = $categoryName;
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'categories' => $uniqueCategories,
                'categoryColumn' => 'productCategory',
                'message' => 'Extracted ' . count($uniqueCategories) . ' unique categories from JSON file.'
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error reading JSON file: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    if (in_array($extension, ['xls', 'xlsx'])) {
        // Process Excel files
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Read all rows
            $allRows = [];
            $maxRows = 100; // Limit to first 100 rows for performance
            $rowCount = 0;
            
            foreach ($sheet->getRowIterator() as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                
                // Skip empty rows
                if (!empty(array_filter($rowData))) {
                    $allRows[] = $rowData;
                    $rowCount++;
                    if ($rowCount >= $maxRows) break;
                }
            }
            
            if (empty($allRows)) {
                throw new Exception('Excel file is empty or has no data');
            }
            
            // Find header row
            $headerRowIndex = -1;
            for ($i = 0; $i < min(5, count($allRows)); $i++) {
                if (rowHasCategoryHeader($allRows[$i])) {
                    $headerRowIndex = $i;
                    break;
                }
            }
            
            if ($headerRowIndex === -1) {
                $headers = $allRows[0] ?? [];
            } else {
                $headers = $allRows[$headerRowIndex];
            }
            
            // Find category column
            $categoryColumnIndex = -1;
            foreach ($headers as $index => $header) {
                $headerTrimmed = trim((string)$header);
                $headerLower = strtolower($headerTrimmed);
                
                if ($headerLower === 'category shop' ||
                    $headerLower === 'article shop group' ||
                    $headerTrimmed === 'Article Shop Group' ||
                    $headerTrimmed === 'Category Shop') {
                    $categoryColumnIndex = $index;
                    break;
                }
                
                if (strpos($headerLower, 'categor') !== false || 
                    strpos($headerLower, 'shop group') !== false) {
                    $categoryColumnIndex = $index;
                    break;
                }
            }
            
            if ($categoryColumnIndex === -1) {
                echo json_encode([
                    'success' => true,
                    'categories' => [],
                    'categoryColumn' => null,
                    'message' => 'No category column found.'
                ]);
                exit;
            }
            
            // Collect unique categories
            $uniqueCategories = [];
            $startRow = $headerRowIndex === -1 ? 1 : $headerRowIndex + 1;
            
            for ($i = $startRow; $i < count($allRows); $i++) {
                if (isset($allRows[$i][$categoryColumnIndex])) {
                    $categoryValue = trim((string)$allRows[$i][$categoryColumnIndex]);
                    if (!empty($categoryValue) && !in_array($categoryValue, $uniqueCategories)) {
                        $uniqueCategories[] = $categoryValue;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'categories' => $uniqueCategories,
                'categoryColumn' => $headers[$categoryColumnIndex] ?? 'Unknown'
            ]);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage()
            ]);
            exit;
        }
    } else {
        // Handle CSV files
        $lines = file($file['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new Exception('Cannot open CSV file');
        }

        $delimiter = detectCsvDelimiter($lines);
        $allRows = [];
        $maxRows = 1000;
        foreach ($lines as $line) {
            $allRows[] = str_getcsv($line, $delimiter);
            if (count($allRows) >= ($maxRows + 5)) break;
        }

        if (empty($allRows)) {
            throw new Exception('CSV file is empty or has no headers');
        }

        $headerRowIndex = findHeaderRowIndex($allRows);
        if ($headerRowIndex === -1) {
            $headers = $allRows[0] ?? [];
            $rows = array_slice($allRows, 1, $maxRows);
        } else {
            $headers = $allRows[$headerRowIndex];
            $rows = array_slice($allRows, $headerRowIndex + 1, $maxRows);
        }
    }
    
    if (empty($headers)) {
        throw new Exception('File has no header row');
    }
    
    // Find category column index
    $categoryColumnIndex = -1;
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
            $categoryColumnIndex = $index;
            break;
        }
        
        // Partial matches
        if (strpos($headerLower, 'categor') !== false || 
            strpos($headerLower, 'shop group') !== false ||
            strpos($headerLower, 'category') !== false) {
            $categoryColumnIndex = $index;
            break;
        }
    }
    
    if ($categoryColumnIndex === -1) {
        // For CSV/Excel files without explicit category column, return empty categories
        echo json_encode([
            'success' => true,
            'categories' => [],
            'categoryColumn' => null,
            'message' => 'No category column found. Available columns: ' . implode(', ', $headers)
        ]);
        exit;
    }
    
    // Read all rows and collect unique category values
    $uniqueCategories = [];
    
    foreach ($rows as $row) {
        if (isset($row[$categoryColumnIndex]) && !empty($row[$categoryColumnIndex])) {
            $categoryValue = trim($row[$categoryColumnIndex]);
            if (!empty($categoryValue) && !in_array($categoryValue, $uniqueCategories)) {
                $uniqueCategories[] = $categoryValue;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $uniqueCategories,
        'categoryColumn' => $headers[$categoryColumnIndex]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
