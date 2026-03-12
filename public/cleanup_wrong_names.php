<?php
/**
 * Clean up product_translation names that have wrong patterns like "- 10 - 105"
 */
require_once 'db_config.php';
$db = Database::getConnection();

$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b'); // English

echo "=== CLEANING UP WRONG VARIANT NAMES ===\n\n";

// Find products with "- digit - digit" pattern (wrong names)
$stmt = $db->prepare("
    SELECT pt.product_id, p.product_number, pt.name, p.parent_id
    FROM product_translation pt
    JOIN product p ON p.id = pt.product_id
    WHERE pt.language_id = ? 
    AND pt.name REGEXP ' - [0-9]+ - [0-9]+( - |$)'
    LIMIT 100
");
$stmt->execute([$langId]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($results) . " products with wrong pattern\n\n";

foreach ($results as $row) {
    // Extract parent name
    if ($row['parent_id']) {
        $parentStmt = $db->prepare("
            SELECT pt.name FROM product_translation pt
            WHERE pt.product_id = ? AND pt.language_id = ?
        ");
        $parentStmt->execute([$row['parent_id'], $langId]);
        $parentName = $parentStmt->fetchColumn();
    } else {
        $parentName = null;
    }
    
    // Clean the name - remove the "- digit - digit" part
    $oldName = $row['name'];
    $newName = preg_replace('/ - [0-9]+ - [0-9]+/', '', $oldName);
    
    // If we removed something, update
    if ($newName !== $oldName) {
        $updateStmt = $db->prepare("UPDATE product_translation SET name = ? WHERE product_id = ? AND language_id = ?");
        $updateStmt->execute([$newName, $row['product_id'], $langId]);
        echo "✓ " . $row['product_number'] . "\n";
        echo "  Old: " . substr($oldName, 0, 80) . "\n";
        echo "  New: " . substr($newName, 0, 80) . "\n\n";
    }
}

echo "\nClearing cache...\n";
system('cd ' . dirname(__DIR__) . ' && bin/console cache:clear 2>&1 | grep -i "ok\|cache"');
echo "✓ Done!\n";
