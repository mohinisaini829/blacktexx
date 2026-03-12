<?php
/**
 * Queue Processor - Monitors temp directory for queued jobs and processes them
 * Run this as: php queue_processor.php
 * Or in background: nohup php queue_processor.php > /tmp/queue_processor.log 2>&1 &
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('memory_limit', '4G');
set_time_limit(0);

$queueDir = sys_get_temp_dir();
$logFile = '/tmp/queue_processor_' . date('Ymd') . '.log';

function queueLog($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $msg\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    error_log($message);
}

queueLog("Queue processor started");

while (true) {
    try {
        // Find queued jobs
        $files = glob($queueDir . '/import_queue_*.json');
        
        foreach ($files as $file) {
            try {
                $queueData = json_decode(file_get_contents($file), true);
                if (!$queueData || empty($queueData['job_id'])) {
                    unlink($file);
                    continue;
                }
                
                $jobId = $queueData['job_id'];
                queueLog("Processing queued job: $jobId");
                
                // Run the worker for this job
                $cmd = PHP_BINARY . ' ' . __DIR__ . '/process_csv_worker.php job_id=' . escapeshellarg($jobId);
                $output = shell_exec($cmd . ' 2>&1');
                
                queueLog("Job $jobId output: " . substr($output, -200)); // Last 200 chars
                
                // Remove the queue file after processing
                @unlink($file);
                queueLog("Job $jobId processed, queue file removed");
                
            } catch (Throwable $e) {
                queueLog("Error processing queue file $file: " . $e->getMessage());
                @unlink($file);
            }
        }
        
        // Sleep for 2 seconds before checking again
        sleep(2);
        
    } catch (Throwable $e) {
        queueLog("Queue processor error: " . $e->getMessage());
        sleep(5);
    }
}
?>
