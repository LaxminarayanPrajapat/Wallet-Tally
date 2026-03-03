<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';
$backup_file = '';

// Essential tables that should never be deleted
$essential_tables = ['users', 'transactions', 'categories', 'admins', 'otp_verifications', 'pending_users', 'user_feedback'];

// Handle cleanup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cleanup'])) {
    $database_name = 'wallet_tally';
    
    // Get all tables
    $query = "SELECT TABLE_NAME, TABLE_ROWS 
              FROM information_schema.TABLES 
              WHERE TABLE_SCHEMA = '$database_name'
              ORDER BY TABLE_NAME";
    $result = $conn->query($query);
    
    $tables_to_delete = [];
    
    while ($row = $result->fetch_assoc()) {
        $table_name = $row['TABLE_NAME'];
        $row_count = $row['TABLE_ROWS'];
        
        // Skip essential tables
        if (in_array($table_name, $essential_tables)) {
            continue;
        }
        
        // Check if table is empty and not referenced
        if ($row_count == 0) {
            // Simple check for references in code
            $has_references = false;
            $php_files = array_merge(glob('../*.php'), glob('*.php'));
            
            foreach ($php_files as $file) {
                $content = file_get_contents($file);
                if (stripos($content, $table_name) !== false) {
                    $has_references = true;
                    break;
                }
            }
            
            if (!$has_references) {
                $tables_to_delete[] = $table_name;
            }
        }
    }
    
    if (!empty($tables_to_delete)) {
        // Create backup directory if it doesn't exist
        $backup_dir = '../backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Create backup file
        $backup_file = $backup_dir . '/database_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_content = "-- Database Backup - Unused Tables\n";
        $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables_to_delete as $table) {
            // Get table structure
            $create_table = $conn->query("SHOW CREATE TABLE `$table`");
            if ($create_table && $row = $create_table->fetch_assoc()) {
                $backup_content .= "\n-- Table: $table\n";
                $backup_content .= $row['Create Table'] . ";\n\n";
            }
        }
        
        // Save backup file
        file_put_contents($backup_file, $backup_content);
        
        // Delete tables
        $deleted_count = 0;
        $failed_tables = [];
        
        foreach ($tables_to_delete as $table) {
            $drop_query = "DROP TABLE IF EXISTS `$table`";
            if ($conn->query($drop_query)) {
                $deleted_count++;
            } else {
                $failed_tables[] = $table;
            }
        }
        
        if ($deleted_count > 0) {
            $message = "Successfully deleted $deleted_count unused table(s). Backup saved to: " . basename($backup_file);
        }
        
        if (!empty($failed_tables)) {
            $error = "Failed to delete tables: " . implode(', ', $failed_tables);
        }
    } else {
        $message = "No unused tables found to delete.";
    }
}

// Get current unused tables for display
$database_name = 'wallet_tally';
$query = "SELECT TABLE_NAME, TABLE_ROWS 
          FROM information_schema.TABLES 
          WHERE TABLE_SCHEMA = '$database_name'
          ORDER BY TABLE_NAME";
$result = $conn->query($query);

$unused_tables = [];
while ($row = $result->fetch_assoc()) {
    $table_name = $row['TABLE_NAME'];
    $row_count = $row['TABLE_ROWS'];
    
    if (in_array($table_name, $essential_tables)) {
        continue;
    }
    
    if ($row_count == 0) {
        $has_references = false;
        $php_files = array_merge(glob('../*.php'), glob('*.php'));
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            if (stripos($content, $table_name) !== false) {
                $has_references = true;
                break;
            }
        }
        
        if (!$has_references) {
            $unused_tables[] = [
                'name' => $table_name,
                'rows' => $row_count
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Cleanup - Wallet Tally Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="gradient-text mb-0">Database Cleanup</h2>
                <p class="text-muted mb-0">Remove unused tables and optimize database</p>
            </div>
            <div>
                <a href="analyze_database.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-chart-bar me-2"></i>View Analysis
                </a>
                <a href="dashboard.php" class="btn btn-gradient">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (empty($unused_tables)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Database is clean!</strong> No unused tables found.
        </div>
        <?php else: ?>
        <div class="table-card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Unused Tables Detected</h5>
            </div>
            <div class="card-body">
                <p>The following tables will be deleted:</p>
                <ul>
                    <?php foreach ($unused_tables as $table): ?>
                        <li><strong><?php echo htmlspecialchars($table['name']); ?></strong> (<?php echo $table['rows']; ?> rows)</li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone! A backup will be created before deletion.
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete these tables? A backup will be created first.');">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm_backup" required>
                        <label class="form-check-label" for="confirm_backup">
                            I understand that a backup will be created before deletion
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm_delete" required>
                        <label class="form-check-label" for="confirm_delete">
                            I confirm that I want to delete the unused tables listed above
                        </label>
                    </div>
                    <button type="submit" name="confirm_cleanup" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Delete Unused Tables
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="table-card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Protected Tables</h5>
            </div>
            <div class="card-body">
                <p>The following essential tables will never be deleted:</p>
                <div class="row">
                    <?php foreach ($essential_tables as $table): ?>
                    <div class="col-md-4 mb-2">
                        <span class="badge bg-primary"><i class="fas fa-lock me-1"></i><?php echo $table; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> If you have an old <code>reviews</code> table in your database, you can remove it using the dedicated removal tool.
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
