<?php
/**
 * Fix variants using the original import CSV (dynamic for all parents)
 * - Updates variant names to include color + size
 * - Inserts missing product_option entries for color/size
 *
 * Optional filter: pass a product number prefix as CLI arg or ?prefix= in URL.
 */

require_once 'db_config.php';
$db = Database::getConnection();

$langId = hex2bin('2fbb5fe2e29a4d70aa5854ce7ce3e20b'); // English
$liveVersionId = hex2bin('0FA91CE3E96A4BC2BE4BD9CE752C3425');

$colorGroupId = hex2bin('0198135f7a2f7600a44ed9ab388d112a');
$sizeGroupId = hex2bin('0198135ff7147512ab7153a50575bdc8');

// Initialize $csvPath if not already set (from include)
if (!isset($csvPath) || empty($csvPath)) {
    $csvPath = __DIR__ . '/../files/import/019c288c/00177069/bd2533be/1790595b';
}
$targetPrefix = null;

if (PHP_SAPI === 'cli') {
    if (isset($argv[1]) && trim($argv[1]) !== '') {
        $arg1 = trim($argv[1]);
        if (file_exists($arg1)) {
            $csvPath = $arg1;
        } else {
            $targetPrefix = $arg1;
        }
    }
    if (isset($argv[2]) && trim($argv[2]) !== '') {
        $arg2 = trim($argv[2]);
        if (file_exists($arg2)) {
            $csvPath = $arg2;
        } else {
            $targetPrefix = $arg2;
        }
    }
} else {
    if (isset($_GET['csv']) && trim($_GET['csv']) !== '' && file_exists($_GET['csv'])) {
        $csvPath = trim($_GET['csv']);
    }
    if (isset($_GET['prefix']) && trim($_GET['prefix']) !== '') {
        $targetPrefix = trim($_GET['prefix']);
    }
}

// Auto-pick latest HAKRO CSV if available
if (!file_exists($csvPath)) {
    $harkoDir = __DIR__ . '/csv-imports/product/';
    $harkoFiles = glob($harkoDir . 'harko_product_import_*.csv');
    if (!empty($harkoFiles)) {
        usort($harkoFiles, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $csvPath = $harkoFiles[0];
    }
}

if (!file_exists($csvPath)) {
    echo "CSV not found: $csvPath\n";
    exit(1);
}

// Build option map by name + group
$optionMap = [];
$colorNames = [];
$sizeNames = [];
$optionIdMap = [];
$stmt = $db->prepare("
    SELECT pgo.id, HEX(pgo.id) AS id_hex, pgo.property_group_id, pot.name
    FROM property_group_option pgo
    JOIN property_group_option_translation pot ON pot.property_group_option_id = pgo.id
    WHERE pot.language_id = ?
");
$stmt->execute([$langId]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nameKey = strtolower(trim($row['name']));
    if ($nameKey === '') {
        continue;
    }
    $optionMap[$nameKey][$row['property_group_id']] = $row['id'];
    $idHex = strtolower($row['id_hex']);
    $optionIdMap[$idHex] = [
        'id' => $row['id'],
        'group_id' => $row['property_group_id'],
        'name' => $row['name']
    ];
    if ($row['property_group_id'] === $colorGroupId) {
        $colorNames[$nameKey] = $row['name'];
    }
    if ($row['property_group_id'] === $sizeGroupId) {
        $sizeNames[$nameKey] = $row['name'];
    }
}

uksort($colorNames, function ($a, $b) {
    return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
});
uksort($sizeNames, function ($a, $b) {
    return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
});

// Parent name cache
$parentNameCache = [];
$parentStmt = $db->prepare("
    SELECT parent.id AS parent_id, pt.name AS parent_name
    FROM product p
    JOIN product parent ON parent.id = p.parent_id
    LEFT JOIN product_translation pt ON pt.product_id = parent.id AND pt.language_id = ?
    WHERE p.id = ?
    LIMIT 1
");

$selectProductId = $db->prepare("SELECT id FROM product WHERE product_number = ? LIMIT 1");
$checkName = $db->prepare("SELECT COUNT(*) FROM product_translation WHERE product_id = ? AND language_id = ?");
$updateName = $db->prepare("UPDATE product_translation SET name = ? WHERE product_id = ? AND language_id = ?");
$insertName = $db->prepare("INSERT INTO product_translation (product_id, product_version_id, language_id, name, created_at) VALUES (?, ?, ?, ?, NOW())");
$checkOption = $db->prepare("SELECT COUNT(*) FROM product_option WHERE product_id = ? AND property_group_option_id = ?");
$insertOption = $db->prepare("INSERT INTO product_option (product_id, product_version_id, property_group_option_id) VALUES (?, ?, ?)");

$updated = 0;
$errors = 0;

if (($handle = fopen($csvPath, 'r')) !== false) {
    $header = fgetcsv($handle, 0, ';');
    $index = array_flip($header);

    $productNumberKey = null;
    foreach (['productNumber', 'product_number'] as $key) {
        if (isset($index[$key])) {
            $productNumberKey = $key;
            break;
        }
    }

    $nameKey = null;
    foreach (['name', 'translations.DEFAULT.name'] as $key) {
        if (isset($index[$key])) {
            $nameKey = $key;
            break;
        }
    }

    $optionIdsKey = null;
    foreach (['optionIds', 'option_ids'] as $key) {
        if (isset($index[$key])) {
            $optionIdsKey = $key;
            break;
        }
    }

    $propertyIdsKey = null;
    foreach (['propertyIds', 'property_ids'] as $key) {
        if (isset($index[$key])) {
            $propertyIdsKey = $key;
            break;
        }
    }

    if (!$productNumberKey || !$nameKey) {
        echo "CSV header missing required columns. Found: " . implode(',', $header) . "\n";
        fclose($handle);
        exit(1);
    }

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $productNumber = $row[$index[$productNumberKey]] ?? '';
        $rawName = $row[$index[$nameKey]] ?? '';

        if ($productNumber === '') {
            continue;
        }
        if ($targetPrefix !== null && strpos($productNumber, $targetPrefix) !== 0) {
            continue;
        }

        $selectProductId->execute([$productNumber]);
        $productId = $selectProductId->fetchColumn();
        if (!$productId) {
            $errors++;
            echo "! Missing product: $productNumber\n";
            continue;
        }

        $colorOptionId = null;
        $sizeOptionId = null;
        $colorName = '';
        $sizeName = '';

        $rawOptionIds = [];
        if ($optionIdsKey && !empty($row[$index[$optionIdsKey]])) {
            $rawOptionIds = array_merge($rawOptionIds, array_filter(array_map('trim', explode('|', $row[$index[$optionIdsKey]]))));
        }
        if (empty($rawOptionIds) && $propertyIdsKey && !empty($row[$index[$propertyIdsKey]])) {
            $rawOptionIds = array_merge($rawOptionIds, array_filter(array_map('trim', explode('|', $row[$index[$propertyIdsKey]]))));
        }

        if (!empty($rawOptionIds)) {
            $optionIds = array_unique($rawOptionIds);
            foreach ($optionIds as $optId) {
                $optIdHex = strtolower(str_replace('-', '', $optId));
                if (!isset($optionIdMap[$optIdHex])) {
                    continue;
                }
                $opt = $optionIdMap[$optIdHex];
                if ($opt['group_id'] === $colorGroupId) {
                    $colorOptionId = $opt['id'];
                    $colorName = $opt['name'];
                }
                if ($opt['group_id'] === $sizeGroupId) {
                    $sizeOptionId = $opt['id'];
                    $sizeName = $opt['name'];
                }
            }
        }

        $cleanName = preg_replace('/^HAKRO\s+/i', '', $rawName);
        $parts = array_map('trim', explode(',', $cleanName, 2));
        $basePart = $parts[0] ?? $cleanName;
        $sizeName = $sizeName !== '' ? $sizeName : ($parts[1] ?? '');

        // If we still don't have color/size from CSV optionIds, check product_option in database
        if ($colorName === '' || $sizeName === '') {
            $dbOptions = $db->prepare("
                SELECT pgo.id, pot.name, pgo.property_group_id
                FROM product_option po
                JOIN property_group_option pgo ON pgo.id = po.property_group_option_id
                JOIN property_group_option_translation pot ON pot.property_group_option_id = pgo.id
                WHERE po.product_id = ? AND pot.language_id = ?
            ");
            $dbOptions->execute([$productId, $langId]);
            foreach ($dbOptions->fetchAll(PDO::FETCH_ASSOC) as $opt) {
                if ($opt['property_group_id'] === $colorGroupId && $colorName === '') {
                    $colorName = $opt['name'];
                    $colorOptionId = $opt['id'];
                }
                if ($opt['property_group_id'] === $sizeGroupId && $sizeName === '') {
                    $sizeName = $opt['name'];
                    $sizeOptionId = $opt['id'];
                }
            }
        }

        // Resolve parent name from product -> parent_id
        $parentName = null;
        $parentStmt->execute([$langId, $productId]);
        $parentRow = $parentStmt->fetch(PDO::FETCH_ASSOC);
        if ($parentRow && !empty($parentRow['parent_id'])) {
            $parentId = $parentRow['parent_id'];
            if (!isset($parentNameCache[$parentId])) {
                $parentNameCache[$parentId] = $parentRow['parent_name'] ?: null;
            }
            $parentName = $parentNameCache[$parentId];
        }

        if ($colorName === '') {
            $baseLower = mb_strtolower($basePart, 'UTF-8');
            foreach ($colorNames as $colorKey => $colorDisplay) {
                if ($colorKey === '') {
                    continue;
                }
                if (mb_strpos($baseLower, $colorKey, 0, 'UTF-8') !== false) {
                    $colorName = $colorDisplay;
                    break;
                }
            }
        }

        // Only use fallback extraction if we have a valid parent name
        if ($parentName && $colorName === '') {
            $maybeColor = trim(str_replace($parentName, '', $basePart));
            $maybeColor = trim(preg_replace('/^[\-–—]+/', '', $maybeColor));
            // Validate: must be a real color option, not random text or numbers
            if ($maybeColor !== '' && isset($colorNames[strtolower($maybeColor)])) {
                $colorName = $maybeColor;
            }
        }

        if ($sizeName === '') {
            $nameLower = mb_strtolower($cleanName, 'UTF-8');
            foreach ($sizeNames as $sizeKey => $sizeDisplay) {
                if ($sizeKey === '') {
                    continue;
                }
                if (mb_strpos($nameLower, $sizeKey, 0, 'UTF-8') !== false) {
                    $sizeName = $sizeDisplay;
                    break;
                }
            }
        }

        $colorKey = strtolower($colorName);
        $sizeKey = strtolower($sizeName);

        if (!$colorOptionId) {
            $colorOptionId = $optionMap[$colorKey][$colorGroupId] ?? null;
        }
        if (!$sizeOptionId) {
            $sizeOptionId = $optionMap[$sizeKey][$sizeGroupId] ?? null;
        }

        if (!$colorOptionId || !$sizeOptionId) {
            $errors++;
            echo "! Option missing for $productNumber (color: $colorName, size: $sizeName)\n";
            continue;
        }

        $finalParentName = $parentName ?: $basePart;
        $newName = $finalParentName . ' - ' . $colorName . ' - ' . $sizeName;

        // Skip if the current name already matches the intended pattern
        $rawNameNormalized = trim(preg_replace('/\s+/', ' ', (string)$rawName));
        $newNameNormalized = trim(preg_replace('/\s+/', ' ', (string)$newName));
        if ($rawNameNormalized !== '' && strcasecmp($rawNameNormalized, $newNameNormalized) === 0) {
            $updated++;
            echo "✓ Skipped (already correct): $productNumber -> $rawNameNormalized\n";
            continue;
        }

        if ($parentName && $colorName !== '' && $sizeName !== '') {
            $pattern = '/'.preg_quote($parentName, '/').'\s*-\s*'.preg_quote($colorName, '/').'\s*-\s*'.preg_quote($sizeName, '/').'/i';
            if (preg_match($pattern, (string)$rawName)) {
                $updated++;
                echo "✓ Skipped (contains parent/color/size): $productNumber -> $rawName\n";
                continue;
            }
        }

        $checkName->execute([$productId, $langId]);
        if ($checkName->fetchColumn() > 0) {
            $updateName->execute([$newName, $productId, $langId]);
        } else {
            $insertName->execute([$productId, $liveVersionId, $langId, $newName]);
        }

        $checkOption->execute([$productId, $colorOptionId]);
        if ($checkOption->fetchColumn() == 0) {
            $insertOption->execute([$productId, $liveVersionId, $colorOptionId]);
        }

        $checkOption->execute([$productId, $sizeOptionId]);
        if ($checkOption->fetchColumn() == 0) {
            $insertOption->execute([$productId, $liveVersionId, $sizeOptionId]);
        }

        // --- Ensure product_visibility for main sales channel ---
        static $upsertVisibility = null;
        static $salesChannelId = null;
        static $VISIBILITY_ALL = 30;
        if ($upsertVisibility === null) {
            $upsertVisibility = $db->prepare("INSERT IGNORE INTO product_visibility (id, product_id, product_version_id, sales_channel_id, visibility, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $salesChannelId = hex2bin('0197e3dc1566708987331d818f8e1867');
        }
        try {
            $id = random_bytes(16); // 16 bytes for binary(16) id
            $upsertVisibility->execute([$id, $productId, $liveVersionId, $salesChannelId, $VISIBILITY_ALL]);
        } catch (Exception $e) {
            echo "! Visibility error for $productNumber: " . $e->getMessage() . "\n";
        }

        $updated++;
        echo "✓ Fixed: $productNumber -> $newName\n";
    }
    fclose($handle);
}

echo "\nSummary:\n";
echo "  Updated: $updated\n";
echo "  Errors: $errors\n";
// ...existing code...
// Clear cache
echo "\nClearing Shopware cache...\n";
system('cd ' . dirname(__DIR__) . ' && bin/console cache:clear 2>&1');
echo "✓ Cache cleared\n";
