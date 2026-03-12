<?php
/**
 * Import Progress API - Returns current progress status
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('QUEUE_DIR', __DIR__ . '/import-queue/');
define('LOG_DIR', __DIR__ . '/import-logs/');

if (!isset($_GET['job_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Job ID not specified'
    ]);
    exit;
}

$jobId = $_GET['job_id'];
$statusFile = QUEUE_DIR . $jobId . '_status.json';

if (!file_exists($statusFile)) {
    try {
        require_once __DIR__ . '/db_config.php';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT job_id, import_type, vendor_name, status, total_rows, processed_rows, error_rows FROM vendor_import_jobs WHERE job_id = ? LIMIT 1");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            echo json_encode([
                'success' => true,
                'job_id' => $job['job_id'],
                'import_type' => $job['import_type'],
                'vendor_name' => $job['vendor_name'],
                'status' => $job['status'],
                'total' => (int)$job['total_rows'],
                'processed' => (int)$job['processed_rows'],
                'errors' => (int)$job['error_rows'],
                'logs' => []
            ]);
            exit;
        }
    } catch (Throwable $e) {
        // fall through
    }
    echo json_encode([
        'success' => false,
        'message' => 'Job not found'
    ]);
    exit;
}

$status = json_decode(file_get_contents($statusFile), true);

// Read recent log entries
$logFile = LOG_DIR . $jobId . '.log';
$logs = [];
if (file_exists($logFile)) {
    $logContent = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentLogs = array_slice($logContent, -50); // Last 50 entries
    
    foreach ($recentLogs as $logLine) {
        $type = 'info';
        if (strpos($logLine, 'ERROR') !== false) {
            $type = 'error';
        } elseif (strpos($logLine, 'SUCCESS') !== false) {
            $type = 'success';
        }
        
        // Extract timestamp and message
        $parts = explode(' - ', $logLine, 2);
        $logs[] = [
            'timestamp' => isset($parts[0]) ? trim($parts[0]) : date('Y-m-d H:i:s'),
            'message' => isset($parts[1]) ? trim($parts[1]) : $logLine,
            'type' => $type
        ];
    }
}

$status['logs'] = $logs;
echo json_encode($status);
