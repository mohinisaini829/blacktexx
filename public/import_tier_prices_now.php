<?php
/**
 * Direct Tier Price Import from CSV
 */

$csvFile = __DIR__ . '/csv-imports/tierprice/harko_product_tier_price_import_090226.csv';

if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile\n");
}

require_once __DIR__ . '/db_config.php';
$db = Database::getConnection();

echo "Starting tier price import...\n";
echo "CSV File: $csvFile\n\n";

$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle, 0, ';');
echo "Header: " . implode(', ', $header) . "\n\n";

$inserted = 0;
$updated = 0;
$errors = 0;
$rowNum = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $rowNum++;
    
    try {
        $tierPriceId = $row[0];
        $productId = $row[1];
        $ruleId = $row[2];
        $priceNet = floatval($row[3]);
        $priceGross = floatval($row[4]);
        $quantityStart = intval($row[5]);
        $quantityEnd = !empty($row[6]) ? intval($row[6]) : null;
        
        // Create price JSON
        $priceJson = json_encode([[
            'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
            'net' => $priceNet,
            'gross' => $priceGross,
            'linked' => false
        ]]);
        
        // Insert/Update query - Note: table has both version_id and product_version_id
        $sql = "INSERT INTO product_price 
                (id, version_id, product_id, product_version_id, rule_id, price, quantity_start, quantity_end, created_at) 
                VALUES 
                (UNHEX(:id), UNHEX('0fa91ce3e96a4bc2be4bd9ce752c3425'), UNHEX(:product_id), UNHEX('0fa91ce3e96a4bc2be4bd9ce752c3425'), UNHEX(:rule_id), :price, :qty_start, :qty_end, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    price = VALUES(price), 
                    quantity_start = VALUES(quantity_start), 
                    quantity_end = VALUES(quantity_end)";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':id' => $tierPriceId,
            ':product_id' => $productId,
            ':rule_id' => $ruleId,
            ':price' => $priceJson,
            ':qty_start' => $quantityStart,
            ':qty_end' => $quantityEnd
        ]);
        
        if ($stmt->rowCount() > 0) {
            $inserted++;
        }
        
        if ($rowNum % 50 == 0) {
            echo "Progress: $rowNum rows processed, $inserted inserted...\n";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "Error on row $rowNum: " . $e->getMessage() . "\n";
        if ($errors > 10) {
            echo "Too many errors, stopping...\n";
            break;
        }
    }
}

fclose($handle);

echo "\n=== Import Completed ===\n";
echo "Total rows: $rowNum\n";
echo "Inserted/Updated: $inserted\n";
echo "Errors: $errors\n";
