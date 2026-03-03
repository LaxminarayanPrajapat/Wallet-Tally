<?php
require_once('includes/auth_check.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$email_status = [];
$overall_status = true;

// Check if email config exists
if(file_exists('../config/email_config.php')) {
    $email_status['config'] = [
        'status' => 'success',
        'message' => 'Email configuration file exists'
    ];
    
    require_once('../config/email_config.php');
    
    // Check if constants are defined
    if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        $email_status['config'] = [
            'status' => 'error',
            'message' => 'Email configuration constants not defined'
        ];
        $overall_status = false;
    }
    
    // Check PHPMailer
    if(file_exists('../vendor/autoload.php')) {
        require_once('../vendor/autoload.php');
        
        $email_status['phpmailer'] = [
            'status' => 'success',
            'message' => 'PHPMailer library is installed'
        ];
        
        // Test SMTP connection
        try {
            
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->Timeout = 30;
            
            // Try to connect
            if($mail->smtpConnect()) {
                $email_status['smtp'] = [
                    'status' => 'success',
                    'message' => 'SMTP connection successful'
                ];
                $mail->smtpClose();
            } else {
                $email_status['smtp'] = [
                    'status' => 'error',
                    'message' => 'SMTP connection failed'
                ];
                $overall_status = false;
            }
            
        } catch(Exception $e) {
            $email_status['smtp'] = [
                'status' => 'error',
                'message' => 'SMTP Error: ' . $e->getMessage()
            ];
            $overall_status = false;
        }
        
    } else {
        $email_status['phpmailer'] = [
            'status' => 'error',
            'message' => 'PHPMailer library not found. Run: composer require phpmailer/phpmailer'
        ];
        $overall_status = false;
    }
    
} else {
    $email_status['config'] = [
        'status' => 'error',
        'message' => 'Email configuration file not found'
    ];
    $overall_status = false;
}

// Check email service file
if(file_exists('../includes/email_service.php')) {
    $email_status['service'] = [
        'status' => 'success',
        'message' => 'Email service file exists'
    ];
} else {
    $email_status['service'] = [
        'status' => 'warning',
        'message' => 'Email service file not found'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Connection Check - Wallet Tally Admin</title>
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
                <h2 class="gradient-text mb-0">Email Connection Check</h2>
                <p class="text-muted mb-0">SMTP and email service status</p>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Overall Status -->
        <div class="alert <?php echo $overall_status ? 'alert-success' : 'alert-danger'; ?> mb-4">
            <i class="fas <?php echo $overall_status ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
            <strong><?php echo $overall_status ? 'Email System Operational' : 'Email System Issues Detected'; ?></strong>
        </div>
        
        <!-- Email Status -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-envelope me-2"></i>Email System Status</h5>
                    
                    <?php foreach($email_status as $key => $status): ?>
                    <div class="alert alert-<?php 
                        echo $status['status'] == 'success' ? 'success' : 
                            ($status['status'] == 'warning' ? 'warning' : 'danger'); 
                    ?> mb-3">
                        <i class="fas <?php 
                            echo $status['status'] == 'success' ? 'fa-check-circle' : 
                                ($status['status'] == 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle'); 
                        ?> me-2"></i>
                        <strong><?php echo ucfirst($key); ?>:</strong> <?php echo $status['message']; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-cog me-2"></i>Configuration Details</h5>
                    
                    <?php if(defined('SMTP_HOST')): ?>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>SMTP Host:</strong></td>
                            <td><?php echo htmlspecialchars(SMTP_HOST); ?></td>
                        </tr>
                        <tr>
                            <td><strong>SMTP Port:</strong></td>
                            <td><?php echo htmlspecialchars(SMTP_PORT); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Encryption:</strong></td>
                            <td><?php echo strtoupper(SMTP_ENCRYPTION); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td><?php echo htmlspecialchars(SMTP_USERNAME); ?></td>
                        </tr>
                        <tr>
                            <td><strong>From Email:</strong></td>
                            <td><?php echo htmlspecialchars(defined('FROM_EMAIL') ? FROM_EMAIL : 'Not defined'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>From Name:</strong></td>
                            <td><?php echo htmlspecialchars(defined('FROM_NAME') ? FROM_NAME : 'Not defined'); ?></td>
                        </tr>
                    </table>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Email configuration not loaded
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="table-card mt-4">
            <h5 class="gradient-text mb-3"><i class="fas fa-info-circle me-2"></i>System Information</h5>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>PHP mail() function:</strong> <?php echo function_exists('mail') ? 'Available' : 'Not Available'; ?></p>
                    <p><strong>OpenSSL:</strong> <?php echo extension_loaded('openssl') ? 'Loaded' : 'Not Loaded'; ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Socket Support:</strong> <?php echo function_exists('fsockopen') ? 'Available' : 'Not Available'; ?></p>
                    <p><strong>cURL:</strong> <?php echo extension_loaded('curl') ? 'Loaded' : 'Not Loaded'; ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Last Check:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <button class="btn btn-sm btn-gradient" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
        
        <?php if(!$overall_status): ?>
        <div class="table-card mt-4">
            <h5 class="gradient-text mb-3"><i class="fas fa-wrench me-2"></i>Troubleshooting Steps</h5>
            <ol>
                <li>Verify SMTP credentials in <code>config/email_config.php</code></li>
                <li>Check if PHPMailer is installed: <code>composer require phpmailer/phpmailer</code></li>
                <li>Ensure firewall allows outbound connections on SMTP port</li>
                <li>Verify SMTP host and port are correct</li>
                <li>Check if 2FA or app passwords are required for your email provider</li>
                <li>Review email logs in <code>logs/</code> directory</li>
            </ol>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
