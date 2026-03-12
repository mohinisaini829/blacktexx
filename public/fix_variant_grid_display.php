<?php
/**
 * Dynamic Variant Display Fixer - Works for ALL products
 * Ensures variant names show properly in Shopware admin
 */

require_once 'db_config.php';
$db = Database::getConnection();

$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b');
$liveVersion = hex2bin('0FA91CE3E96A4BC2BE4BD9CE752C3425');

echo "=== DYNAMIC VARIANT DISPLAY FIXER ===\n\n";

// 1. Find ALL variants without proper product_option entries
echo "Step 1: Finding variants without options...\n";
$stmt = $db->query("
    SELECT COUNT(DISTINCT v.id) as count
    FROM product v
    LEFT JOIN product_option po ON v.id = po.product_id
    WHERE v.parent_id IS NOT NULL
    AND po.property_group_option_id IS NULL
");
$missingOptions = $stmt->fetchColumn();
echo "Found $missingOptions variants without options\n\n";

// 2. Find ALL parent products without configurator settings
echo "Step 2: Finding parent products without configurator settings...\n";
$stmt = $db->query("
    SELECT COUNT(DISTINCT parent.id) as count
    FROM product variant
    JOIN product parent ON variant.parent_id = parent.id
    WHERE variant.parent_id IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM product_configurator_setting pcs 
        WHERE pcs.product_id = parent.id
    )
");
$missingConfigurators = $stmt->fetchColumn();
echo "Found $missingConfigurators parents without configurator settings\n\n";

// 3. Check for variants with names but no options showing in grid
echo "Step 3: Checking variant grid display data...\n";
$stmt = $db->prepare("
    SELECT 
        v.product_number,
        MAX(vt.name) as db_name,
        COUNT(DISTINCT po.property_group_option_id) as option_count
    FROM product v
    LEFT JOIN product_translation vt ON v.id = vt.product_id AND vt.language_id = ?
    LEFT JOIN product_option po ON v.id = po.product_id
    WHERE v.parent_id IS NOT NULL
    GROUP BY v.id, v.product_number
    HAVING option_count = 0
    LIMIT 10
");
$stmt->execute([$langId]);
$problematicVariants = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($problematicVariants) > 0) {
    echo "Found variants with names but no options:\n";
    foreach ($problematicVariants as $pv) {
        echo "  - {$pv['product_number']}: {$pv['db_name']}\n";
    }
    echo "\n";
}

// 4. Fix recommendations
echo "\n=== FIX ACTIONS ===\n";
$needsFix = false;

if ($missingOptions > 0) {
    echo "❌ Issue: $missingOptions variants missing product_option entries\n";
    echo "   Fix: Run importVariantOptionsFromCsv() during product import\n";
    echo "   Or: CSV must have 'optionIds' column with proper option IDs\n\n";
    $needsFix = true;
}

if ($missingConfigurators > 0) {
    echo "❌ Issue: $missingConfigurators parents missing configurator settings\n";
    echo "   Fix: Run fix_configurator_settings.php\n\n";
    $needsFix = true;
}

// 5. Check property group option translations
$stmt = $db->prepare("
    SELECT COUNT(*) FROM property_group_option pgo
    LEFT JOIN property_group_option_translation pgot 
        ON pgo.id = pgot.property_group_option_id AND pgot.language_id = ?
    WHERE pgot.name IS NULL
");
$stmt->execute([$langId]);
$missingTranslations = $stmt->fetchColumn();

if ($missingTranslations > 0) {
    echo "⚠ Warning: $missingTranslations property options missing translations\n";
    echo "   This will cause empty names in admin grid\n\n";
    $needsFix = true;
}

if (!$needsFix) {
    echo "✅ All checks passed!\n\n";
    echo "=== IMPORTANT: Shopware Admin Grid Behavior ===\n";
    echo "The variant 'Name' column shows OPTION COMBINATIONS, not product names:\n";
    echo "  Example: 'weiß / XS' instead of 'Hemd Business Regular 105 - weiß - XS'\n";
    echo "\nThis is NORMAL Shopware behavior!\n";
    echo "To see full product names:\n";
    echo "  1. Click 'Edit' on individual variants\n";
    echo "  2. Or check product_translation table directly\n\n";
    echo "If grid is still empty:\n";
    echo "  1. Clear browser cache (Ctrl+Shift+R)\n";
    echo "  2. Clear Shopware cache: bin/console cache:clear\n";
    echo "  3. Logout and login to admin panel\n";
} else {
    echo "\n=== AUTO-FIX AVAILABLE ===\n";
    echo "Run these scripts to fix all issues:\n";
    echo "  php fix_configurator_settings.php\n";
    echo "  php fix_variants_simple.php [csv-file]\n";
}

// 6. Show statistics
echo "\n=== STATISTICS ===\n";
$stats = $db->query("SELECT COUNT(*) FROM product WHERE parent_id IS NOT NULL")->fetchColumn();
echo "Total variants in system: $stats\n";

$statsWithNames = $db->prepare("
    SELECT COUNT(DISTINCT v.id) FROM product v
    JOIN product_translation vt ON v.id = vt.product_id AND vt.language_id = ?
    WHERE v.parent_id IS NOT NULL AND vt.name IS NOT NULL
");
$statsWithNames->execute([$langId]);
echo "Variants with names: " . $statsWithNames->fetchColumn() . "\n";

$statsWithOptions = $db->query("
    SELECT COUNT(DISTINCT v.id) FROM product v
    JOIN product_option po ON v.id = po.product_id
    WHERE v.parent_id IS NOT NULL
")->fetchColumn();
echo "Variants with options: $statsWithOptions\n";
