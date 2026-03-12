<?php
/**
 * Update Product Names from CSV
 */

require_once __DIR__ . '/db_config.php';

// Check if CSV file path is provided as argument or find latest
if (isset($argv[1]) && file_exists($argv[1])) {
    $csvFile = $argv[1];
} elseif (isset($_GET['csv_file']) && file_exists($_GET['csv_file'])) {
    $csvFile = $_GET['csv_file'];
} elseif (defined('CSV_FILE_PATH') && file_exists(CSV_FILE_PATH)) {
    $csvFile = CSV_FILE_PATH;
} else {
    // Find latest CSV file in product folder
    $csvDir = __DIR__ . '/csv-imports/product/';
    if (is_dir($csvDir)) {
        $files = glob($csvDir . '*.csv');
        if (!empty($files)) {
            // Sort by modification time, newest first
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $csvFile = $files[0];
        }
    }
}

if (!isset($csvFile) || !file_exists($csvFile)) {
    die("CSV file not found. Please provide CSV path as argument.\n");
}

echo "Using CSV file: " . basename($csvFile) . "\n";

$db = Database::getConnection();
$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b'); // Default language

$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle, 0, ';');

// Find the indices
$productNumberIdx = array_search('product_number', $headers);
$nameIdx = array_search('translations.DEFAULT.name', $headers);

if ($productNumberIdx === false || $nameIdx === false) {
    die("Required columns not found in CSV\n");
}

echo "Found columns - product_number: $productNumberIdx, name: $nameIdx\n\n";

$updated = 0;
$notFound = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $productNumber = $row[$productNumberIdx] ?? '';
    $name = $row[$nameIdx] ?? '';
    
    if (empty($productNumber) || empty($name)) {
        continue;
    }
    
    // Get product ID and version ID
    $stmt = $db->prepare("SELECT LOWER(HEX(id)) as id, LOWER(HEX(version_id)) as version_id FROM product WHERE product_number = ?");
    $stmt->execute([$productNumber]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $notFound++;
        continue;
    }
    
    $productId = hex2bin($product['id']);
    $versionId = hex2bin($product['version_id']);
    
    // Update or insert translation
    try {
        // Check if translation exists
        $check = $db->prepare("SELECT 1 FROM product_translation WHERE product_id = ? AND product_version_id = ? AND language_id = ?");
        $check->execute([$productId, $versionId, $langId]);
        
        if ($check->fetch()) {
            // Update
            $update = $db->prepare("UPDATE product_translation SET name = ? WHERE product_id = ? AND product_version_id = ? AND language_id = ?");
            $update->execute([$name, $productId, $versionId, $langId]);
        } else {
            // Insert
            $insert = $db->prepare("INSERT INTO product_translation (product_id, product_version_id, language_id, name, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insert->execute([$productId, $versionId, $langId, $name]);
        }
        
        echo "✓ Updated: $productNumber → $name\n";
        $updated++;
        
    } catch (Exception $e) {
        echo "✗ Error for $productNumber: " . $e->getMessage() . "\n";
    }
}

fclose($handle);

echo "\n========================================\n";
echo "Summary:\n";
echo "  Updated: $updated\n";
echo "  Not Found: $notFound\n";
echo "========================================\n";
