<?php
/**
 * AUTO-FIX: Assign missing product options to all variants
 * Creates product_option entries based on variant names (color/size detection)
 */

require_once 'db_config.php';
$db = Database::getConnection();

$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b');
$liveVersion = hex2bin('0FA91CE3E96A4BC2BE4BD9CE752C3425');

// Get property group IDs
$colorGroupId = hex2bin('0198135f7a2f7600a44ed9ab388d112a');
$sizeGroupId = hex2bin('0198135ff7147512ab7153a50575bdc8');

echo "=== AUTO-FIX: Assigning Missing Product Options ===\n\n";

// Build option maps
echo "Building option maps...\n";
$colorOptions = [];
$sizeOptions = [];

$stmt = $db->prepare("
    SELECT pgo.id, pgot.name, pgo.property_group_id
    FROM property_group_option pgo
    JOIN property_group_option_translation pgot ON pgo.id = pgot.property_group_option_id
    WHERE pgot.language_id = ?
");
$stmt->execute([$langId]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $name = strtolower(trim($row['name']));
    if ($row['property_group_id'] === $colorGroupId) {
        $colorOptions[$name] = $row['id'];
    } elseif ($row['property_group_id'] === $sizeGroupId) {
        $sizeOptions[$name] = $row['id'];
    }
}

echo "Color options: " . count($colorOptions) . "\n";
echo "Size options: " . count($sizeOptions) . "\n\n";

// Get variants without options
$stmt = $db->prepare("
    SELECT v.id, v.product_number, vt.name
    FROM product v
    LEFT JOIN product_translation vt ON v.id = vt.product_id AND vt.language_id = ?
    LEFT JOIN product_option po ON v.id = po.product_id
    WHERE v.parent_id IS NOT NULL
    AND po.property_group_option_id IS NULL
");
$stmt->execute([$langId]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Processing " . count($variants) . " variants...\n\n";

$insertStmt = $db->prepare("
    INSERT INTO product_option (product_id, product_version_id, property_group_option_id)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE product_id=product_id
");

$fixed = 0;
$skipped = 0;

foreach ($variants as $variant) {
    $variantName = strtolower($variant['name'] ?? '');
    $foundColor = null;
    $foundSize = null;
    
    // Try to match color from name
    foreach ($colorOptions as $colorName => $colorId) {
        if (stripos($variantName, $colorName) !== false) {
            $foundColor = $colorId;
            break;
        }
    }
    
    // Try to match size from name
    foreach ($sizeOptions as $sizeName => $sizeId) {
        if (preg_match('/\b' . preg_quote($sizeName, '/') . '\b/i', $variantName)) {
            $foundSize = $sizeId;
            break;
        }
    }
    
    if ($foundColor && $foundSize) {
        try {
            $insertStmt->execute([$variant['id'], $liveVersion, $foundColor]);
            $insertStmt->execute([$variant['id'], $liveVersion, $foundSize]);
            $fixed++;
            if ($fixed % 100 == 0) {
                echo "Fixed $fixed variants...\n";
            }
        } catch (Exception $e) {
            // Skip duplicates
        }
    } else {
        $skipped++;
        if ($skipped <= 10) {
            echo "Skipped {$variant['product_number']}: {$variant['name']} (could not detect options)\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed variants\n";
echo "Skipped: $skipped variants (could not auto-detect options)\n";

// Now fix configurator settings
echo "\nFixing configurator settings...\n";
include __DIR__ . '/fix_configurator_settings.php';