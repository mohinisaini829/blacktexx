<?php
/**
 * Fix Newwave variant names from CSV
 * Reads the generated CSV file and updates product_translation with correct names
 */

require_once 'db_config.php';

$db = Database::getConnection();
$langId = hex2bin('2FBB5FE2E29A4D70AA5854CE7CE3E20B'); // English

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

if (!isset($headerMap['product_number']) || !isset($headerMap['translations.DEFAULT.name'])) {
    echo "❌ Invalid CSV header\n";
    exit(1);
}

$productNumberIdx = $headerMap['product_number'];
$nameIdx = $headerMap['translations.DEFAULT.name'];
$parentIdIdx = $headerMap['parent_id'];

$updated = 0;
$skipped = 0;
$errors = 0;

$rowNum = 1;
while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $rowNum++;
    
    $productNumber = trim($row[$productNumberIdx] ?? '');
    $name = trim($row[$nameIdx] ?? '');
    $parentId = trim($row[$parentIdIdx] ?? '');
    
    // Skip if no parent_id (parent products) or no name
    if (empty($parentId) || empty($name)) {
        $skipped++;
        continue;
    }
    
    // Skip if not a newwave product (not starting with 010177, etc.)
    if (strpos($productNumber, '010177') !== 0 && strpos($productNumber, '010') !== 0) {
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
        
        // Update or insert translation
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM product_translation WHERE product_id = ? AND language_id = ?");
        $checkStmt->execute([$productId, $langId]);
        
        if ($checkStmt->fetchColumn() > 0) {
            // Update
            $updateStmt = $db->prepare("
                UPDATE product_translation 
                SET name = ?
                WHERE product_id = ? AND language_id = ?
            ");
            $updateStmt->execute([$name, $productId, $langId]);
        } else {
            // Insert
            $insertStmt = $db->prepare("
                INSERT INTO product_translation (product_id, product_version_id, language_id, name, created_at)
                VALUES (?, UNHEX('0FA91CE3E96A4BC2BE4BD9CE752C3425'), ?, ?, NOW())
            ");
            $insertStmt->execute([$productId, $langId, $name]);
        }
        
        echo "✓ $productNumber -> $name\n";
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
