<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import System - Quick Start</title>
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
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 40px;
        }
        
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .card .icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .card p {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .features {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .features h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .features ul {
            list-style: none;
            padding-left: 0;
        }
        
        .features li {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .features li:last-child {
            border-bottom: none;
        }
        
        .features li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .quick-link {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .quick-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .quick-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Import System</h1>
            <p>Welcome to the Vendor Import System</p>
        </div>
        
        <div class="content">
            <div class="cards">
                <div class="card">
                    <div class="icon">📤</div>
                    <h3>New Import</h3>
                    <p>Start a new product, image, or tier price import</p>
                    <a href="vendor_import.php" class="btn">Start Import</a>
                </div>
                
                <div class="card">
                    <div class="icon">📊</div>
                    <h3>Import Manager</h3>
                    <p>View and manage all your import jobs</p>
                    <a href="import_manager.php" class="btn">View Imports</a>
                </div>
                
                <div class="card">
                    <div class="icon">📖</div>
                    <h3>Documentation</h3>
                    <p>Read the complete documentation</p>
                    <a href="IMPORT_SYSTEM_README.md" class="btn" target="_blank">Read Docs</a>
                </div>
            </div>
            
            <div class="features">
                <h3>✨ Key Features</h3>
                <ul>
                    <li>Three import types: Products, Images, and Tier Prices</li>
                    <li>Queue-based background processing</li>
                    <li>Real-time progress tracking with live updates</li>
                    <li>Configurable batch sizes (10, 25, 50, 100 rows)</li>
                    <li>Category mapping support</li>
                    <li>Detailed logging with timestamps</li>
                    <li>Beautiful and intuitive interface</li>
                    <li>CSV file organization by import type</li>
                    <li>Import history and management</li>
                    <li>Downloadable log files</li>
                </ul>
            </div>
            
            <h3 style="margin-bottom: 15px;">🔗 Quick Links</h3>
            <div class="quick-links">
                <div class="quick-link">
                    <a href="csv-imports/product/">📁 Product CSVs</a>
                </div>
                <div class="quick-link">
                    <a href="csv-imports/images/">📁 Image CSVs</a>
                </div>
                <div class="quick-link">
                    <a href="csv-imports/tierprice/">📁 Tier Price CSVs</a>
                </div>
                <div class="quick-link">
                    <a href="import-logs/">📋 Log Files</a>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h4 style="color: #856404; margin-bottom: 10px;">📝 Getting Started</h4>
                <ol style="color: #856404; padding-left: 20px;">
                    <li>Prepare your CSV file with the correct format</li>
                    <li>Click "Start Import" above</li>
                    <li>Select your import type (Product/Image/Tier Price)</li>
                    <li>Upload your CSV file</li>
                    <li>Configure batch size and category mappings</li>
                    <li>Click "Start Import Process"</li>
                    <li>Monitor progress in real-time</li>
                    <li>View logs for detailed information</li>
                </ol>
            </div>
            
            <div style="margin-top: 20px; padding: 20px; background: #d1ecf1; border-radius: 8px; border-left: 4px solid #17a2b8;">
                <h4 style="color: #0c5460; margin-bottom: 10px;">💡 Sample Files Available</h4>
                <p style="color: #0c5460; margin-bottom: 10px;">We've included sample CSV files to help you get started:</p>
                <ul style="color: #0c5460; padding-left: 20px;">
                    <li><code>csv-imports/product/sample_products.csv</code></li>
                    <li><code>csv-imports/images/sample_images.csv</code></li>
                    <li><code>csv-imports/tierprice/sample_tierprices.csv</code></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
