<?php
/**
 * Import Manager - View and manage import jobs
 */

define('QUEUE_DIR', __DIR__ . '/import-queue/');
define('LOG_DIR', __DIR__ . '/import-logs/');

require_once __DIR__ . '/db_config.php';

// Get all jobs from database
$jobs = [];
try {
    $db = Database::getConnection();
    $sql = "SELECT * FROM vendor_import_jobs ORDER BY created_at DESC LIMIT 100";
    $stmt = $db->query($sql);
    $jobs = $stmt->fetchAll();
    
    // Update with latest status from status files
    foreach ($jobs as &$job) {
        $statusFile = QUEUE_DIR . $job['job_id'] . '_status.json';
        if (file_exists($statusFile)) {
            $status = json_decode(file_get_contents($statusFile), true);
            $job['processed_rows'] = $status['processed'] ?? $job['processed_rows'];
            $job['error_rows'] = $status['errors'] ?? $job['error_rows'];
            $job['status'] = $status['status'] ?? $job['status'];
            
            // Update database with latest status
            $updateSql = "UPDATE vendor_import_jobs 
                         SET processed_rows = :processed, 
                             error_rows = :errors, 
                             status = :status
                         WHERE job_id = :job_id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([
                ':processed' => $job['processed_rows'],
                ':errors' => $job['error_rows'],
                ':status' => $job['status'],
                ':job_id' => $job['job_id']
            ]);
        }
    }
    unset($job);
    
} catch (Exception $e) {
    error_log("Failed to load jobs from database: " . $e->getMessage());
    
    // Fallback to file-based loading
    $statusFiles = glob(QUEUE_DIR . '*_status.json');
    foreach ($statusFiles as $statusFile) {
        $status = json_decode(file_get_contents($statusFile), true);
        $jobs[] = [
            'job_id' => $status['job_id'],
            'vendor_name' => null,
            'import_type' => $status['import_type'],
            'file_name' => 'N/A',
            'total_rows' => $status['total'],
            'processed_rows' => $status['processed'],
            'error_rows' => $status['errors'],
            'status' => $status['status'],
            'created_at' => $status['started_at']
        ];
    }
    
    // Sort by created_at descending
    usort($jobs, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header .actions {
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .jobs-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .job-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: box-shadow 0.3s;
        }
        
        .job-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .job-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-processing {
            background: #ffc107;
            color: #000;
        }
        
        .status-completed {
            background: #28a745;
            color: white;
        }
        
        .status-failed {
            background: #dc3545;
            color: white;
        }
        
        .job-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat .label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat .value {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
        }
        
        .job-actions a {
            padding: 8px 15px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .job-actions a:hover {
            background: #5568d3;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Import Manager</h1>
            <p>View and manage all import jobs</p>
            <div class="actions">
                <a href="vendor_import.php" class="btn">+ New Import</a>
                <a href="view_database.php" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">🗄️ View Database</a>
            </div>
        </div>
        
        <div class="jobs-list">
            <?php if (empty($jobs)): ?>
                <div class="empty-state">
                    <h2>No import jobs found</h2>
                    <p>Start your first import to see it here</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div class="job-title">
                                <?php 
                                $icons = [
                                    'product' => '📦',
                                    'images' => '🖼️',
                                    'tierprice' => '💰'
                                ];
                                echo $icons[$job['import_type']] ?? '📄';
                                ?>
                                <?php echo ucfirst($job['import_type']); ?> Import
                                <?php if (!empty($job['vendor_name'])): ?>
                                    <span style="font-size: 0.8em; color: #666; font-weight: normal;">
                                        (<?php echo htmlspecialchars($job['vendor_name']); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge status-<?php echo $job['status']; ?>">
                                <?php echo strtoupper($job['status']); ?>
                            </span>
                        </div>
                        
                        <div style="margin-bottom: 10px; color: #666; font-size: 0.9em;">
                            <strong>📄 File:</strong> <?php echo htmlspecialchars($job['file_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="job-stats">
                            <div class="stat">
                                <div class="label">Total</div>
                                <div class="value"><?php echo $job['total_rows'] ?? $job['total'] ?? 0; ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Processed</div>
                                <div class="value"><?php echo $job['processed_rows'] ?? $job['processed'] ?? 0; ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Errors</div>
                                <div class="value"><?php echo $job['error_rows'] ?? $job['errors'] ?? 0; ?></div>
                            </div>
                            <div class="stat">
                                <div class="label">Progress</div>
                                <div class="value">
                                    <?php 
                                    $total = $job['total_rows'] ?? $job['total'] ?? 0;
                                    $processed = $job['processed_rows'] ?? $job['processed'] ?? 0;
                                    $percentage = $total > 0 ? round(($processed / $total) * 100) : 0;
                                    echo $percentage . '%';
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px; color: #666; font-size: 0.9em;">
                            <strong>Started:</strong> <?php echo $job['created_at'] ?? $job['started_at'] ?? 'N/A'; ?>
                            <?php if (isset($job['completed_at']) && !empty($job['completed_at'])): ?>
                                | <strong>Completed:</strong> <?php echo $job['completed_at']; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-actions">
                            <a href="view_log.php?job_id=<?php echo $job['job_id']; ?>">View Log</a>
                            <?php if ($job['status'] === 'processing'): ?>
                                <a href="import_progress.php?job_id=<?php echo $job['job_id']; ?>" target="_blank">Check Progress</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
