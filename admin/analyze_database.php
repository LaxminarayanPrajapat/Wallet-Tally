<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// Get all tables in the database
$database_name = 'wallet_tally';
$query = "SELECT TABLE_NAME, TABLE_ROWS 
          FROM information_schema.TABLES 
          WHERE TABLE_SCHEMA = '$database_name'
          ORDER BY TABLE_NAME";
$result = $conn->query($query);

$tables = [];
while ($row = $result->fetch_assoc()) {
    $tables[] = [
        'name' => $row['TABLE_NAME'],
        'rows' => $row['TABLE_ROWS']
    ];
}

// Essential tables that should never be deleted
$essential_tables = ['users', 'transactions', 'categories', 'admins', 'otp_verifications', 'pending_users', 'user_feedback'];

// Analyze each table
$analysis = [];
foreach ($tables as $table) {
    $table_name = $table['name'];
    $is_essential = in_array($table_name, $essential_tables);
    
    // Check if table is referenced in PHP files
    $references = [];
    $search_patterns = [
        "FROM $table_name",
        "INTO $table_name",
        "TABLE $table_name",
        "UPDATE $table_name",
        "\"$table_name\"",
        "'$table_name'"
    ];
    
    // Search in PHP files (simplified - in production, use grep or similar)
    $php_files = glob('../*.php');
    $admin_files = glob('*.php');
    $all_files = array_merge($php_files, $admin_files);
    
    foreach ($all_files as $file) {
        $content = file_get_contents($file);
        foreach ($search_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $references[] = basename($file);
                break;
            }
        }
    }
    
    $references = array_unique($references);
    
    // Determine if table is unused
    $is_unused = !$is_essential && $table['rows'] == 0 && empty($references);
    
    $analysis[] = [
        'name' => $table_name,
        'rows' => $table['rows'],
        'is_essential' => $is_essential,
        'references' => $references,
        'is_unused' => $is_unused,
        'recommendation' => $is_unused ? 'Can be deleted' : ($is_essential ? 'Essential - Keep' : 'In use - Keep')
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Analysis - Wallet Tally Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'includes/admin_styles.php'; ?>
    <style>
        .table-unused {
            background-color: #fff3cd;
        }
        
        .table-essential {
            background-color: #d1ecf1;
        }
        
        .table-inuse {
            background-color: #d4edda;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="gradient-text mb-0">Database Analysis</h2>
                <p class="text-muted mb-0">Analyze database tables and their usage</p>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Analysis Summary:</strong> Found <?php echo count($tables); ?> tables in database.
            <?php 
            $unused_count = count(array_filter($analysis, function($t) { return $t['is_unused']; }));
            if ($unused_count > 0) {
                echo "<br><strong>$unused_count unused table(s) detected</strong> that can be safely removed.";
            } else {
                echo "<br>All tables are in use or essential.";
            }
            ?>
        </div>
        
        <div class="table-card">
            <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Table Name</th>
                                <th>Row Count</th>
                                <th>Status</th>
                                <th>Referenced In</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis as $item): ?>
                            <tr class="<?php 
                                if ($item['is_unused']) echo 'table-unused';
                                elseif ($item['is_essential']) echo 'table-essential';
                                else echo 'table-inuse';
                            ?>">
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><?php echo number_format($item['rows']); ?></td>
                                <td>
                                    <?php if ($item['is_essential']): ?>
                                        <span class="badge bg-primary">Essential</span>
                                    <?php elseif ($item['is_unused']): ?>
                                        <span class="badge bg-warning text-dark">Unused</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Use</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($item['references'])): ?>
                                        <span class="text-muted">No references found</span>
                                    <?php else: ?>
                                        <small><?php echo implode(', ', array_slice($item['references'], 0, 3)); ?>
                                        <?php if (count($item['references']) > 3): ?>
                                            <br>+ <?php echo count($item['references']) - 3; ?> more
                                        <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $item['recommendation']; ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($unused_count > 0): ?>
        <div class="card mt-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Unused Tables Detected</h5>
            </div>
            <div class="card-body">
                <p>The following tables appear to be unused and can be safely removed:</p>
                <ul>
                    <?php foreach ($analysis as $item): ?>
                        <?php if ($item['is_unused']): ?>
                            <li><strong><?php echo htmlspecialchars($item['name']); ?></strong> (<?php echo $item['rows']; ?> rows)</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> Before deleting any tables, make sure to create a backup!
                </div>
                <a href="cleanup_database.php" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-2"></i>Proceed to Cleanup
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Legend</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="p-3 table-essential rounded">
                            <strong>Essential Tables</strong><br>
                            <small>Core tables required for application functionality</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 table-inuse rounded">
                            <strong>In Use Tables</strong><br>
                            <small>Tables with data or code references</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 table-unused rounded">
                            <strong>Unused Tables</strong><br>
                            <small>Empty tables with no code references</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
