<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

// ===============================
// BASIC SETUP
// ===============================
$message = '';
$vendorCategories = [];
$categoryParents = [];
$categoryNames = [];
$categoryTree = [];
$tempFileName = ''; // will store uploaded file for second submit

// ===============================
// READ DB CONFIG (.env.local)
// ===============================
$envFile = __DIR__ . '/../.env.local';
$dbHost = 'localhost';
$dbName = '';
$dbUser = '';
$dbPass = '';

if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        if (strpos($line, 'DATABASE_URL=') === 0) {
            $url = trim(substr($line, 13));
            $url = preg_replace('#^mysql://#', '', $url);
            $at = strrpos($url, '@');
            $up = substr($url, 0, $at);
            $hp = substr($url, $at + 1);
            [$dbUser, $dbPass] = explode(':', $up, 2);
            [$dbHost, $dbName] = explode('/', $hp, 2);
        }
    }
}

// ===============================
// FETCH SHOPWARE CATEGORIES (RECURSIVE)
// ===============================
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("
        WITH RECURSIVE tree AS (
            SELECT id, parent_id FROM category WHERE parent_id IS NULL
            UNION ALL
            SELECT c.id, c.parent_id
            FROM category c
            INNER JOIN tree t ON c.parent_id = t.id
        )
        SELECT c.id, c.parent_id, ct.name
        FROM tree t
        JOIN category c ON c.id = t.id
        JOIN category_translation ct ON ct.category_id = c.id
        WHERE ct.language_id = (
            SELECT language_id FROM locale WHERE code = 'en-GB' LIMIT 1
        )
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = bin2hex($row['id']);
        $parent = $row['parent_id'] ? bin2hex($row['parent_id']) : null;
        $categoryParents[$id] = $parent;
        $categoryNames[$id] = $row['name'];
    }

    foreach ($categoryParents as $id => $parent) {
        $categoryTree[$parent][] = $id;
    }

} catch (Throwable $e) {
    $message = 'Shopware category error: ' . $e->getMessage();
}

// ===============================
// CATEGORY DROPDOWN FUNCTION
// ===============================
function renderCategoryOptions($parent, $tree, $names, $level = 0) {
    if (!isset($tree[$parent])) return;
    foreach ($tree[$parent] as $id) {
        echo '<option value="'.$id.'">'
            . str_repeat('— ', $level)
            . htmlspecialchars($names[$id])
            . '</option>';
        renderCategoryOptions($id, $tree, $names, $level + 1);
    }
}

// ===============================
// LOAD VENDOR CATEGORIES (ON LOAD CATEGORIES BUTTON)
// ===============================



if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['load_categories'])
    && isset($_FILES['vendor_file'])
) {

    $vendor = $_POST['vendor'] ?? null;
    $vendorCategories = [];

    $fileTmp       = $_FILES['vendor_file']['tmp_name'];
    $originalName  = basename($_FILES['vendor_file']['name']);

    // ===============================
    // SAVE TEMP FILE
    // ===============================
    $tempDir = __DIR__ . '/uploads/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $tempFileName = uniqid('vendor_') . '_' . $originalName;
    move_uploaded_file($fileTmp, $tempDir . $tempFileName);

    // ===============================
    // NEWWAVE (JSON)
    // ===============================
    if ($vendor === 'newwave') {

        if (!file_exists($tempDir . $tempFileName) || filesize($tempDir . $tempFileName) === 0) {
            $message = "Uploaded file is missing or empty.";
        } else {

            $json = json_decode(file_get_contents($tempDir . $tempFileName), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $message = "Invalid JSON file: " . json_last_error_msg();
            } elseif (!empty($json['result'])) {

                foreach ($json['result'] as $row) {
                    if (!empty($row['productCategory'][0]['translation']['en'])) {
                        $vendorCategories[] = trim(
                            $row['productCategory'][0]['translation']['en']
                        );
                    }
                }

            } else {
                $message = "No categories found in file.";
            }
        }

    }
    // ===============================
    // ROSS / HARKO (CSV / XLS / XLSX)
    // ===============================
    elseif (in_array($vendor, ['ross', 'harko'], true)) {

        require __DIR__ . '/../vendor/autoload.php';

        $sheet   = IOFactory::load($tempDir . $tempFileName)->getActiveSheet();
        $header  = [];
        $rowCount = 1;

        // Header normalization
        $headerMap = [
            'Kategorie Shop'     => 'Category Shop',
            'Kategorie Original' => 'Category Original',
            'Lieferant'          => 'Supplier',
            'Lieferantennummer'  => 'Supplier Number',
            'Geschlecht'         => 'Gender/Sex',
            'Article Shop Group' => 'Article Shop Group'
        ];

        foreach ($sheet->getRowIterator() as $row) {

            // Ross specific junk rows
            if ($vendor === 'ross' && in_array($rowCount, [1,3,4,5], true)) {
                $rowCount++;
                continue;
            }

            $cells = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cells[] = trim((string)$cell->getCalculatedValue());
            }

            // HEADER ROW (first non-empty)
            if (empty($header)) {
                $header = array_map('trim', $cells);
                $rowCount++;
                continue;
            }

            // Skip empty rows
            if (count(array_filter($cells)) === 0) {
                $rowCount++;
                continue;
            }

            // Pad header if needed
            if (count($cells) > count($header)) {
                for ($i = count($header) + 1; $i <= count($cells); $i++) {
                    $header[] = 'Unknown' . $i;
                }
            }

            if (count($header) !== count($cells)) {
                $rowCount++;
                continue;
            }

            // Combine data
            $data = array_combine(
                array_map(function ($key) use ($headerMap) {
                    return $headerMap[$key] ?? $key;
                }, $header),
                $cells
            );

            // ===============================
            // CATEGORY EXTRACTION
            // ===============================
            //echo $data['Category Shop'];die;
           
            if ($vendor === 'ross' && !empty($data['Category Shop'])) {
                // echo "<pre>Vendor: ".$vendor."</pre>";
                // echo "<pre>Header: "; print_r($header); echo "</pre>";
                // echo "<pre>Cells: "; print_r($cells); echo "</pre>";
                // echo "<pre>Data: "; print_r($data); echo "</pre>";
                // die('DEBUG END');
                $vendorCategories[] = trim($data['Category Shop']);
               // print_r($vendorCategories);
                //die('ggggggggg');
            }
            //die('hhhhhh');
            if ($vendor === 'harko' && !empty($data['Article Shop Group'])) {
                $vendorCategories[] = trim($data['Article Shop Group']);
            }

            $rowCount++;
        }
        // DEBUG OUTPUT
    // echo '<pre>';
    // print_r($vendorCategories);
    // echo '</pre>';
    // die;
    }

    // ===============================
    // FINAL CLEANUP
    // ===============================
    $vendorCategories = array_values(
        array_unique(
            array_filter($vendorCategories)
        )
    );

    
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor → Shopware Category Mapping</title>
    <style>
        body { font-family: Arial; background:#f2f2f2; padding:40px }
        .box { background:#fff; padding:25px; max-width:900px; margin:auto; border-radius:8px }
        h2 { text-align:center }
        .row { display:flex; gap:10px; margin-bottom:10px; }
        input, select { padding:6px; width:100% }
        button { padding:10px; background:#4CAF50; color:#fff; border:none; cursor:pointer }
        button:hover { background:#43a047 }
    </style>
</head>
<body>

<div class="box">
<h2>Vendor → Shopware Category Mapping</h2>

<!-- ======================= -->
<!-- LOAD CATEGORIES FORM -->
<!-- ======================= -->
<form method="POST" enctype="multipart/form-data">
    <label>Vendor</label>
    <select name="vendor" required>
        <option value="ross">Falk Ross</option>
        <option value="harko">Harko</option>
        <option value="newwave">New Wave</option>
    </select>
    <br><br>

    <label>Upload CSV / XLS / XLSX / JSON</label>
    <input type="file" name="vendor_file" required>
    <br><br>

    <button type="submit" name="load_categories">Load Categories</button>
</form>

<hr>

<?php if (!empty($vendorCategories)): ?>
    <!-- ======================= -->
    <!-- MAPPING FORM -->
    <!-- ======================= -->
    <form method="POST" action="create_product.php">
        <input type="hidden" name="vendor" value="<?= htmlspecialchars($vendor) ?>">
        <input type="hidden" name="temp_file" value="<?= htmlspecialchars($tempFileName) ?>">

        <h3>Category Mapping</h3>
        <?php foreach ($vendorCategories as $i => $cat): ?>
            <div class="row">
                <input type="text" name="vendorCategories[<?= $i ?>]" value="<?= htmlspecialchars($cat) ?>" readonly>
                <select name="shopware_category[<?= $i ?>]">
                    <option value="">-- Select Shopware Category --</option>
                    <?php renderCategoryOptions(null, $categoryTree, $categoryNames); ?>
                </select>
            </div>
        <?php endforeach; ?>

        <br>
        <button type="submit">Submit Mapping</button>
    </form>
<?php endif; ?>

<?php if($message): ?>
    <p style="color:red"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

</div>
</body>
</html>
