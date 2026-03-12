<?php
/**
 * Fix Child Product Names - Ensure all variants have proper names with color/size
 */

require_once __DIR__ . '/db_config.php';

echo "=== Fixing Child Product Names ===\n\n";

$db = Database::getConnection();
$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b'); // English

// Get all child products that are missing names or have only parent names
$sql = "
SELECT 
    p.product_number,
    LOWER(HEX(p.id)) as product_id,
    LOWER(HEX(p.version_id)) as version_id,
    LOWER(HEX(p.parent_id)) as parent_id,
    pt.name as child_name,
    parent_pt.name as parent_name,
    GROUP_CONCAT(pot.name ORDER BY pgt.name SEPARATOR ' - ') as options
FROM product p
LEFT JOIN product_translation pt ON pt.product_id = p.id AND pt.product_version_id = p.version_id AND pt.language_id = ?
LEFT JOIN product parent_p ON parent_p.id = p.parent_id
LEFT JOIN product_translation parent_pt ON parent_pt.product_id = parent_p.id AND parent_pt.product_version_id = parent_p.version_id AND parent_pt.language_id = ?
LEFT JOIN product_option po ON po.product_id = p.id AND po.product_version_id = p.version_id
LEFT JOIN property_group_option pgo ON pgo.id = po.property_group_option_id
LEFT JOIN property_group_option_translation pot ON pot.property_group_option_id = pgo.id AND pot.language_id = ?
LEFT JOIN property_group pg ON pg.id = pgo.property_group_id
LEFT JOIN property_group_translation pgt ON pgt.property_group_id = pg.id AND pgt.language_id = ?
WHERE p.parent_id IS NOT NULL
GROUP BY p.id, p.product_number, p.version_id, p.parent_id, pt.name, parent_pt.name
HAVING options IS NOT NULL
ORDER BY p.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute([$langId, $langId, $langId, $langId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($children) . " child products to process\n\n";

$updated = 0;
$inserted = 0;
$errors = 0;

foreach ($children as $child) {
    try {
        $productId = hex2bin($child['product_id']);
        $versionId = hex2bin($child['version_id']);
        $productNumber = $child['product_number'];
        $parentName = $child['parent_name'];
        $options = $child['options'];
        
        // Generate child name: Parent Name - Options
        $newName = trim($parentName . ' - ' . $options);
        
        // Check if translation exists
        $checkSql = "SELECT name FROM product_translation WHERE product_id = ? AND product_version_id = ? AND language_id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$productId, $versionId, $langId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing name
            $updateSql = "UPDATE product_translation SET name = ?, updated_at = NOW() WHERE product_id = ? AND product_version_id = ? AND language_id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$newName, $productId, $versionId, $langId]);
            echo "✓ Updated: $productNumber -> $newName\n";
            $updated++;
        } else {
            // Insert new translation
            $insertSql = "INSERT INTO product_translation (product_id, product_version_id, language_id, name, created_at) VALUES (?, ?, ?, ?, NOW())";
            $insertStmt = $db->prepare($insertSql);
            $insertStmt->execute([$productId, $versionId, $langId, $newName]);
            echo "✓ Inserted: $productNumber -> $newName\n";
            $inserted++;
        }
        
    } catch (Exception $e) {
        echo "✗ Error for $productNumber: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  Updated: $updated\n";
echo "  Inserted: $inserted\n";
echo "  Errors: $errors\n";
echo "========================================\n";

// Also clear Shopware cache
echo "\nClearing Shopware cache...\n";
system("cd /var/www/html/shopware678 && php bin/console cache:clear 2>&1");
echo "✓ Cache cleared\n";
