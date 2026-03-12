<?php
/**
 * Import variant options from CSV
 * Reads optionIds from CSV and creates product_option entries
 */

require_once 'db_config.php';

$db = Database::getConnection();

// Find the latest newwave CSV
$csvDir = __DIR__ . '/csv-imports/product/';
$csvFiles = glob($csvDir . 'newwave_product_import_*.csv');

if (empty($csvFiles)) {
    echo "❌ No newwave CSV files found\n";
    exit(1);
}

// Get the latest file
usort($csvFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$csvFile = $csvFiles[0];
echo "Using CSV: " . basename($csvFile) . "\n\n";

$handle = fopen($csvFile, 'r');
if (!$handle) {
    echo "❌ Cannot open CSV file\n";
    exit(1);
}

// Read header
$header = fgetcsv($handle, 0, ';');
$headerMap = array_flip($header);

if (!isset($headerMap['product_number']) || !isset($headerMap['optionIds']) || !isset($headerMap['parent_id'])) {
    echo "❌ Invalid CSV header\n";
    exit(1);
}

$productNumberIdx = $headerMap['product_number'];
$optionIdsIdx = $headerMap['optionIds'];
$parentIdIdx = $headerMap['parent_id'];

$updated = 0;
$skipped = 0;
$errors = 0;

$rowNum = 1;
while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $rowNum++;
    
    $productNumber = trim($row[$productNumberIdx] ?? '');
    $optionIds = trim($row[$optionIdsIdx] ?? '');
    $parentId = trim($row[$parentIdIdx] ?? '');
    
    // Skip if no parent_id (parent products) or no optionIds
    if (empty($parentId) || empty($optionIds)) {
        $skipped++;
        continue;
    }
    
    try {
        // Find product by product_number
        $findStmt = $db->prepare("SELECT id FROM product WHERE product_number = ?");
        $findStmt->execute([$productNumber]);
        $product = $findStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $skipped++;
            continue;
        }
        
        $productId = $product['id'];
        
        // Split optionIds (format: "id1|id2|...")
        $optionIdsList = array_filter(array_map('trim', explode('|', $optionIds)));
        
        if (empty($optionIdsList)) {
            $skipped++;
            continue;
        }
        
        // Delete existing product_option entries for this product
        $db->prepare("DELETE FROM product_option WHERE product_id = ?")->execute([$productId]);
        
        // Insert new product_option entries
        $insertStmt = $db->prepare("
            INSERT INTO product_option (product_id, product_version_id, property_group_option_id)
            VALUES (?, UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425'), ?)
        ");
        
        foreach ($optionIdsList as $optionId) {
            $insertStmt->execute([$productId, hex2bin($optionId)]);
        }
        
        echo "✓ $productNumber: Added " . count($optionIdsList) . " options\n";
        $updated++;
        
    } catch (\Exception $e) {
        echo "❌ Error for $productNumber: " . $e->getMessage() . "\n";
        $errors++;
    }
}

fclose($handle);

echo "\n" . str_repeat("=", 60) . "\n";
echo "Summary:\n";
echo "  Updated: $updated\n";
echo "  Skipped: $skipped\n";
echo "  Errors: $errors\n";
echo str_repeat("=", 60) . "\n";

// Clear cache
echo "\nClearing Shopware cache...\n";
shell_exec('php bin/console cache:clear 2>&1');
echo "✓ Cache cleared\n";
?>
