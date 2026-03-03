<?php
require_once('includes/auth_check.php');
require_once('../includes/image_helper.php');

// Get system status
$image_status = ImageHelper::getSystemStatus();
$php_version = phpversion();

// Check all required extensions
$required_extensions = [
    'mysqli' => 'MySQL Database Connection',
    'gd' => 'Image Processing (GD Library)',
    'openssl' => 'SSL/TLS Encryption',
    'curl' => 'HTTP Client Library',
    'mbstring' => 'Multibyte String Functions',
    'json' => 'JSON Processing',
    'session' => 'Session Management',
    'filter' => 'Input Filtering',
    'hash' => 'Hashing Functions'
];

$extension_status = [];
foreach ($required_extensions as $ext => $description) {
    $extension_status[$ext] = [
        'loaded' => extension_loaded($ext),
        'description' => $description
    ];
}

// Check PHP configuration
$php_config = [
    'version' => $php_version,
    'version_ok' => version_compare($php_version, '8.0.0', '>='),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'session_save_path' => session_save_path(),
    'session_save_path_writable' => is_writable(session_save_path())
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Extensions Check - Wallet Tally Admin</title>
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
                <h2 class="gradient-text mb-0">System Extensions Check</h2>
                <p class="text-muted mb-0">PHP Extensions and Configuration Status</p>
            </div>
            <a href="system_info.php" class="btn btn-gradient">
                <i class="fas fa-info-circle me-2"></i>Full System Info
            </a>
        </div>
        
        <!-- PHP Version Status -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-primary me-3">
                                <i class="fab fa-php text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">PHP Version</h5>
                                <h3 class="mb-0 <?php echo $php_config['version_ok'] ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $php_version; ?>
                                    <?php if ($php_config['version_ok']): ?>
                                        <i class="fas fa-check-circle ms-2"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle ms-2"></i>
                                    <?php endif; ?>
                                </h3>
                                <small class="text-muted">
                                    <?php echo $php_config['version_ok'] ? 'Compatible' : 'Requires PHP 8.0+'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-success me-3">
                                <i class="fas fa-server text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Server Status</h5>
                                <h3 class="mb-0 text-success">
                                    Running
                                    <i class="fas fa-check-circle ms-2"></i>
                                </h3>
                                <small class="text-muted">All systems operational</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Required Extensions -->
        <div class="table-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-puzzle-piece me-2"></i>Required PHP Extensions
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Extension</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extension_status as $ext => $info): ?>
                        <tr>
                            <td>
                                <code><?php echo $ext; ?></code>
                            </td>
                            <td><?php echo $info['description']; ?></td>
                            <td>
                                <?php if ($info['loaded']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Loaded
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Missing
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$info['loaded']): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showInstallInstructions('<?php echo $ext; ?>')">
                                        <i class="fas fa-question-circle me-1"></i>How to Install
                                    </button>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i> OK
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- PHP Configuration -->
        <div class="table-card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>PHP Configuration
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Current Value</th>
                            <th>Status</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>memory_limit</code></td>
                            <td><?php echo $php_config['memory_limit']; ?></td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> OK
                                </span>
                            </td>
                            <td>128M or higher recommended</td>
                        </tr>
                        <tr>
                            <td><code>max_execution_time</code></td>
                            <td><?php echo $php_config['max_execution_time']; ?>s</td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> OK
                                </span>
                            </td>
                            <td>30s or higher for reports</td>
                        </tr>
                        <tr>
                            <td><code>upload_max_filesize</code></td>
                            <td><?php echo $php_config['upload_max_filesize']; ?></td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> OK
                                </span>
                            </td>
                            <td>2M minimum for profile pictures</td>
                        </tr>
                        <tr>
                            <td><code>post_max_size</code></td>
                            <td><?php echo $php_config['post_max_size']; ?></td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> OK
                                </span>
                            </td>
                            <td>8M or higher recommended</td>
                        </tr>
                        <tr>
                            <td><code>session_save_path</code></td>
                            <td>
                                <code><?php echo $php_config['session_save_path'] ?: 'Default'; ?></code>
                            </td>
                            <td>
                                <?php if ($php_config['session_save_path_writable']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Writable
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Check Permissions
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>Must be writable for sessions</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Image Processing Status -->
        <?php if (isset($image_status)): ?>
        <div class="table-card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-image me-2"></i>Image Processing Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>GD Library Status:</h6>
                        <p class="<?php echo $image_status['gd_available'] ? 'text-success' : 'text-danger'; ?>">
                            <i class="fas fa-<?php echo $image_status['gd_available'] ? 'check-circle' : 'times-circle'; ?> me-2"></i>
                            <?php echo $image_status['gd_available'] ? 'Available' : 'Not Available'; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Supported Formats:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($image_status['supported_formats'] as $format => $supported): ?>
                                <span class="badge <?php echo $supported ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo strtoupper($format); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function showInstallInstructions(extension) {
            const instructions = {
                'mysqli': 'Install MySQL extension:\n• Ubuntu/Debian: sudo apt-get install php-mysql\n• CentOS/RHEL: sudo yum install php-mysql\n• Windows: Enable in php.ini: extension=mysqli',
                'gd': 'Install GD extension:\n• Ubuntu/Debian: sudo apt-get install php-gd\n• CentOS/RHEL: sudo yum install php-gd\n• Windows: Enable in php.ini: extension=gd',
                'openssl': 'Install OpenSSL extension:\n• Ubuntu/Debian: sudo apt-get install php-openssl\n• CentOS/RHEL: sudo yum install php-openssl\n• Windows: Enable in php.ini: extension=openssl',
                'curl': 'Install cURL extension:\n• Ubuntu/Debian: sudo apt-get install php-curl\n• CentOS/RHEL: sudo yum install php-curl\n• Windows: Enable in php.ini: extension=curl',
                'mbstring': 'Install Multibyte String extension:\n• Ubuntu/Debian: sudo apt-get install php-mbstring\n• CentOS/RHEL: sudo yum install php-mbstring\n• Windows: Enable in php.ini: extension=mbstring',
                'json': 'Install JSON extension:\n• Ubuntu/Debian: sudo apt-get install php-json\n• CentOS/RHEL: sudo yum install php-json\n• Windows: Usually built-in, check php.ini',
                'session': 'Session extension is usually built-in.\nIf missing, reinstall PHP or check compilation options.',
                'filter': 'Filter extension is usually built-in.\nIf missing, reinstall PHP or check compilation options.',
                'hash': 'Hash extension is usually built-in.\nIf missing, reinstall PHP or check compilation options.'
            };
            
            Swal.fire({
                title: `Install ${extension} Extension`,
                text: instructions[extension] || 'Please consult your system administrator or hosting provider.',
                icon: 'info',
                confirmButtonColor: '#1A237E',
                confirmButtonText: 'Got it!'
            });
        }
    </script>
</body>
</html>