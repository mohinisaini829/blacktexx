<?php
/**
 * Create missing product_configurator_setting entries for parent products
 * This fixes the variant listing display in Shopware admin
 */

require_once 'db_config.php';
$db = Database::getConnection();

$liveVersionId = hex2bin('0FA91CE3E96A4BC2BE4BD9CE752C3425');

echo "Creating missing configurator settings for parent products...\n\n";

// Find all parent products that have variants but no configurator settings
$stmt = $db->query("
    SELECT DISTINCT
        HEX(parent.id) as parent_id,
        parent.product_number as parent_number
    FROM product variant
    JOIN product parent ON variant.parent_id = parent.id
    WHERE variant.parent_id IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM product_configurator_setting pcs 
        WHERE pcs.product_id = parent.id
    )
");

$parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($parents) . " parent products without configurator settings\n\n";

if (empty($parents)) {
    echo "All parent products already have configurator settings!\n";
    return;
}

$totalCreated = 0;

foreach ($parents as $parent) {
        // Print product_option rows for the first child product
        if (!empty($children)) {
            $firstChildId = $children[0]['id'];
            echo "  [DEBUG] Checking product_option for child: $firstChildId\n";
            $poStmt = $db->prepare("SELECT HEX(product_id) as product_id, HEX(property_group_option_id) as option_id FROM product_option WHERE product_id = UNHEX(?) LIMIT 5");
            $poStmt->execute([$firstChildId]);
            $poRows = $poStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($poRows)) {
                foreach ($poRows as $row) {
                    echo "    - product_option.product_id: ".$row['product_id'].", option_id: ".$row['option_id']."\n";
                }
            } else {
                echo "    - No product_option rows found for this child\n";
            }
        }
    $parentId = $parent['parent_id'];
    $parentIdBin = hex2bin($parentId);
    $parentNumber = $parent['parent_number'];
    
    echo "Processing parent: $parentNumber\n";
    echo "  [DEBUG] parentId param (hex): $parentId\n";
    echo "  [DEBUG] parentId param (bin): ".bin2hex($parentIdBin)."\n";
    // Print a sample of child products for this parent
    $childStmt = $db->prepare("SELECT HEX(id) as id, HEX(parent_id) as parent_id FROM product WHERE parent_id = UNHEX(?) LIMIT 3");
    $childStmt->execute([$parentId]);
    $children = $childStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($children)) {
        echo "  [DEBUG] Found ".count($children)." child products. Sample:\n";
        foreach ($children as $child) {
            echo "    - Child ID: ".$child['id'].", parent_id: ".$child['parent_id']."\n";
        }
    } else {
        echo "  [DEBUG] No child products found for this parentId\n";
    }
    
    // Get all unique options used by variants of this parent
    $optionsStmt = $db->prepare("
        SELECT DISTINCT
            HEX(po.property_group_option_id) as option_id,
            HEX(pgo.property_group_id) as group_id,
            pgo.property_group_id as group_id_raw,
            po.property_group_option_id as option_id_raw
        FROM product p
        JOIN product_option po ON p.id = po.product_id
        JOIN property_group_option pgo ON po.property_group_option_id = pgo.id
        WHERE HEX(p.parent_id) = ?
        ORDER BY group_id_raw, option_id_raw
    ");
    $optionsStmt->execute([$parentId]);
    $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  [DEBUG] SQL returned ".count($options)." options\n";
    
    if (empty($options)) {
        echo "  ⚠ No options found for variants\n";
        continue;
    }
    
    $created = 0;
    $position = 0;
    
    foreach ($options as $option) {
        try {
            // Check if configurator setting already exists
            $checkStmt = $db->prepare("
                SELECT COUNT(*) FROM product_configurator_setting 
                WHERE product_id = UNHEX(?) 
                AND property_group_option_id = UNHEX(?)
            ");
            $checkStmt->execute([$parentId, $option['option_id']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                continue; // Already exists
            }
            
            // Create configurator setting
            $insertStmt = $db->prepare("
                INSERT INTO product_configurator_setting 
                (id, version_id, product_id, product_version_id, property_group_option_id, position, created_at)
                VALUES (UNHEX(REPLACE(UUID(), '-', '')), ?, UNHEX(?), ?, UNHEX(?), ?, NOW())
            ");
            
            $insertStmt->execute([
                $liveVersionId,
                $parentId,
                $liveVersionId,
                $option['option_id'],
                $position++
            ]);
            
            $created++;
            $totalCreated++;
            
        } catch (Exception $e) {
            echo "  ✗ Error creating configurator setting: " . $e->getMessage() . "\n";
        }
    }
    
    echo "  ✓ Created $created configurator settings\n";
}

echo "\n=== Summary ===\n";
echo "Total configurator settings created: $totalCreated\n";

if ($totalCreated > 0) {
    echo "\nClearing Shopware cache...\n";
    $cacheDir = dirname(__DIR__);
    @system("cd $cacheDir && bin/console cache:clear 2>&1");
    echo "✓ Cache cleared\n";
    echo "\nVariant listing should now work properly in Shopware admin!\n";
}
