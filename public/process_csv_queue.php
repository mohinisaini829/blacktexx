<?php
/**
 * CSV Queue Processor - Process generated product CSV with progress tracking
 * Prevents duplicate products and shows real-time progress
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '4G');
set_time_limit(0);

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;

// Initialize Shopware kernel
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/../vendor/autoload.php';
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
$connection = $container->get(Connection::class);

// Get job_id from parameter
$jobId = $_GET['job_id'] ?? null;

if (!$jobId) {
    die('❌ No job_id provided');
}

// Get job details from database
$pdo = $connection->getNativeConnection();
$sql = "SELECT * FROM vendor_import_jobs WHERE job_id = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die('❌ Job not found');
}

$csvFile = $job['file_path'];
if (!file_exists($csvFile)) {
    die('❌ CSV file not found: ' . $csvFile);
}

// Update job status to processing
$updateSql = "UPDATE vendor_import_jobs SET status = 'processing', started_at = NOW() WHERE job_id = ?";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([$jobId]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Import - <?= htmlspecialchars($jobId) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .job-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .job-info p {
            margin: 5px 0;
            color: #666;
        }
        .progress-container {
            background: #f0f0f0;
            border-radius: 10px;
            height: 40px;
            overflow: hidden;
            margin: 20px 0;
            position: relative;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .stat-box.success h3 { color: #28a745; }
        .stat-box.danger h3 { color: #dc3545; }
        .stat-box.warning h3 { color: #ffc107; }
        .stat-box.info h3 { color: #17a2b8; }
        .stat-box p {
            color: #666;
            font-size: 14px;
        }
        .log {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 10px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-top: 20px;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid transparent;
        }
        .log-entry.success { border-color: #28a745; }
        .log-entry.error { border-color: #dc3545; color: #ff6b6b; }
        .log-entry.skip { border-color: #ffc107; color: #ffd93d; }
        .log-entry.info { border-color: #17a2b8; }
        .complete-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 2px solid #c3e6cb;
            display: none;
        }
        .complete-message.show { display: block; }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Processing Import Job</h1>
        
        <div class="job-info">
            <p><strong>Job ID:</strong> <?= htmlspecialchars($jobId) ?></p>
            <p><strong>Vendor:</strong> <?= htmlspecialchars($job['vendor_name']) ?></p>
            <p><strong>File:</strong> <?= htmlspecialchars(basename($csvFile)) ?></p>
            <p><strong>Total Products:</strong> <?= $job['total_rows'] ?></p>
        </div>

        <div class="progress-container">
            <div class="progress-bar" id="progressBar">
                <span id="progressText">0%</span>
            </div>
        </div>

        <div class="stats">
            <div class="stat-box success">
                <h3 id="processedCount">0</h3>
                <p>Processed</p>
            </div>
            <div class="stat-box info">
                <h3 id="createdCount">0</h3>
                <p>Created</p>
            </div>
            <div class="stat-box warning">
                <h3 id="skippedCount">0</h3>
                <p>Skipped</p>
            </div>
            <div class="stat-box danger">
                <h3 id="errorCount">0</h3>
                <p>Errors</p>
            </div>
        </div>

        <div class="complete-message" id="completeMessage">
            <h2>✅ Import Completed Successfully!</h2>
            <p>All products have been processed.</p>
        </div>

        <div class="log" id="logContainer">
            <div class="log-entry info">Starting import process...</div>
        </div>
    </div>

    <script>
        const jobId = '<?= $jobId ?>';
        let processed = 0;
        let created = 0;
        let skipped = 0;
        let errors = 0;
        const totalRows = <?= $job['total_rows'] ?>;

        function updateProgress() {
            const progress = (processed / totalRows) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressText').innerHTML = 
                progress < 100 
                ? '<span class="spinner"></span> ' + Math.round(progress) + '%'
                : '100% Complete';
            
            document.getElementById('processedCount').textContent = processed;
            document.getElementById('createdCount').textContent = created;
            document.getElementById('skippedCount').textContent = skipped;
            document.getElementById('errorCount').textContent = errors;

            if (processed >= totalRows) {
                document.getElementById('completeMessage').classList.add('show');
            }
        }

        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = 'log-entry ' + type;
            entry.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        // Start processing
        processCSV();

        async function processCSV() {
            try {
                const response = await fetch('process_csv_worker.php?job_id=' + encodeURIComponent(jobId));
                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const {done, value} = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value);
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (!line.trim()) continue;

                        try {
                            const data = JSON.parse(line);
                            
                            if (data.type === 'progress') {
                                processed = data.processed;
                                created = data.created;
                                skipped = data.skipped;
                                errors = data.errors;
                                updateProgress();
                            } else if (data.type === 'log') {
                                addLog(data.message, data.level);
                            } else if (data.type === 'complete') {
                                addLog('✅ Import completed successfully!', 'success');
                                updateProgress();
                            }
                        } catch (e) {
                            console.error('Parse error:', e, line);
                        }
                    }
                }
            } catch (error) {
                addLog('❌ Error: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
