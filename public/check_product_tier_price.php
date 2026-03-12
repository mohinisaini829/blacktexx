<?php
require_once __DIR__ . '/db_config.php';

$productId = $argv[1] ?? '019c4263552470aa822a9e8b88457426';

$db = Database::getConnection();

// Check if product exists
echo "Checking Product ID: $productId\n\n";

$stmt = $db->prepare("SELECT HEX(id) as pid, product_number FROM product WHERE id = UNHEX(?)");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "❌ Product NOT found in database!\n";
    exit(1);
}

echo "✅ Product exists: " . $product['product_number'] . "\n\n";

// Check tier prices
$stmt = $db->prepare("SELECT HEX(id) as tier_id, price, quantity_start, quantity_end, created_at 
                      FROM product_price 
                      WHERE product_id = UNHEX(?) 
                      AND rule_id = UNHEX('0197e3c809b773c591e619aa39e52a29') 
                      ORDER BY quantity_start");
$stmt->execute([$productId]);

$count = 0;
echo "Tier Prices:\n";
echo "------------\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $count++;
    $priceData = json_decode($row['price'], true);
    $gross = $priceData[0]['gross'] ?? 0;
    $net = $priceData[0]['net'] ?? 0;
    
    printf("Tier %d: Qty %d-%s = Net: €%.2f, Gross: €%.2f (Created: %s)\n",
        $count,
        $row['quantity_start'],
        $row['quantity_end'] ?: '∞',
        $net,
        $gross,
        $row['created_at']
    );
}

if ($count == 0) {
    echo "❌ No tier prices found!\n\n";
    
    // Check if this product is in the CSV
    $csvFile = __DIR__ . '/csv-imports/tierprice/harko_product_tier_price_import_090226.csv';
    if (file_exists($csvFile)) {
        echo "Checking CSV file...\n";
        $found = false;
        $handle = fopen($csvFile, 'r');
        fgetcsv($handle, 0, ';'); // Skip header
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (strtoupper($row[1]) === strtoupper($productId)) {
                $found = true;
                echo "✅ Product found in CSV with tier price: Qty {$row[5]}-{$row[6]} = €{$row[4]}\n";
            }
        }
        fclose($handle);
        
        if (!$found) {
            echo "❌ Product NOT in tier price CSV file\n";
        }
    }
} else {
    echo "\n✅ Total tier prices: $count\n";
}
