<?php
/**
 * Verify Product Translation Integrity
 * Checks if all products have valid product_translation entries with language_id
 */

require_once __DIR__ . '/db_config.php';

echo "=== Product Translation Integrity Check ===\n\n";

$db = Database::getConnection();

// 1. Check total products vs translations
echo "1. Product Count Check:\n";
$sql = "SELECT COUNT(*) as total FROM product";
$stmt = $db->query($sql);
$productCount = $stmt->fetchColumn();
echo "   Total Products: $productCount\n";

$sql = "SELECT COUNT(DISTINCT product_id) as total FROM product_translation WHERE name IS NOT NULL AND name != ''";
$stmt = $db->query($sql);
$translatedCount = $stmt->fetchColumn();
echo "   Products with translations: $translatedCount\n";

$missing = $productCount - $translatedCount;
echo "   Missing translations: $missing\n";
if ($missing > 0) {
    echo "   ⚠️  WARNING: Some products have no translations!\n";
} else {
    echo "   ✓ All products have translations\n";
}

// 2. Check for unnamed products
echo "\n2. Unnamed Product Check:\n";
$sql = "SELECT COUNT(*) as total FROM product_translation WHERE name IS NULL OR name = '' OR name = 'Unnamed Product'";
$stmt = $db->query($sql);
$unnamedCount = $stmt->fetchColumn();
echo "   Unnamed products: $unnamedCount\n";
if ($unnamedCount > 0) {
    echo "   ⚠️  WARNING: Found unnamed products!\n";
    
    // Show examples
    $sql = "SELECT p.product_number, pt.name, LOWER(HEX(pt.product_id)) as pid
            FROM product p
            LEFT JOIN product_translation pt ON pt.product_id = p.id AND pt.product_version_id = p.version_id
            WHERE pt.name IS NULL OR pt.name = '' OR pt.name = 'Unnamed Product'
            LIMIT 5";
    $stmt = $db->query($sql);
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n   Examples:\n";
    foreach ($examples as $ex) {
        echo "   - {$ex['product_number']}: '{$ex['name']}'\n";
    }
} else {
    echo "   ✓ No unnamed products found\n";
}

// 3. Check language_id consistency
echo "\n3. Language ID Check:\n";
$sql = "SELECT LOWER(HEX(language_id)) as lang_id, l.name as lang_name, COUNT(*) as count
        FROM product_translation pt
        LEFT JOIN language l ON l.id = pt.language_id
        GROUP BY pt.language_id
        ORDER BY count DESC";
$stmt = $db->query($sql);
$langStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($langStats as $stat) {
    $langName = $stat['lang_name'] ?: 'Unknown';
    echo "   {$langName} ({$stat['lang_id']}): {$stat['count']} products\n";
}

// 4. Check products without language_id
$sql = "SELECT COUNT(*) as count FROM product p
        WHERE NOT EXISTS (
            SELECT 1 FROM product_translation pt 
            WHERE pt.product_id = p.id 
            AND pt.product_version_id = p.version_id
            AND pt.language_id = UNHEX('2fbb5fe2e29a4d70aa5854ce7ce3e20b')
        )";
$stmt = $db->query($sql);
$missingLangId = $stmt->fetchColumn();

if ($missingLangId > 0) {
    echo "\n   ⚠️  WARNING: $missingLangId products missing English (en-GB) translation\n";
    
    // Show examples
    $sql = "SELECT p.product_number, LOWER(HEX(p.id)) as pid
            FROM product p
            WHERE NOT EXISTS (
                SELECT 1 FROM product_translation pt 
                WHERE pt.product_id = p.id 
                AND pt.product_version_id = p.version_id
                AND pt.language_id = UNHEX('2fbb5fe2e29a4d70aa5854ce7ce3e20b')
            )
            LIMIT 5";
    $stmt = $db->query($sql);
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n   Examples (missing en-GB translation):\n";
    foreach ($examples as $ex) {
        echo "   - {$ex['product_number']} (ID: {$ex['pid']})\n";
    }
} else {
    echo "   ✓ All products have English (en-GB) translations\n";
}

// 5. Recent imports check
echo "\n4. Recent Imports (Last 10 products):\n";
$sql = "SELECT 
        p.product_number,
        pt.name,
        LOWER(HEX(pt.language_id)) as lang_id,
        l.name as lang_name,
        p.created_at
    FROM product p
    LEFT JOIN product_translation pt ON pt.product_id = p.id AND pt.product_version_id = p.version_id
    LEFT JOIN language l ON l.id = pt.language_id
    ORDER BY p.created_at DESC
    LIMIT 10";

$stmt = $db->query($sql);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recent as $row) {
    $status = (!empty($row['name']) && $row['name'] !== 'Unnamed Product') ? '✓' : '✗';
    $langName = $row['lang_name'] ?: 'MISSING';
    printf("   %s %s | %s | %s | %s\n", 
        $status,
        str_pad($row['product_number'], 15), 
        str_pad(substr($row['name'] ?: 'NULL', 0, 30), 30),
        str_pad($langName, 10),
        $row['created_at']
    );
}

echo "\n=== Check Complete ===\n";

// Provide fix command if issues found
if ($missing > 0 || $unnamedCount > 0 || $missingLangId > 0) {
    echo "\n⚠️  Issues detected! Run this to fix:\n";
    echo "   php update_product_names_from_csv.php /path/to/your/csv/file.csv\n";
}
