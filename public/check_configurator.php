<?php
/**
 * Check product options and configurator settings for variants
 */

require_once 'db_config.php';
$db = Database::getConnection();

$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b');

echo "Checking variant options and configurator settings...\n\n";

// Check one parent product
$parentProductNumber = 'M-HK0105';

$stmt = $db->prepare("
    SELECT HEX(id) as pid FROM product WHERE product_number = ?
");
$stmt->execute([$parentProductNumber]);
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    echo "Parent product not found: $parentProductNumber\n";
    exit(1);
}

$parentId = $parent['pid'];
echo "Parent Product: $parentProductNumber (ID: $parentId)\n\n";

// Check configurator settings
echo "=== Configurator Settings ===\n";
$stmt = $db->prepare("
    SELECT 
        HEX(pcs.property_group_option_id) as option_id,
        pgot.name as option_name,
        HEX(pgo.property_group_id) as group_id,
        pgt.name as group_name
    FROM product_configurator_setting pcs
    JOIN property_group_option pgo ON pcs.property_group_option_id = pgo.id
    LEFT JOIN property_group_option_translation pgot ON pgo.id = pgot.property_group_option_id AND pgot.language_id = ?
    JOIN property_group pg ON pgo.property_group_id = pg.id
    LEFT JOIN property_group_translation pgt ON pg.id = pgt.property_group_id AND pgt.language_id = ?
    WHERE pcs.product_id = UNHEX(?)
    ORDER BY pgt.name, pgot.name
");
$stmt->execute([$langId, $langId, $parentId]);
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($settings)) {
    echo "NO CONFIGURATOR SETTINGS FOUND!\n";
    echo "This is likely the problem - Shopware needs configurator settings to display variants.\n\n";
} else {
    foreach ($settings as $setting) {
        echo "{$setting['group_name']}: {$setting['option_name']}\n";
    }
    echo "\nTotal configurator settings: " . count($settings) . "\n\n";
}

// Check one variant's options
echo "=== Variant Options (first variant) ===\n";
$stmt = $db->prepare("
    SELECT 
        p.product_number,
        HEX(po.property_group_option_id) as option_id,
        pgot.name as option_name,
        pgt.name as group_name
    FROM product p
    LEFT JOIN product_option po ON p.id = po.product_id
    LEFT JOIN property_group_option pgo ON po.property_group_option_id = pgo.id
    LEFT JOIN property_group_option_translation pgot ON pgo.id = pgot.property_group_option_id AND pgot.language_id = ?
    LEFT JOIN property_group pg ON pgo.property_group_id = pg.id
    LEFT JOIN property_group_translation pgt ON pg.id = pgt.property_group_id AND pgt.language_id = ?
    WHERE p.parent_id = UNHEX(?)
    LIMIT 1
");
$stmt->execute([$langId, $langId, $parentId]);
$variant = $stmt->fetch(PDO::FETCH_ASSOC);

if ($variant) {
    echo "Variant: {$variant['product_number']}\n";
    if ($variant['option_id']) {
        echo "  {$variant['group_name']}: {$variant['option_name']}\n";
    } else {
        echo "  NO OPTIONS FOUND!\n";
        echo "  This is the problem - variants need product_option entries.\n";
    }
}

echo "\n";
