<?php
require_once('../config/db.php');

// Security check - only allow this to run from admin context
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Access denied. Admin login required.");
}

try {
    echo "<h2>Auto-Increment Reset for Empty Tables</h2>";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
    
    // List of all tables that should have auto-increment reset
    $tables = [
        'users',
        'transactions',
        'categories', 
        'user_feedback',
        'user_warnings',
        'email_logs',
        'otp_verifications',
        'pending_users'
    ];
    
    echo "<h3>Checking and Resetting Auto-Increment Counters:</h3>";
    
    $reset_count = 0;
    $preserved_count = 0;
    
    foreach ($tables as $table) {
        // Check if table exists
        $table_check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($table_check->num_rows == 0) {
            echo "⚠ Table '$table' does not exist - skipping<br>";
            continue;
        }
        
        // Get current record count
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $record_count = $count_result->fetch_assoc()['count'];
        
        // Get current auto-increment value
        $auto_inc_result = $conn->query("SHOW TABLE STATUS LIKE '$table'");
        $table_status = $auto_inc_result->fetch_assoc();
        $current_auto_inc = $table_status['Auto_increment'] ?? 'N/A';
        
        if ($record_count == 0) {
            // Table is empty, reset auto-increment to 1
            $reset_query = "ALTER TABLE `$table` AUTO_INCREMENT = 1";
            if ($conn->query($reset_query)) {
                echo "✅ `$table`: Empty table - reset auto-increment from $current_auto_inc to 1<br>";
                $reset_count++;
            } else {
                echo "❌ `$table`: Failed to reset auto-increment - " . $conn->error . "<br>";
            }
        } else {
            // Table has data, leave auto-increment as is
            echo "📊 `$table`: Has $record_count records - auto-increment preserved at $current_auto_inc<br>";
            $preserved_count++;
        }
    }
    
    echo "<br><div style='background: #e8f5e8; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50;'>";
    echo "<h3 style='color: #2e7d32; margin-top: 0;'>✅ Auto-Increment Reset Completed!</h3>";
    echo "<ul style='color: #2e7d32;'>";
    echo "<li><strong>Tables reset to start from ID = 1:</strong> $reset_count</li>";
    echo "<li><strong>Tables with data preserved:</strong> $preserved_count</li>";
    echo "<li><strong>Next new records will use fresh ID numbers</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // Show detailed status of each table
    echo "<br><h3>Final Table Status:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; background: white;'>";
    echo "<tr style='background: #e3f2fd;'>";
    echo "<th>Table</th><th>Records</th><th>Next ID</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($tables as $table) {
        $table_check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($table_check->num_rows == 0) continue;
        
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $record_count = $count_result->fetch_assoc()['count'];
        
        $auto_inc_result = $conn->query("SHOW TABLE STATUS LIKE '$table'");
        $table_status = $auto_inc_result->fetch_assoc();
        $next_id = $table_status['Auto_increment'] ?? 'N/A';
        
        $status = $record_count == 0 ? 
                 "<span style='color: green;'>✅ Ready for fresh IDs</span>" : 
                 "<span style='color: blue;'>📊 Has data</span>";
        
        $row_color = $record_count == 0 ? 'background: #e8f5e8;' : '';
        
        echo "<tr style='$row_color'>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$record_count</td>";
        echo "<td>$next_id</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><div style='background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;'>";
    echo "<h4 style='color: #1565c0; margin-top: 0;'>🎯 What This Means:</h4>";
    echo "<ul style='color: #1565c0;'>";
    echo "<li><strong>New users</strong> will start with ID = 1</li>";
    echo "<li><strong>New transactions</strong> will start with ID = 1</li>";
    echo "<li><strong>New categories</strong> will start with ID = 1</li>";
    echo "<li><strong>New feedback</strong> will start with ID = 1</li>";
    echo "<li><strong>Admin accounts</strong> are preserved with their existing IDs</li>";
    echo "<li><strong>All tables are optimized</strong> for fresh data entry</li>";
    echo "</ul>";
    echo "</div>";
    
    // Log the operation
    $admin_name = $_SESSION['admin_name'] ?? 'Unknown Admin';
    $log_entry = date('Y-m-d H:i:s') . " - Auto-increment reset performed by: $admin_name - $reset_count tables reset\n";
    
    // Create logs directory if it doesn't exist
    if (!is_dir('../logs')) {
        mkdir('../logs', 0755, true);
    }
    
    file_put_contents('../logs/auto_increment_reset.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    echo "<br><p style='color: #666;'><em>Operation logged to: logs/auto_increment_reset.log</em></p>";
    echo "<p style='color: #666;'><em>You can safely delete this file after use.</em></p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; border-left: 4px solid #f44336;'>";
    echo "<h3 style='color: #c62828; margin-top: 0;'>❌ Auto-Increment Reset Failed</h3>";
    echo "<p style='color: #c62828;'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auto-Increment Reset - Wallet Tally Admin</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1000px; 
            margin: 20px auto; 
            padding: 20px;
            background: #f8f9fa;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #1A237E;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #303F9F;
            color: white;
            text-decoration: none;
        }
        table {
            font-family: monospace;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">← Back to Admin Dashboard</a>
</body>
</html>