<?php
// Check if composer autoload exists
$autoload_found = false;
$autoload_paths = [
    __DIR__ . '/../../vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/vendor/autoload.php',
    dirname(__DIR__) . '/../vendor/autoload.php'
];

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $autoload_found = true;
        break;
    }
}

if (!$autoload_found) {
    throw new Exception("Composer autoload not found. Please run 'composer install'.");
}

// Load email configuration
$config_found = false;
$config_paths = [
    __DIR__ . '/../../config/email_config.php',
    dirname(dirname(__DIR__)) . '/config/email_config.php',
    dirname(__DIR__) . '/../config/email_config.php'
];

foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $config_found = true;
        break;
    }
}

if (!$config_found) {
    throw new Exception("Email configuration file not found.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class WarningEmailService {
    private $mailer;
    private $conn;
    
    public function __construct($database_connection = null) {
        $this->mailer = new PHPMailer(true);
        $this->conn = $database_connection;
        $this->setupMailer();
    }
    
    private function setupMailer() {
        try {
            // Check if email constants are defined
            if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
                throw new Exception("Email configuration not found. Please check email_config.php");
            }
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Enable debug output for troubleshooting (disable in production)
            if (defined('EMAIL_DEBUG_MODE') && EMAIL_DEBUG_MODE) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            // Sender info
            $this->mailer->setFrom(SMTP_USERNAME, 'Wallet Tally Admin Team');
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Mailer setup error: " . $e->getMessage());
            throw new Exception("Email service configuration error: " . $e->getMessage());
        }
    }
    
    public function sendWarningEmail($user, $category, $description, $admin_name) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['username']);
            
            $this->mailer->Subject = 'IMPORTANT: Account Warning - Wallet Tally';
            
            $emailBody = $this->generateWarningEmailTemplate($user, $category, $description, $admin_name);
            $this->mailer->Body = $emailBody;
            
            // Plain text version
            $this->mailer->AltBody = $this->generatePlainTextWarning($user, $category, $description, $admin_name);
            
            // Log email attempt
            error_log("Attempting to send warning email to: " . $user['email']);
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Warning email sent successfully to: " . $user['email']);
                $this->logEmailToDatabase(
                    $user['email'], 
                    $user['username'], 
                    'warning', 
                    'IMPORTANT: Account Warning - Wallet Tally', 
                    'SUCCESS',
                    null,
                    $user['id']
                );
            } else {
                error_log("Warning email failed to send to: " . $user['email']);
                $this->logEmailToDatabase(
                    $user['email'], 
                    $user['username'], 
                    'warning', 
                    'IMPORTANT: Account Warning - Wallet Tally', 
                    'FAILED',
                    'Email service returned false',
                    $user['id']
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Warning email sending error: " . $e->getMessage());
            error_log("SMTP Error Info: " . $this->mailer->ErrorInfo);
            
            $this->logEmailToDatabase(
                $user['email'], 
                $user['username'], 
                'warning', 
                'IMPORTANT: Account Warning - Wallet Tally', 
                'FAILED',
                $e->getMessage(),
                $user['id']
            );
            
            return false;
        }
    }
    
    private function generateWarningEmailTemplate($user, $category, $description, $admin_name) {
        $current_date = date('F d, Y');
        $current_time = date('g:i A T');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Account Warning - Wallet Tally</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
                .header .icon { font-size: 48px; margin-bottom: 15px; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #dee2e6; }
                .warning-box { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .warning-box h3 { color: #856404; margin-top: 0; }
                .violation-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #dc3545; }
                .final-warning { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .final-warning h3 { color: #721c24; margin-top: 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .button { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #1A237E, #1B5E20); color: white; text-decoration: none; border-radius: 5px; margin: 15px 0; }
                .contact-info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .timestamp { color: #6c757d; font-size: 12px; text-align: right; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>⚠️</div>
                    <h1>ACCOUNT WARNING</h1>
                    <p style='margin: 0; font-size: 16px;'>Important Notice Regarding Your Wallet Tally Account</p>
                </div>
                
                <div class='content'>
                    <h2 style='color: #dc3545; margin-top: 0;'>Dear {$user['username']},</h2>
                    
                    <p>We are writing to inform you of a serious concern regarding your Wallet Tally account activity. Our administrative team has identified behavior that violates our Terms of Service and community guidelines.</p>
                    
                    <div class='violation-details'>
                        <h3 style='color: #dc3545; margin-top: 0;'><i>📋</i> Violation Details</h3>
                        <p><strong>Violation Type:</strong> {$category}</p>
                        <p><strong>Description:</strong></p>
                        <p style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 3px solid #dc3545;'>{$description}</p>
                        <p><strong>Reviewed By:</strong> {$admin_name}</p>
                        <p><strong>Date:</strong> {$current_date} at {$current_time}</p>
                    </div>
                    
                    <div class='warning-box'>
                        <h3><i>⚠️</i> This is an Official Warning</h3>
                        <p>This warning serves as formal notice that your account activity has been flagged for violating our community standards. We take these matters seriously to maintain a safe and trustworthy environment for all users.</p>
                    </div>
                    
                    <div class='final-warning'>
                        <h3><i>🚨</i> CRITICAL: Final Warning Notice</h3>
                        <p><strong>Please be advised that this is your final warning.</strong></p>
                        <p><strong>Any future violations of our Terms of Service, community guidelines, or similar inappropriate behavior will result in the immediate and permanent deletion of your Wallet Tally account.</strong></p>
                        <p>This includes, but is not limited to:</p>
                        <ul>
                            <li>Submitting false or misleading feedback</li>
                            <li>Violating our Terms of Service</li>
                            <li>Engaging in inappropriate behavior</li>
                            <li>Spam or excessive activity</li>
                            <li>Data misuse or manipulation</li>
                            <li>Any other actions that compromise system integrity</li>
                        </ul>
                        <p><strong>Account deletion will be permanent and irreversible, resulting in the loss of all your financial data, transaction history, and account access.</strong></p>
                    </div>
                    
                    <h3 style='color: #1A237E;'>What You Need to Do:</h3>
                    <ol>
                        <li><strong>Review our Terms of Service:</strong> Familiarize yourself with our community guidelines and acceptable use policies.</li>
                        <li><strong>Modify your behavior:</strong> Ensure all future activity complies with our standards.</li>
                        <li><strong>Contact us if needed:</strong> If you have questions about this warning or need clarification, reach out to our support team.</li>
                    </ol>
                    
                    <div class='contact-info'>
                        <h4 style='margin-top: 0; color: #1A237E;'>Need Help or Have Questions?</h4>
                        <p>If you believe this warning was issued in error or if you need clarification about our policies, please contact our support team:</p>
                        <p><strong>Email:</strong> support@wallettally.com<br>
                        <strong>Subject:</strong> Account Warning Appeal - User ID {$user['id']}</p>
                    </div>
                    
                    <p>We value you as a member of the Wallet Tally community and hope to continue providing you with excellent service. Please ensure your future interactions align with our community standards.</p>
                    
                    <p>Thank you for your immediate attention to this matter.</p>
                    
                    <p><strong>Best regards,</strong><br>
                    The Wallet Tally Administrative Team<br>
                    <em>Committed to maintaining a safe and trustworthy platform</em></p>
                    
                    <div class='timestamp'>
                        Warning issued on {$current_date} at {$current_time}<br>
                        Reference ID: WRN-{$user['id']}-" . time() . "
                    </div>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from Wallet Tally Administrative System.<br>
                    Please do not reply directly to this email.</p>
                    <p style='font-size: 12px; color: #999;'>
                        © " . date('Y') . " Wallet Tally. All rights reserved.<br>
                        This email was sent regarding account ID: {$user['id']}
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function generatePlainTextWarning($user, $category, $description, $admin_name) {
        $current_date = date('F d, Y');
        $current_time = date('g:i A T');
        
        return "
ACCOUNT WARNING - WALLET TALLY
==============================

Dear {$user['username']},

We are writing to inform you of a serious concern regarding your Wallet Tally account activity. Our administrative team has identified behavior that violates our Terms of Service and community guidelines.

VIOLATION DETAILS:
- Violation Type: {$category}
- Description: {$description}
- Reviewed By: {$admin_name}
- Date: {$current_date} at {$current_time}

⚠️ THIS IS AN OFFICIAL WARNING ⚠️

This warning serves as formal notice that your account activity has been flagged for violating our community standards.

🚨 CRITICAL: FINAL WARNING NOTICE 🚨

Please be advised that this is your final warning.

ANY FUTURE VIOLATIONS OF OUR TERMS OF SERVICE, COMMUNITY GUIDELINES, OR SIMILAR INAPPROPRIATE BEHAVIOR WILL RESULT IN THE IMMEDIATE AND PERMANENT DELETION OF YOUR WALLET TALLY ACCOUNT.

Account deletion will be permanent and irreversible, resulting in the loss of all your financial data, transaction history, and account access.

WHAT YOU NEED TO DO:
1. Review our Terms of Service and community guidelines
2. Modify your behavior to comply with our standards
3. Contact us if you need clarification

NEED HELP?
If you believe this warning was issued in error or need clarification:
Email: support@wallettally.com
Subject: Account Warning Appeal - User ID {$user['id']}

We value you as a member of the Wallet Tally community and hope to continue providing you with excellent service.

Best regards,
The Wallet Tally Administrative Team

Warning issued on {$current_date} at {$current_time}
Reference ID: WRN-{$user['id']}-" . time() . "

---
This is an automated message from Wallet Tally Administrative System.
© " . date('Y') . " Wallet Tally. All rights reserved.
Account ID: {$user['id']}
        ";
    }
    
    /**
     * Log email activity to database
     */
    private function logEmailToDatabase($recipient_email, $recipient_name, $email_type, $subject, $status, $error_message = null, $user_id = null) {
        if (!$this->conn) {
            return; // No database connection available
        }
        
        try {
            // Get admin name
            $admin_name = 'System Administrator';
            if (isset($_SESSION['user_id'])) {
                $admin_stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
                if ($admin_stmt) {
                    $admin_stmt->bind_param("i", $_SESSION['user_id']);
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    if ($admin_result->num_rows > 0) {
                        $admin_name = $admin_result->fetch_assoc()['username'];
                    }
                }
            }
            
            // Create table if not exists
            $this->conn->query("CREATE TABLE IF NOT EXISTS email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_email VARCHAR(255) NOT NULL,
                recipient_name VARCHAR(255) DEFAULT NULL,
                email_type ENUM('appreciation', 'warning', 'feedback_deletion', 'user_deletion') NOT NULL,
                subject VARCHAR(500) NOT NULL,
                status ENUM('SUCCESS', 'FAILED', 'PENDING') DEFAULT 'PENDING',
                error_message TEXT DEFAULT NULL,
                admin_name VARCHAR(100) DEFAULT 'System',
                user_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Insert log
            $log_stmt = $this->conn->prepare("INSERT INTO email_logs (recipient_email, recipient_name, email_type, subject, status, error_message, admin_name, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($log_stmt) {
                $log_stmt->bind_param("sssssssi", $recipient_email, $recipient_name, $email_type, $subject, $status, $error_message, $admin_name, $user_id);
                $log_stmt->execute();
            }
        } catch (Exception $e) {
            // Don't fail email sending if logging fails
            error_log("Email logging failed: " . $e->getMessage());
        }
    }
}
?>