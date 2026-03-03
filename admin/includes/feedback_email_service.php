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

class FeedbackEmailService {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Send appreciation email when feedback is approved for testimonials
     */
    public function sendApprovalEmail($feedback_id) {
        // Check if email constants are defined
        if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
            $this->logEmail('unknown', 'Approval Email', 'SKIPPED: No email config');
            return false;
        }
        
        try {
            // Get feedback and user details
            $sql = "SELECT f.*, u.username, u.email 
                    FROM user_feedback f 
                    JOIN users u ON f.user_id = u.id 
                    WHERE f.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $feedback_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Feedback not found");
            }
            
            $feedback = $result->fetch_assoc();
            $user_name = $feedback['username']; // Just use username
            
            $result = $this->sendEmail(
                $feedback['email'],
                $user_name,
                "🌟 Your Review is Now Featured on Wallet Tally!",
                $this->getApprovalEmailTemplate($user_name, $feedback)
            );
            
            $this->logEmailToDatabase(
                $feedback['email'], 
                $user_name, 
                'appreciation', 
                "🌟 Your Review is Now Featured on Wallet Tally!", 
                $result ? 'SUCCESS' : 'FAILED',
                null,
                $feedback['user_id']
            );
            
            // Also log to file for backward compatibility
            $this->logEmail($feedback['email'], 'Approval Email', $result ? 'SUCCESS' : 'FAILED');
            return $result;
            
        } catch (Exception $e) {
            $this->logEmailToDatabase('unknown', 'Unknown', 'appreciation', 'Approval Email', 'FAILED', $e->getMessage());
            $this->logEmail('unknown', 'Approval Email', 'ERROR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification email when feedback is deleted
     */
    public function sendDeletionEmail($user_email, $user_name, $feedback_text, $rating, $deletion_reason, $user_id = null) {
        // Check if email constants are defined
        if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
            $this->logEmail($user_email, 'Deletion Email', 'SKIPPED: No email config');
            return false;
        }
        
        try {
            $result = $this->sendEmail(
                $user_email,
                $user_name,
                "Regarding Your Wallet Tally Feedback",
                $this->getDeletionEmailTemplate($user_name, $feedback_text, $rating, $deletion_reason)
            );
            
            $this->logEmailToDatabase(
                $user_email, 
                $user_name, 
                'feedback_deletion', 
                "Regarding Your Wallet Tally Feedback", 
                $result ? 'SUCCESS' : 'FAILED',
                null,
                $user_id
            );
            
            $this->logEmail($user_email, 'Deletion Email', $result ? 'SUCCESS' : 'FAILED');
            return $result;
            
        } catch (Exception $e) {
            $this->logEmailToDatabase($user_email, $user_name, 'feedback_deletion', 'Deletion Email', 'FAILED', $e->getMessage(), $user_id);
            $this->logEmail($user_email, 'Deletion Email', 'ERROR: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification email when user account is deleted
     */
    public function sendUserDeletionEmail($user_email, $user_name, $deletion_reason, $user_id = null) {
        // Check if email constants are defined
        if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
            $this->logEmail($user_email, 'User Deletion Email', 'SKIPPED: No email config');
            return false;
        }
        
        try {
            $result = $this->sendEmail(
                $user_email,
                $user_name,
                "Important: Your Wallet Tally Account Has Been Closed",
                $this->getUserDeletionEmailTemplate($user_name, $deletion_reason)
            );
            
            $this->logEmailToDatabase(
                $user_email, 
                $user_name, 
                'user_deletion', 
                "Important: Your Wallet Tally Account Has Been Closed", 
                $result ? 'SUCCESS' : 'FAILED',
                null,
                $user_id
            );
            
            $this->logEmail($user_email, 'User Deletion Email', $result ? 'SUCCESS' : 'FAILED');
            return $result;
            
        } catch (Exception $e) {
            $this->logEmailToDatabase($user_email, $user_name, 'user_deletion', 'User Deletion Email', 'FAILED', $e->getMessage(), $user_id);
            $this->logEmail($user_email, 'User Deletion Email', 'ERROR: ' . $e->getMessage());
            return false;
        }
    }
    private function sendEmail($to_email, $to_name, $subject, $body) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Enable debug output for troubleshooting (disable in production)
            if (defined('EMAIL_DEBUG_MODE') && EMAIL_DEBUG_MODE) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            // Sender info
            $mail->setFrom(SMTP_USERNAME, 'Wallet Tally Team');
            $mail->addReplyTo(defined('REPLY_TO') ? REPLY_TO : SMTP_USERNAME, 'Wallet Tally Team');
            
            // Recipient
            $mail->addAddress($to_email, $to_name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Generate plain text version
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
            
            // Log email attempt
            error_log("Attempting to send feedback email to: " . $to_email);
            
            $result = $mail->send();
            
            if ($result) {
                error_log("Feedback email sent successfully to: " . $to_email);
            } else {
                error_log("Feedback email failed to send to: " . $to_email);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Feedback email sending error: " . $e->getMessage());
            error_log("SMTP Error Info: " . (isset($mail) ? $mail->ErrorInfo : 'No mail object'));
            throw new Exception("Email sending failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get approval email template
     */
    private function getApprovalEmailTemplate($user_name, $feedback) {
        $stars = str_repeat('⭐', $feedback['rating']);
        $feedback_text = htmlspecialchars($feedback['feedback']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1A237E; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .highlight { background: #e3f2fd; padding: 15px; margin: 15px 0; border-left: 4px solid #1976d2; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Congratulations, {$user_name}!</h1>
                    <p>Your review has been selected for our testimonials!</p>
                </div>
                <div class='content'>
                    <p>Dear {$user_name},</p>
                    <p>We're thrilled to let you know that your amazing <strong>{$feedback['rating']}-star review</strong> has been selected to be featured on our homepage testimonials section!</p>
                    <div class='highlight'>
                        <h3>Your Featured Review:</h3>
                        <p><strong>Rating:</strong> {$stars}</p>
                        <p><strong>Review:</strong> \"{$feedback_text}\"</p>
                    </div>
                    <p>Thank you for being an amazing part of the Wallet Tally community!</p>
                    <p>Best regards,<br><strong>The Wallet Tally Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get deletion email template
     */
    private function getDeletionEmailTemplate($user_name, $feedback_text, $rating, $deletion_reason) {
        $stars = str_repeat('⭐', $rating);
        $feedback_text = htmlspecialchars($feedback_text);
        $deletion_reason = htmlspecialchars($deletion_reason);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1A237E; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .highlight { background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; }
                .reason-box { background: #f8d7da; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📝 Regarding Your Wallet Tally Feedback</h1>
                </div>
                <div class='content'>
                    <p>Dear {$user_name},</p>
                    <p>We want to inform you that your recent feedback submission has been removed from our system.</p>
                    <div class='highlight'>
                        <h3>Your Submitted Feedback:</h3>
                        <p><strong>Rating:</strong> {$stars}</p>
                        <p><strong>Review:</strong> \"{$feedback_text}\"</p>
                    </div>
                    <div class='reason-box'>
                        <h3>Reason for Removal:</h3>
                        <p>{$deletion_reason}</p>
                    </div>
                    <p>Thank you for your understanding and for being part of the Wallet Tally community.</p>
                    <p>Best regards,<br><strong>The Wallet Tally Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get user deletion email template
     */
    private function getUserDeletionEmailTemplate($user_name, $deletion_reason) {
        $deletion_reason = htmlspecialchars($deletion_reason);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .reason-box { background: #f8d7da; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; }
                .info-box { background: #d1ecf1; padding: 15px; margin: 15px 0; border-left: 4px solid #17a2b8; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Account Closure Notification</h1>
                    <p>Your Wallet Tally account has been closed</p>
                </div>
                <div class='content'>
                    <p>Dear {$user_name},</p>
                    
                    <p>We regret to inform you that your Wallet Tally account has been permanently closed by our administration team.</p>
                    
                    <div class='reason-box'>
                        <h3>Reason for Account Closure:</h3>
                        <p>{$deletion_reason}</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>What This Means:</h3>
                        <ul>
                            <li>🔒 Your account has been permanently deactivated</li>
                            <li>📊 All your transaction data has been removed</li>
                            <li>💬 All your feedback and reviews have been deleted</li>
                            <li>🚫 You will no longer be able to access Wallet Tally services</li>
                        </ul>
                    </div>
                    
                    <p>If you believe this action was taken in error or if you have any questions about this decision, please contact our support team immediately.</p>
                    
                    <p><strong>Important:</strong> This action is permanent and cannot be undone. All your data has been permanently removed from our systems.</p>
                    
                    <div class='footer'>
                        <p>If you have any questions, please contact us at:<br>
                        <strong>support@wallettally.com</strong></p>
                        <p>Best regards,<br><strong>The Wallet Tally Team</strong></p>
                        <p><small>This is an automated notification. Please do not reply to this email.</small></p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Log email activity to database
     */
    private function logEmailToDatabase($recipient_email, $recipient_name, $email_type, $subject, $status, $error_message = null, $user_id = null) {
        try {
            // Get admin name
            $admin_name = 'System';
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
    
    /**
     * Log email activity to file (backward compatibility)
     */
    private function logEmail($recipient, $subject, $status) {
        $log_file = __DIR__ . '/../../logs/email.log';
        $log_entry = date('Y-m-d H:i:s') . " | TO: {$recipient} | SUBJECT: {$subject} | STATUS: {$status}" . PHP_EOL;
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>