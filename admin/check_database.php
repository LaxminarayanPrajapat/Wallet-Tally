<?php
require_once('includes/auth_check.php');

$db_status = [];
$overall_status = true;

// Test database connection
try {
    require_once('../config/db.php');
    $db_status['connection'] = [
        'status' => 'success',
        'message' => 'Database connection successful'
    ];
    
    // Check database name
    $db_name = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'];
    $db_status['database'] = [
        'status' => 'success',
        'message' => "Connected to database: $db_name"
    ];
    
    // Check required tables
    $required_tables = ['users', 'transactions', 'categories', 'user_feedback', 'admins', 'otp_verifications', 'pending_users'];
    $existing_tables = [];
    $result = $conn->query("SHOW TABLES");
    while($row = $result->fetch_array()) {
        $existing_tables[] = $row[0];
    }
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    if(empty($missing_tables)) {
        $db_status['tables'] = [
            'status' => 'success',
            'message' => 'All required tables exist (' . count($required_tables) . ' tables)'
        ];
    } else {
        $db_status['tables'] = [
            'status' => 'warning',
            'message' => 'Missing tables: ' . implode(', ', $missing_tables)
        ];
        $overall_status = false;
    }
    
    // Check table counts
    $counts = [];
    foreach($required_tables as $table) {
        if(in_array($table, $existing_tables)) {
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            $counts[$table] = $count;
        }
    }
    $db_status['counts'] = [
        'status' => 'info',
        'data' => $counts
    ];
    
    // Check database size
    $size_query = "SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES 
        WHERE table_schema = '$db_name'";
    $size = $conn->query($size_query)->fetch_assoc()['size_mb'];
    $db_status['size'] = [
        'status' => 'info',
        'message' => "Database size: {$size} MB"
    ];
    
} catch(Exception $e) {
    $db_status['connection'] = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
    $overall_status = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Check - Wallet Tally Admin</title>
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
                <h2 class="gradient-text mb-0">Database Connection Check</h2>
                <p class="text-muted mb-0">System database connectivity status</p>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Overall Status -->
        <div class="alert <?php echo $overall_status ? 'alert-success' : 'alert-danger'; ?> mb-4">
            <i class="fas <?php echo $overall_status ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
            <strong><?php echo $overall_status ? 'All Systems Operational' : 'Issues Detected'; ?></strong>
        </div>
        
        <!-- Connection Status -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-database me-2"></i>Connection Status</h5>
                    
                    <?php foreach(['connection', 'database', 'tables', 'size'] as $key): ?>
                        <?php if(isset($db_status[$key])): ?>
                        <div class="alert alert-<?php 
                            echo $db_status[$key]['status'] == 'success' ? 'success' : 
                                ($db_status[$key]['status'] == 'warning' ? 'warning' : 
                                ($db_status[$key]['status'] == 'error' ? 'danger' : 'info')); 
                        ?> mb-3">
                            <i class="fas <?php 
                                echo $db_status[$key]['status'] == 'success' ? 'fa-check-circle' : 
                                    ($db_status[$key]['status'] == 'warning' ? 'fa-exclamation-triangle' : 
                                    ($db_status[$key]['status'] == 'error' ? 'fa-times-circle' : 'fa-info-circle')); 
                            ?> me-2"></i>
                            <?php echo $db_status[$key]['message']; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-table me-2"></i>Table Statistics</h5>
                    
                    <?php if(isset($db_status['counts']['data'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th class="text-end">Row Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($db_status['counts']['data'] as $table => $count): ?>
                                <tr>
                                    <td><i class="fas fa-table me-2 text-muted"></i><?php echo $table; ?></td>
                                    <td class="text-end"><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="table-card mt-4">
            <h5 class="gradient-text mb-3"><i class="fas fa-info-circle me-2"></i>Connection Information</h5>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_NAME']; ?></p>
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>MySQL Extension:</strong> <?php echo extension_loaded('mysqli') ? 'Loaded' : 'Not Loaded'; ?></p>
                    <p><strong>PDO Extension:</strong> <?php echo extension_loaded('pdo_mysql') ? 'Loaded' : 'Not Loaded'; ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Last Check:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <button class="btn btn-sm btn-gradient" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
