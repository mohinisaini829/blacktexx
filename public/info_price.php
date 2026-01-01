<?php
// Define destination folder
//$uploadDir = '/usr/home/estdbu/public_html/live/htdocs/custom/plugins/EstBogensportImport/src/Import/';

// Handle form submission
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_POST['vendor'] == 'ross'){ 
        $file   =   $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "❌ Upload failed with error code: " . $file['error'];
        } else {
            $fileType = mime_content_type($file['tmp_name']);
            $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];

            if (!in_array($fileType, $allowedTypes)) {
                $message = "❌ Invalid file type. Please upload a CSV file.";
            } else {
                $fileTmpPath = $_FILES['csv_file']['tmp_name'];
                if (($handle = fopen($fileTmpPath, "r")) !== false) { 
                    // Optional: Read the first row as header
                    fgetcsv($handle);
                    fgetcsv($handle);
                    fgetcsv($handle);
                    fgetcsv($handle);
                    fgetcsv($handle);                    
                    
                    $header     =   [

                                    ];

                    $unsetKeys  =   [8,12,15,20,29,32,33,34,36,37,38,39,50];

                    /* MODiFY HEADER */
/*                    $header[14]  =   'Artikelnummer_kurz_1';
                    $header[19]  =   'color_size';
                    $header[3]   =   'Article Shop Group';
                    $header[17]  =   'child_sku';
                    $header[18]  =   'master_sku';*/
                    /* MODiFY HEADER */

                    echo '<pre />'; print_r($header); die;

                    while (($row = fgetcsv($handle)) !== false) {

                        foreach ($unsetKeys as $unsetKeysKey => $unsetKeysValue) {
                            unset($header[$unsetKeysValue]);
                            unset($row[$unsetKeysValue]);
                        }

                        $data = array_combine($header, $row);
                        //print_r($data); die;

                        $data = array_combine(
                            array_map('trim', array_keys($data)),
                            $data
                        );

                        echo '<pre />'; print_r($data); die;

                    }
                    fclose($handle);
                } else {
                    $message =  "Error: Could not open the uploaded file.";
                }
            }
        }
    }
    die($message);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload CSV File</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            padding: 40px;
        }
        .upload-container {
            background-color: #fff;
            padding: 25px 30px;
            border-radius: 8px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-top: 20px;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 16px;
            cursor: pointer;
            display: block;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            font-size: 14px;
            color: #222;
            padding: 10px;
            border-radius: 5px;
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
        }
        code {
            background-color: #eee;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="upload-container">
    <h2>CSV File Upload</h2>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="create_tierproduct_price.php">
        <div class="form-group">
            <label for="csv_file">Select Vendor:</label>
            <select name="vendor" style="width: 100%;height: 35px;">
                <option value="ross">Falk Ross</option>
                <option value="harko">Harko</option>
                <option value="another">Another</option>
            </select>
        </div>
        <div class="form-group">
            <label for="csv_file">Choose CSV file:</label>
            <input type="file" name="csv_file" id="csv_file" accept=".xls" required>
        </div>
        <button type="submit">Upload</button>
    </form>
</div>

</body>
</html>

