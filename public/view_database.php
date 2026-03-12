<?php
/**
 * Database View - View all import jobs from database
 */

require_once __DIR__ . '/db_config.php';

try {
    $db = Database::getConnection();
    $sql = "SELECT * FROM vendor_import_jobs ORDER BY created_at DESC";
    $stmt = $db->query($sql);
    $jobs = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Records - Import Jobs</title>
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
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header-info {
            color: #666;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        
        .status-processing {
            background: #17a2b8;
            color: white;
        }
        
        .status-completed {
            background: #28a745;
            color: white;
        }
        
        .status-failed {
            background: #dc3545;
            color: white;
        }
        
        .icon {
            font-size: 1.2em;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card .label {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 2em;
            font-weight: 600;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Records - Import Jobs</h1>
        <div class="header-info">
            <p>Complete list of all vendor import jobs stored in database</p>
            <p><strong>Table:</strong> vendor_import_jobs | <strong>Total Records:</strong> <?php echo count($jobs); ?></p>
        </div>
        
        <a href="import_manager.php" class="back-btn">← Back to Manager</a>
        
        <?php if (!empty($jobs)): ?>
            <?php
            $totalImports = count($jobs);
            $completedImports = count(array_filter($jobs, fn($j) => $j['status'] === 'completed'));
            $processingImports = count(array_filter($jobs, fn($j) => $j['status'] === 'processing'));
            $failedImports = count(array_filter($jobs, fn($j) => $j['status'] === 'failed'));
            ?>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="label">Total Imports</div>
                    <div class="value"><?php echo $totalImports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Completed</div>
                    <div class="value"><?php echo $completedImports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Processing</div>
                    <div class="value"><?php echo $processingImports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Failed</div>
                    <div class="value"><?php echo $failedImports; ?></div>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Vendor</th>
                            <th>File Name</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Processed</th>
                            <th>Errors</th>
                            <th>Progress</th>
                            <th>Created At</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo $job['id']; ?></td>
                                <td>
                                    <?php 
                                    $icons = [
                                        'product' => '📦',
                                        'images' => '🖼️',
                                        'tierprice' => '💰'
                                    ];
                                    echo $icons[$job['import_type']] ?? '📄';
                                    ?>
                                    <?php echo ucfirst($job['import_type']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($job['vendor_name'] ?? 'N/A'); ?></td>
                                <td title="<?php echo htmlspecialchars($job['file_name']); ?>">
                                    <?php 
                                    $fileName = $job['file_name'];
                                    echo strlen($fileName) > 30 ? substr($fileName, 0, 27) . '...' : $fileName;
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $job['status']; ?>">
                                        <?php echo strtoupper($job['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $job['total_rows']; ?></td>
                                <td><?php echo $job['processed_rows']; ?></td>
                                <td><?php echo $job['error_rows']; ?></td>
                                <td>
                                    <?php 
                                    $percentage = $job['total_rows'] > 0 
                                        ? round(($job['processed_rows'] / $job['total_rows']) * 100) 
                                        : 0;
                                    echo $percentage . '%';
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($job['created_at'])); ?></td>
                                <td><?php echo $job['completed_at'] ? date('Y-m-d H:i', strtotime($job['completed_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No records found</h2>
                <p>Database table is empty. Start your first import!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
