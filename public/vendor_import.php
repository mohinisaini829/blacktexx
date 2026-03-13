<?php
/**
 * Vendor Import - Main Interface
 * Handles Product, Images, and Tier Price imports with queue processing
 */
// Increase memory and execution time for large JSON uploads
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '600');
set_time_limit(600);
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Shopware to get categories
use Doctrine\DBAL\Connection;
use Shopware\Core\Kernel;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new Dotenv();
    $dotenv->loadEnv(__DIR__ . '/../.env.local');
    
    $classLoader = require __DIR__ . '/../vendor/autoload.php';
    $appEnv = $_ENV['APP_ENV'] ?? 'dev';
    $debug = ($_ENV['APP_DEBUG'] ?? '1') !== '0';
    
    $pluginLoader = null;
    if (EnvironmentHelper::getVariable('COMPOSER_PLUGIN_LOADER', false)) {
        $pluginLoader = new ComposerPluginLoader($classLoader, null);
    }
    
    $kernel = KernelFactory::create(
        environment: $appEnv,
        debug: $debug,
        classLoader: $classLoader,
        pluginLoader: $pluginLoader
    );
    
    $kernel->boot();
    $container = $kernel->getContainer();
    
    // Get categories - Load all non-root categories for dropdown
    $categoryList = [];
    $criteria = new Criteria();
    // Don't filter by parent - load all categories so user can select
    $context = Context::createDefaultContext();
    $categoryRepository = $container->get('category.repository');
    $result = $categoryRepository->search($criteria, $context);
    
    foreach ($result as $category) {
        // Skip root category  
        if ($category->getParentId() && $category->getName()) {
            $categoryList[$category->getId()] = $category->getName();
        }
    }
    
} catch (Exception $e) {
    // If Shopware loading fails, continue without categories
    $categoryList = [];
    error_log("Failed to load categories: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Import System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .option-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .option-card {
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .option-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .option-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        
        .option-card input[type="radio"] {
            display: none;
        }
        
        .option-card .icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .option-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .option-card p {
            color: #666;
            font-size: 0.9em;
        }
        
        .upload-form {
            display: none;
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .upload-form.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input[type="file"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }
        
        .form-group input[type="file"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 12px;
            background: white;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .file-input-label.has-file {
            border-style: solid;
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .category-mapping {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .category-mapping h4 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .mapping-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .submit-btn:hover {
            transform: scale(1.02);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .progress-container {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
        }
        
        .progress-container.active {
            display: block;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .progress-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .progress-stat {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .progress-stat .label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .progress-stat .value {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
        }
        
        .progress-stat.success .value {
            color: #28a745;
        }
        
        .progress-stat.error .value {
            color: #dc3545;
        }
        
        .log-container {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
        }
        
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry.success {
            color: #28a745;
        }
        
        .log-entry.error {
            color: #dc3545;
        }
        
        .log-entry.info {
            color: #17a2b8;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Vendor Import System</h1>
            <p>Import Products, Images, and Tier Prices with ease</p>
        </div>
        
        <div class="content">
            <form id="importForm" method="POST" enctype="multipart/form-data">
                <div class="option-cards">
                    <label class="option-card">
                        <input type="radio" name="import_type" value="product" required>
                        <div class="icon">📦</div>
                        <h3>Product Import</h3>
                        <p>Import product data from CSV</p>
                    </label>
                    
                    <label class="option-card">
                        <input type="radio" name="import_type" value="images" required>
                        <div class="icon">🖼️</div>
                        <h3>Image Import</h3>
                        <p>Import product images</p>
                    </label>
                    
                    <label class="option-card">
                        <input type="radio" name="import_type" value="tierprice" required>
                        <div class="icon">💰</div>
                        <h3>Tier Price Import</h3>
                        <p>Import tier pricing data</p>
                    </label>
                </div>
                
                <div id="uploadForm" class="upload-form">
                    <div class="form-group">
                        <label>Vendor Name</label>
                        <select name="vendor_name" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1em;">
                            <option value="">Select Vendor</option>
                            <option value="harko">HARKO</option>
                            <option value="ross">ROSS</option>
                            <option value="newwave">NEWWAVE</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Select CSV File</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="csv_file" id="csvFile" accept=".csv,.xls,.xlsx,.json" required>
                            <label for="csvFile" class="file-input-label" id="fileLabel">
                                📁 Click to select CSV file
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Batch Size (rows per batch)</label>
                        <select name="batch_size">
                            <option value="10">10 rows</option>
                            <option value="25" selected>25 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                    </div>
                    
                    <div class="category-mapping" id="categoryMappingSection" style="display: none;">
                        <h4>Category Mapping (Optional)</h4>
                        
                        <button type="button" id="loadCategoriesBtn" class="add-mapping-btn" style="margin-bottom: 15px; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                            📥 Load Categories from CSV
                        </button>
                        
                        <div id="categoryMappings">
                            <div class="mapping-row">
                                <input type="text" class="form-control" name="vendorCategories[]" placeholder="Vendor Category Name">
                                <select name="shopware_category[]" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1em;">
                                    <option value="">Select Shopware Category</option>
                                    <?php foreach ($categoryList as $catId => $catName): ?>
                                        <option value="<?php echo htmlspecialchars($catId); ?>">
                                            <?php echo htmlspecialchars($catName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="add-mapping-btn" onclick="addMappingRow()" style="margin-top: 10px; padding: 8px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">+ Add Mapping</button>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        Start Import Process
                    </button>
                </div>
            </form>
            
            <div id="progressContainer" class="progress-container">
                <h3 style="margin-bottom: 20px;">Import Progress</h3>
                
                <div class="progress-bar">
                    <div id="progressBarFill" class="progress-bar-fill">0%</div>
                </div>
                
                <div class="progress-info">
                    <div class="progress-stat">
                        <div class="label">Total Rows</div>
                        <div class="value" id="totalRows">0</div>
                    </div>
                    <div class="progress-stat success">
                        <div class="label">Processed</div>
                        <div class="value" id="processedRows">0</div>
                    </div>
                    <div class="progress-stat error">
                        <div class="label">Errors</div>
                        <div class="value" id="errorRows">0</div>
                    </div>
                </div>
                
                <h4 style="margin-bottom: 10px;">Log Messages</h4>
                <div id="logContainer" class="log-container">
                    <div class="log-entry info">Waiting to start...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle option card selection
        document.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                // Ensure the radio input is checked
                const radio = this.querySelector('input[name="import_type"]');
                if (radio) radio.checked = true;
                document.getElementById('uploadForm').classList.add('active');
                updateCategoryMappingSection();
            });
        });

        // Also update on radio change (keyboard navigation or programmatic)
        document.querySelectorAll('input[name="import_type"]').forEach(radio => {
            radio.addEventListener('change', updateCategoryMappingSection);
        });

        function updateCategoryMappingSection() {
            const importTypeRadio = document.querySelector('input[name="import_type"]:checked');
            const importType = importTypeRadio ? importTypeRadio.value : '';
            const categorySection = document.getElementById('categoryMappingSection');
            const loadCategoriesBtn = document.getElementById('loadCategoriesBtn');
            if (importType === 'product') {
                categorySection.style.display = 'block';
                loadCategoriesBtn.disabled = false;
                loadCategoriesBtn.style.opacity = '1';
                loadCategoriesBtn.style.cursor = '';
                loadCategoriesBtn.style.pointerEvents = '';
                categorySection.style.visibility = 'visible';
            } else {
                categorySection.style.display = 'none';
                categorySection.style.visibility = 'hidden';
                loadCategoriesBtn.disabled = true;
                loadCategoriesBtn.style.opacity = '0.5';
                loadCategoriesBtn.style.cursor = 'not-allowed';
                loadCategoriesBtn.style.pointerEvents = 'none';
            }
        }

        // On page load, ensure correct state
        document.addEventListener('DOMContentLoaded', updateCategoryMappingSection);
        
        // Handle file input
        document.getElementById('csvFile').addEventListener('change', function(e) {
            const label = document.getElementById('fileLabel');
            if (this.files.length > 0) {
                label.textContent = '✅ ' + this.files[0].name;
                label.classList.add('has-file');
            } else {
                label.textContent = '📁 Click to select CSV file';
                label.classList.remove('has-file');
            }
        });
        
        // Load categories button click handler
        document.getElementById('loadCategoriesBtn').addEventListener('click', async function() {
            const fileInput = document.getElementById('csvFile');
            const vendorSelect = document.querySelector('select[name="vendor_name"]');
            const importType = document.querySelector('input[name="import_type"]:checked').value;
            
            // Prevent category loading for non-product imports
            if (importType !== 'product') {
                alert('Category mapping is only available for Product imports');
                return;
            }
            
            if (!fileInput.files.length) {
                alert('Please select a CSV file first');
                return;
            }
            
            if (!vendorSelect.value) {
                alert('Please select a vendor first');
                return;
            }
            
            this.disabled = true;
            this.textContent = '⏳ Loading categories...';
            
            await loadCategoryValues(fileInput.files[0]);
            
            this.disabled = false;
            this.textContent = '📥 Load Categories from CSV';
        });
        
        // Load category values from CSV
        async function loadCategoryValues(file) {
            // Double-check import type before proceeding
            const importType = document.querySelector('input[name="import_type"]:checked').value;
            if (importType !== 'product') {
                alert('❌ Category mapping is only available for Product imports!');
                return;
            }
            
            const formData = new FormData();
            formData.append('csv_file', file);
            
            try {
                const response = await fetch('read_csv_category_values.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get the response text first to see what we're getting
                const text = await response.text();
                
                // Try to parse as JSON
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON. Response text:', text);
                    throw new Error('Invalid JSON response from server. Check console for details.');
                }
                
                if (result.success && result.categories.length > 0) {
                    // Clear existing mappings
                    const container = document.getElementById('categoryMappings');
                    container.innerHTML = '';
                    
                    // Add a row for each unique category value found
                    result.categories.forEach(categoryValue => {
                        const row = document.createElement('div');
                        row.className = 'mapping-row';
                        row.innerHTML = `
                            <input type="text" class="form-control" name="vendorCategories[]" value="${categoryValue}" readonly 
                                   style="background: #f8f9fa; cursor: not-allowed; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1em;">
                            <select name="shopware_category[]" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1em;">
                                <option value="">Select Shopware Category</option>
                                <?php foreach ($categoryList as $catId => $catName): ?>
                                    <option value="<?php echo htmlspecialchars($catId); ?>">
                                        <?php echo htmlspecialchars($catName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        `;
                        container.appendChild(row);
                    });
                    
                    alert(`✅ Loaded ${result.categories.length} unique categories from CSV`);
                } else if (result.success && result.categories.length === 0) {
                    // No categories found in CSV - this is optional for product imports
                    // Just silently skip the mapping (user can add manual mappings if needed)
                    console.log('No category column found in CSV file. Category mapping is optional.');
                } else {
                    alert('❌ Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading category values:', error);
                alert('❌ Error loading categories: ' + error.message);
            }
        }
        
        // Add category mapping row
        function addMappingRow() {
            const container = document.getElementById('categoryMappings');
            const row = document.createElement('div');
            row.className = 'mapping-row';
            row.innerHTML = `
                <input type="text" class="form-control" name="vendorCategories[]" placeholder="Vendor Category Name">
                <select name="shopware_category[]" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1em;">
                    <option value="">Select Shopware Category</option>
                    <?php foreach ($categoryList as $catId => $catName): ?>
                        <option value="<?php echo htmlspecialchars($catId); ?>">
                            <?php echo htmlspecialchars($catName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            `;
            container.appendChild(row);
        }
        
        // Handle form submission
        document.getElementById('importForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.submit-btn');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            try {
                const response = await fetch('import_processor.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Check if direct processing (vendor CSV generated) with job_id
                    if (result.direct_processing && result.job_id) {
                        // Show inline progress UI
                        document.getElementById('progressContainer').classList.add('active');
                        document.getElementById('progressBarFill').style.width = '0%';
                        document.getElementById('progressBarFill').textContent = '0%';
                        document.getElementById('totalRows').textContent = '0';
                        document.getElementById('processedRows').textContent = '0';
                        document.getElementById('errorRows').textContent = '0';
                        
                        // Start queue processing
                        startQueueProcessing(result.job_id);
                    } else if (result.job_id) {
                        // Check if this is an images import - trigger background processing
                        const importTypeRadio = document.querySelector('input[name="import_type"]:checked');
                        if (importTypeRadio && importTypeRadio.value === 'images') {
                            // Trigger image import in background
                            fetch('trigger_image_import.php?job_id=' + encodeURIComponent(result.job_id))
                                .then(r => console.log('Image import triggered:', result.job_id))
                                .catch(e => console.error('Failed to trigger image import:', e));
                        }
                        // Legacy queue processing
                        document.getElementById('progressContainer').classList.add('active');
                        startProgressTracking(result.job_id);
                    } else {
                        // Show success message
                        const msg = result.message || 'Import completed successfully!';
                        alert('✅ ' + msg);
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Start Import Process';
                    }
                } else {
                    // Show error message
                    const errMsg = result.message || 'An error occurred during import'; ``
                    alert('❌ ' + errMsg);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Start Import Process';
                }
            } catch (error) {
                alert('Error submitting form: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Start Import Process';
            }
        });
        
        // Track progress
        let progressInterval;
        
        // New function for queue processing with worker
        async function startQueueProcessing(jobId) {
            try {
                const response = await fetch('process_csv_worker.php?job_id=' + encodeURIComponent(jobId));
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let gotCompleteEvent = false;
                
                while (true) {
                    const {done, value} = await reader.read();
                    if (done) break;
                    
                    buffer += decoder.decode(value, {stream: true});
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep incomplete line
                    
                    for (const line of lines) {
                        if (!line.trim()) continue;
                        
                        try {
                            const data = JSON.parse(line);
                            
                            if (data.type === 'progress') {
                                const percentage = data.total > 0 ? Math.round((data.processed / data.total) * 100) : 0;
                                document.getElementById('progressBarFill').style.width = percentage + '%';
                                document.getElementById('progressBarFill').textContent = percentage + '%';
                                document.getElementById('totalRows').textContent = data.total || 0;
                                document.getElementById('processedRows').textContent = data.processed || 0;
                                document.getElementById('errorRows').textContent = data.errors || 0;
                            } else if (data.type === 'log') {
                                console.log('[' + data.level + '] ' + data.message);
                            } else if (data.type === 'complete') {
                                gotCompleteEvent = true;
                                alert('✅ Import Completed!\\n\\nProcessed: ' + data.processed + '\\nCreated: ' + data.created + '\\nSkipped: ' + data.skipped + '\\nErrors: ' + data.errors);
                                document.querySelector('.submit-btn').disabled = false;
                                document.querySelector('.submit-btn').textContent = 'Start Import Process';
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e, line);
                        }
                    }
                }

                // Process any trailing buffered line
                if (buffer && buffer.trim()) {
                    try {
                        const data = JSON.parse(buffer.trim());
                        if (data.type === 'complete') {
                            gotCompleteEvent = true;
                            alert('✅ Import Completed!\\n\\nProcessed: ' + data.processed + '\\nCreated: ' + data.created + '\\nSkipped: ' + data.skipped + '\\nErrors: ' + data.errors);
                        }
                    } catch (e) {
                        console.warn('Trailing buffer parse skipped:', e);
                    }
                }

                // Fallback: if stream ended without complete event, verify final status from progress endpoint
                if (!gotCompleteEvent) {
                    try {
                        const statusResponse = await fetch('import_progress.php?job_id=' + encodeURIComponent(jobId));
                        const statusData = await statusResponse.json();
                        if (statusData && (statusData.status === 'completed' || statusData.status === 'failed')) {
                            document.querySelector('.submit-btn').disabled = false;
                            document.querySelector('.submit-btn').textContent = 'Start Import Process';
                            if (statusData.status === 'completed') {
                                alert('✅ Import completed.');
                            } else {
                                alert('⚠️ Import finished with failure status. Please check logs.');
                            }
                        }
                    } catch (statusErr) {
                        console.warn('Status fallback check failed:', statusErr);
                    }
                }
            } catch (error) {
                console.error('Queue processing error:', error);
                alert('Error during import: ' + error.message);
                document.querySelector('.submit-btn').disabled = false;
                document.querySelector('.submit-btn').textContent = 'Start Import Process';
            }
        }
        
        function startProgressTracking(jobId) {
            progressInterval = setInterval(async () => {
                try {
                    const response = await fetch('import_progress.php?job_id=' + jobId);
                    const data = await response.json();
                    
                    updateProgress(data);
                    
                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(progressInterval);
                        document.querySelector('.submit-btn').disabled = false;
                        document.querySelector('.submit-btn').textContent = 'Start Import Process';
                    }
                } catch (error) {
                    console.error('Error fetching progress:', error);
                }
            }, 1000);
        }
        
        function updateProgress(data) {
            const percentage = data.total > 0 ? Math.round((data.processed / data.total) * 100) : 0;
            
            document.getElementById('progressBarFill').style.width = percentage + '%';
            document.getElementById('progressBarFill').textContent = percentage + '%';
            document.getElementById('totalRows').textContent = data.total;
            document.getElementById('processedRows').textContent = data.processed;
            document.getElementById('errorRows').textContent = data.errors;
            
            // Update logs
            if (data.logs && data.logs.length > 0) {
                const logContainer = document.getElementById('logContainer');
                logContainer.innerHTML = '';
                data.logs.slice(-20).forEach(log => {
                    const entry = document.createElement('div');
                    entry.className = 'log-entry ' + log.type;
                    entry.textContent = log.timestamp + ' - ' + log.message;
                    logContainer.appendChild(entry);
                });
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        }
    </script>
</body>
</html>
