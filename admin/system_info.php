<?php
require_once('includes/auth_check.php');

// Get system information
$sys_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'server_admin' => $_SERVER['SERVER_ADMIN'] ?? 'Unknown',
    'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
    'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
    'error_reporting' => ini_get('error_reporting'),
];

// Check required PHP extensions
$required_extensions = [
    'mysqli' => 'MySQL Database',
    'pdo_mysql' => 'PDO MySQL',
    'mbstring' => 'Multibyte String',
    'openssl' => 'OpenSSL',
    'curl' => 'cURL',
    'gd' => 'GD Graphics',
    'fileinfo' => 'File Information',
    'json' => 'JSON',
    'session' => 'Session'
];

$extensions_status = [];
foreach($required_extensions as $ext => $name) {
    $extensions_status[$name] = extension_loaded($ext);
}

// Get disk space
$disk_free = disk_free_space(".");
$disk_total = disk_total_space(".");
$disk_used = $disk_total - $disk_free;
$disk_usage_percent = ($disk_used / $disk_total) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Information - Wallet Tally Admin</title>
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
                <h2 class="gradient-text mb-0">System Information</h2>
                <p class="text-muted mb-0">Server and PHP configuration details</p>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- PHP Information -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fab fa-php me-2"></i>PHP Configuration</h5>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td><?php echo $sys_info['php_version']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Max Execution Time:</strong></td>
                            <td><?php echo $sys_info['max_execution_time']; ?> seconds</td>
                        </tr>
                        <tr>
                            <td><strong>Memory Limit:</strong></td>
                            <td><?php echo $sys_info['memory_limit']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Upload Max Filesize:</strong></td>
                            <td><?php echo $sys_info['upload_max_filesize']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Post Max Size:</strong></td>
                            <td><?php echo $sys_info['post_max_size']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Display Errors:</strong></td>
                            <td><?php echo $sys_info['display_errors']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-server me-2"></i>Server Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Server Software:</strong></td>
                            <td><?php echo $sys_info['server_software']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Server Name:</strong></td>
                            <td><?php echo $sys_info['server_name']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Server Port:</strong></td>
                            <td><?php echo $sys_info['server_port']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Protocol:</strong></td>
                            <td><?php echo $sys_info['server_protocol']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Document Root:</strong></td>
                            <td><small><?php echo $sys_info['document_root']; ?></small></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- PHP Extensions -->
        <div class="table-card mt-4">
            <h5 class="gradient-text mb-3"><i class="fas fa-puzzle-piece me-2"></i>PHP Extensions</h5>
            <div class="row">
                <?php foreach($extensions_status as $name => $loaded): ?>
                <div class="col-md-3 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas <?php echo $loaded ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> me-2"></i>
                        <span><?php echo $name; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Disk Space -->
        <div class="table-card mt-4">
            <h5 class="gradient-text mb-3"><i class="fas fa-hdd me-2"></i>Disk Space</h5>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Total Space:</strong> <?php echo round($disk_total / 1024 / 1024 / 1024, 2); ?> GB</p>
                    <p><strong>Used Space:</strong> <?php echo round($disk_used / 1024 / 1024 / 1024, 2); ?> GB</p>
                    <p><strong>Free Space:</strong> <?php echo round($disk_free / 1024 / 1024 / 1024, 2); ?> GB</p>
                </div>
                <div class="col-md-8">
                    <p><strong>Disk Usage:</strong></p>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar <?php echo $disk_usage_percent > 90 ? 'bg-danger' : ($disk_usage_percent > 75 ? 'bg-warning' : ''); ?>" 
                             style="width: <?php echo $disk_usage_percent; ?>%; background: linear-gradient(135deg, #1A237E, #1B5E20);">
                            <?php echo round($disk_usage_percent, 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Info -->
        <div class="table-card mt-4">
            <h5 class="gradient-text mb-3"><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Session Save Path:</strong> <?php echo session_save_path(); ?></p>
                    <p><strong>Temp Directory:</strong> <?php echo sys_get_temp_dir(); ?></p>
                </div>
                <div class="col-md-4">
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
