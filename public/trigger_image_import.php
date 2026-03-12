<?php
/**
 * Manual trigger for image import
 * Usage: trigger_image_import.php?job_id=JOB_HARKO_20260211124150_6bcf14
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Get job_id from URL
$jobId = $_GET['job_id'] ?? null;

if (!$jobId) {
    die("❌ Please provide job_id in URL: trigger_image_import.php?job_id=YOUR_JOB_ID");
}

echo "<h2>🚀 Triggering Image Import for Job: $jobId</h2>";
echo "<pre>";

// Set job_id for the worker
$_GET['job_id'] = $jobId;

// Include the worker
include __DIR__ . '/process_csv_worker.php';

echo "</pre>";
echo "<h3>✅ Image import process completed!</h3>";
