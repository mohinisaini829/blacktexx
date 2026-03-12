<?php
require_once 'db_config.php';

$db = Database::getConnection();
$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b');

// Check child products with names
$check = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN pt.name LIKE '%-%' THEN 1 ELSE 0 END) as with_options,
        SUM(CASE WHEN pt.name IS NULL OR pt.name = '' THEN 1 ELSE 0 END) as blank
    FROM product p
    LEFT JOIN product_translation pt ON pt.product_id = p.id AND pt.language_id = ?
    WHERE p.parent_id IS NOT NULL
");
$check->execute([$langId]);
$result = $check->fetch(PDO::FETCH_ASSOC);

echo "Child Products Status:\n";
echo "  Total: " . $result['total'] . "\n";
echo "  With Options (contains '-'): " . $result['with_options'] . "\n";
echo "  Blank/Missing: " . $result['blank'] . "\n\n";

// Sample names
$samples = $db->prepare("
    SELECT pt.name 
    FROM product p
    JOIN product_translation pt ON pt.product_id = p.id AND pt.language_id = ?
    WHERE p.parent_id IS NOT NULL AND pt.name LIKE '%-%'
    ORDER BY p.created_at DESC
    LIMIT 10
");
$samples->execute([$langId]);
echo "Sample Names:\n";
while ($row = $samples->fetch(PDO::FETCH_ASSOC)) {
    echo "  • " . $row['name'] . "\n";
}
