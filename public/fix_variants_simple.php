<?php
/**
 * Simple Variant Name Fix - Updates variant names from CSV
 * Format: ParentName - Color - Size
 * No optionIds dependency
 */

require_once 'db_config.php';
$db = Database::getConnection();

$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b'); // English
$liveVersionId = hex2bin('0FA91CE3E96A4BC2BE4BD9CE752C3425');

// CSV path can be passed via $csvPath variable or CLI argument
$csvFile = $csvPath ?? ($_GET['csv'] ?? null);

if (PHP_SAPI === 'cli' && isset($argv[1]) && file_exists($argv[1])) {
    $csvFile = $argv[1];
}

if (!$csvFile || !file_exists($csvFile)) {
    echo "CSV file not found\n";
    exit(1);
}

echo "Processing CSV: $csvFile\n";

// Read CSV
$handle = fopen($csvFile, 'r');
if (!$handle) {
    echo "Cannot open CSV file\n";
    exit(1);
}

$header = fgetcsv($handle, 0, ';');
if (!$header) {
    echo "Invalid CSV - no header\n";
    fclose($handle);
    exit(1);
}

$headerMap = array_flip($header);

// Find required columns
$productNumberCol = $headerMap['product_number'] ?? $headerMap['productNumber'] ?? null;
$nameCol = $headerMap['translations.DEFAULT.name'] ?? $headerMap['name'] ?? null;
$parentIdCol = $headerMap['parent_id'] ?? $headerMap['parentId'] ?? null;

if ($productNumberCol === null || $nameCol === null) {
    echo "CSV missing required columns (product_number, name)\n";
    fclose($handle);
    exit(1);
}

echo "Found columns: product_number=$productNumberCol, name=$nameCol, parent_id=$parentIdCol\n";

$updated = 0;
$skipped = 0;
$errors = 0;

// Cache parent names
$parentNameCache = [];
$getParentNameStmt = $db->prepare("
    SELECT pt.name
    FROM product_translation pt
    WHERE pt.product_id = UNHEX(?) AND pt.language_id = ?
    LIMIT 1
");

$updateStmt = $db->prepare("
    UPDATE product_translation 
    SET name = ? 
    WHERE product_id = (SELECT id FROM product WHERE product_number = ?) 
    AND language_id = ?
");

$insertStmt = $db->prepare("
    INSERT INTO product_translation (product_id, product_version_id, language_id, name, created_at)
    SELECT id, ?, ?, ?, NOW()
    FROM product 
    WHERE product_number = ?
    LIMIT 1
");

$checkStmt = $db->prepare("
    SELECT COUNT(*) FROM product_translation pt
    JOIN product p ON p.id = pt.product_id
    WHERE p.product_number = ? AND pt.language_id = ?
");

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $productNumber = trim($row[$productNumberCol] ?? '');
    $name = trim($row[$nameCol] ?? '');
    $parentId = trim($row[$parentIdCol] ?? '');
    
    // Skip if no product number or name
    if (empty($productNumber) || empty($name)) {
        continue;
    }
    
    // Only update variants (products with parent_id)
    if (empty($parentId)) {
        $skipped++;
        continue;
    }
    
    // Get parent name from cache or database
    $parentName = null;
    if (!empty($parentId)) {
        if (!isset($parentNameCache[$parentId])) {
            $getParentNameStmt->execute([$parentId, $langId]);
            $parentNameCache[$parentId] = $getParentNameStmt->fetchColumn() ?: null;
        }
        $parentName = $parentNameCache[$parentId];
    }
    
    // Build full name: ParentName - Color - Size
    // If parent name exists and variant name doesn't already include it
    if ($parentName && stripos($name, $parentName) === false) {
        // Variant name format is usually "color - size"
        $name = $parentName . ' - ' . $name;
    }
    
    try {
        // Check if translation exists
        $checkStmt->execute([$productNumber, $langId]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists > 0) {
            // Update existing translation
            $updateStmt->execute([$name, $productNumber, $langId]);
            $updated++;
            echo "✓ Updated: $productNumber -> $name\n";
        } else {
            // Insert new translation
            $insertStmt->execute([$liveVersionId, $langId, $name, $productNumber]);
            $updated++;
            echo "✓ Inserted: $productNumber -> $name\n";
        }
    } catch (\Exception $e) {
        $errors++;
        echo "✗ Error for $productNumber: " . $e->getMessage() . "\n";
    }
}

fclose($handle);

echo "\nSummary:\n";
echo "  Updated: $updated\n";
echo "  Skipped: $skipped (parent products)\n";
echo "  Errors: $errors\n";

// Clear cache
if ($updated > 0) {
    echo "\nClearing Shopware cache...\n";
    $cacheDir = dirname(__DIR__);
    @system("cd $cacheDir && bin/console cache:clear 2>&1");
    echo "✓ Cache cleared\n";
}
