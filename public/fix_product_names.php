<?php
/**
 * Fix Product Names - Add translations for products without names
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;


// Try to load .env.local, but also set fallback for critical env vars
if (file_exists(__DIR__ . '/../.env.local')) {
    (new Dotenv())->loadEnv(__DIR__ . '/../.env.local');
}

// Fallbacks if env not loaded
if (!getenv('APP_ENV') && empty($_ENV['APP_ENV'])) {
    putenv('APP_ENV=dev');
    $_ENV['APP_ENV'] = 'dev';
}
if (!getenv('APP_DEBUG') && empty($_ENV['APP_DEBUG'])) {
    putenv('APP_DEBUG=1');
    $_ENV['APP_DEBUG'] = '1';
}
if (!getenv('DATABASE_URL') && empty($_ENV['DATABASE_URL'])) {
    putenv('DATABASE_URL=mysql://emizen:Emizen@123@localhost:3306/shopware678');
    $_ENV['DATABASE_URL'] = 'mysql://emizen:Emizen@123@localhost:3306/shopware678';
}

$classLoader = require __DIR__ . '/../vendor/autoload.php';
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$debug = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

$kernel = KernelFactory::create(
    environment: $appEnv,
    debug: $debug,
    classLoader: $classLoader,
    pluginLoader: null
);


$kernel->boot();
$container = $kernel->getContainer();
$connection = $container->get(Connection::class);

// Debug: Check DB connection
try {
    $dbParams = $connection->getParams();
    echo "[DEBUG] Connected to DB: ".$dbParams['dbname']." as ".$dbParams['user']."@".$dbParams['host']."\n";
} catch (\Exception $e) {
    echo "[ERROR] DB connection failed: ".$e->getMessage()."\n";
    exit(1);
}

// Get default language ID (IDs are already binary, do not use UNHEX/REPLACE)
$languageId = $connection->fetchOne("SELECT id FROM language WHERE locale_id = (SELECT id FROM locale WHERE code = 'de-DE') LIMIT 1");
echo "[DEBUG] de-DE languageId query result: ".($languageId ? bin2hex($languageId) : 'NULL')."\n";

if (!$languageId) {
    $languageId = $connection->fetchOne("SELECT id FROM language LIMIT 1");
    echo "[DEBUG] fallback languageId query result: ".($languageId ? bin2hex($languageId) : 'NULL')."\n";
}

if (!$languageId) {
    echo "[ERROR] Could not determine language_id. Aborting.\n";
    exit(1);
}

echo "Using Language ID: " . bin2hex($languageId) . "\n\n";

// Get products without translations

$sql = "SELECT 
    LOWER(HEX(p.id)) as product_id,
    p.product_number,
    LOWER(HEX(p.parent_id)) as parent_id
FROM product p
WHERE NOT EXISTS (
    SELECT 1 FROM product_translation pt 
    WHERE pt.product_id = p.id 
    AND pt.language_id = ?
    AND pt.product_version_id = UNHEX('00000000000000000000000000000000')
)
ORDER BY p.created_at DESC";

$products = $connection->fetchAllAssociative($sql, [$languageId]);

echo "Found " . count($products) . " products without translations\n\n";

$updated = 0;
$errors = 0;

foreach ($products as $product) {
    $productNumber = $product['product_number'];
    $productId = hex2bin($product['product_id']);
    // Get the correct version_id for this product
    $productVersionId = $connection->fetchOne(
        "SELECT version_id FROM product WHERE id = ? LIMIT 1",
        [$productId]
    );
    if (!$productVersionId) {
        echo "✗ Skipped: $productNumber (product/version not found)\n";
        $errors++;
        continue;
    }

    // Generate name based on product number
    $name = "Product " . $productNumber;

    // If it's a variant (has parent), generate variant name
    if ($product['parent_id']) {
        $parentId = hex2bin($product['parent_id']);
        // Get the correct version_id for the parent
        $parentVersionId = $connection->fetchOne(
            "SELECT version_id FROM product WHERE id = ? LIMIT 1",
            [$parentId]
        );
        if (!$parentVersionId) {
            $parentVersionId = $productVersionId;
        }

        // Get parent name
        $parentName = $connection->fetchOne(
            "SELECT name FROM product_translation WHERE product_id = ? AND product_version_id = ? AND language_id = ? LIMIT 1",
            [$parentId, $parentVersionId, $languageId]
        );

        // Get variant options (color, size, etc)
        $options = $connection->fetchAllAssociative(
            "SELECT pgt.name as group_name, pot.name as option_name
            FROM product_option po
            JOIN property_group_option pgo ON po.property_group_option_id = pgo.id
            JOIN property_group pg ON pgo.property_group_id = pg.id
            JOIN property_group_translation pgt ON pg.id = pgt.property_group_id AND pgt.language_id = ?
            JOIN property_group_option_translation pot ON pgo.id = pot.property_group_option_id AND pot.language_id = ?
            WHERE po.product_id = ? AND po.product_version_id = ?",
            [$languageId, $languageId, $productId, $productVersionId]
        );

        if ($parentName && !empty($options)) {
            $optionNames = array_column($options, 'option_name');
            $name = $parentName . ' - ' . implode(' - ', $optionNames);
        }
    }

    try {
        // Check if translation exists
        $existing = $connection->fetchOne(
            "SELECT name FROM product_translation WHERE product_id = ? AND product_version_id = ? AND language_id = ? LIMIT 1",
            [$productId, $productVersionId, $languageId]
        );

        if ($existing === false) {
            // Insert translation if missing
            $connection->insert('product_translation', [
                'product_id' => $productId,
                'product_version_id' => $productVersionId,
                'language_id' => $languageId,
                'name' => $name,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            echo "✓ Inserted: $productNumber → $name\n";
            $updated++;
        } elseif ($existing === null || $existing === '' ) {
            // Update translation if name is empty/null
            $connection->update('product_translation',
                [
                    'name' => $name,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'product_id' => $productId,
                    'product_version_id' => $productVersionId,
                    'language_id' => $languageId
                ]
            );
            echo "✓ Updated: $productNumber → $name\n";
            $updated++;
        } else {
            // Already has a name, skip
            // echo "Skipped: $productNumber (already has name)\n";
        }
    } catch (\Exception $e) {
        echo "✗ Error for $productNumber: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  Updated: $updated\n";
echo "  Errors: $errors\n";
echo "========================================\n";
