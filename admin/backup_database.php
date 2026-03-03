<?php
/**
 * Database Backup Script
 * Creates a backup of the database before cleanup operations
 */

require_once('../config/db.php');

// Set content type
header('Content-Type: text/html; charset=utf-8');

$backupCreated = false;
$backupFile = '';
$error = '';

if (isset($_POST['create_backup'])) {
    try {
        // Get database credentials from connection
        $dbHost = 'localhost'; // Default, adjust if needed
        $dbName = 'wallet_tally';
        
        // Create backups directory if it doesn't exist
        $backupDir = __DIR__ . '/../backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Generate backup filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/wallet_tally_backup_' . $timestamp . '.sql';
        
        // Create backup using PHP
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $sqlDump = "-- Wallet Tally Database Backup\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Database: wallet_tally\n\n";
        $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlDump .= "START TRANSACTION;\n";
        $sqlDump .= "SET time_zone = \"+00:00\";\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $createTableResult = $conn->query("SHOW CREATE TABLE `$table`");
            $createTableRow = $createTableResult->fetch_assoc();
            
            $sqlDump .= "\n-- Table structure for table `$table`\n";
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $createTableRow['Create Table'] . ";\n\n";
            
            // Get table data
            $dataResult = $conn->query("SELECT * FROM `$table`");
            if ($dataResult->num_rows > 0) {
                $sqlDump .= "-- Dumping data for table `$table`\n";
                
                while ($row = $dataResult->fetch_assoc()) {
                    $sqlDump .= "INSERT INTO `$table` VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $sqlDump .= implode(', ', $values);
                    $sqlDump .= ");\n";
                }
                $sqlDump .= "\n";
            }
        }
        
        $sqlDump .= "COMMIT;\n";
        
        // Write to file
        if (file_put_contents($backupFile, $sqlDump)) {
            $backupCreated = true;
            $backupFile = basename($backupFile);
        } else {
            $error = "Failed to write backup file";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #17a2b8;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
        }
        .danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 10px 0;
        }
        button {
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <h1>💾 Database Backup</h1>
    
    <?php if ($backupCreated): ?>
        
        <div class="section">
            <div class="success">
                <h2>✅ Backup Created Successfully!</h2>
                <p><strong>Backup File:</strong> <code><?php echo htmlspecialchars($backupFile); ?></code></p>
                <p><strong>Location:</strong> <code>Wallet Tally/backups/</code></p>
                <p><strong>Size:</strong> <?php echo number_format(filesize(__DIR__ . '/../backups/' . $backupFile) / 1024, 2); ?> KB</p>
            </div>
            
            <div class="info">
                <h3>📋 Next Steps:</h3>
                <ol>
                    <li>Verify the backup file exists in the backups directory</li>
                    <li>Optionally download the backup file for safekeeping</li>
                    <li>Proceed with the database cleanup</li>
                    <li>Keep this backup for at least 7 days</li>
                </ol>
            </div>
            
            <button class="btn-success" onclick="window.location.href='execute_database_cleanup.php'">
                Proceed to Cleanup
            </button>
            <button class="btn-primary" onclick="window.location.href='analyze_database_cleanup.php'">
                View Analysis Report
            </button>
        </div>
        
    <?php elseif ($error): ?>
        
        <div class="section">
            <div class="danger">
                <h2>❌ Backup Failed</h2>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
            </div>
            
            <div class="info">
                <h3>Alternative Backup Methods:</h3>
                <p><strong>Using phpMyAdmin:</strong></p>
                <ol>
                    <li>Open phpMyAdmin</li>
                    <li>Select the "wallet_tally" database</li>
                    <li>Click the "Export" tab</li>
                    <li>Choose "Quick" export method</li>
                    <li>Click "Go" to download the backup</li>
                </ol>
                
                <p><strong>Using command line:</strong></p>
                <code>mysqldump -u username -p wallet_tally > backup.sql</code>
            </div>
            
            <form method="POST">
                <button type="submit" name="create_backup" class="btn-primary">Try Again</button>
            </form>
        </div>
        
    <?php else: ?>
        
        <div class="section">
            <div class="info">
                <h2>📦 Create Database Backup</h2>
                <p>Before performing any database cleanup operations, it's essential to create a complete backup of your database.</p>
                
                <h3>What will be backed up:</h3>
                <ul>
                    <li>All database tables and their structures</li>
                    <li>All data in every table</li>
                    <li>Indexes and constraints</li>
                </ul>
                
                <h3>Backup location:</h3>
                <p>The backup will be saved to: <code>Wallet Tally/backups/wallet_tally_backup_[timestamp].sql</code></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="create_backup" class="btn-primary">Create Backup Now</button>
            </form>
        </div>
        
        <div class="section">
            <div class="info">
                <h3>💡 Alternative Backup Methods</h3>
                
                <p><strong>Method 1: Using phpMyAdmin</strong></p>
                <ol>
                    <li>Open phpMyAdmin in your browser</li>
                    <li>Select the "wallet_tally" database from the left sidebar</li>
                    <li>Click on the "Export" tab at the top</li>
                    <li>Select "Quick" export method and "SQL" format</li>
                    <li>Click "Go" to download the backup file</li>
                </ol>
                
                <p><strong>Method 2: Using Command Line</strong></p>
                <code>mysqldump -u root -p wallet_tally > wallet_tally_backup_<?php echo date('Y-m-d'); ?>.sql</code>
                <p><small>Replace 'root' with your MySQL username if different</small></p>
            </div>
        </div>
        
    <?php endif; ?>
    
</body>
</html>
<?php
$conn->close();
?>
