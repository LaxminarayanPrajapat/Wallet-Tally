<?php
/**
 * Email Service Class
 * Handles all email sending functionality with PHPMailer
 */

// Check if composer autoload exists
$autoload_found = false;
$autoload_paths = [
    __DIR__ . '/../vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
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
    __DIR__ . '/../config/email_config.php',
    dirname(__DIR__) . '/config/email_config.php'
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

class EmailService {
    private $mailer;
    
    public function __construct() {
        // Check if email constants are defined
        if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
            throw new Exception("Email configuration not found. Please check email_config.php");
        }
        
        $this->initializePHPMailer();
    }
    
    /**
     * Initialize PHPMailer with SMTP settings
     */
    private function initializePHPMailer() {
        try {
            $this->mailer = new PHPMailer(true);
            
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
            
            // Default sender
            $this->mailer->setFrom(SMTP_USERNAME, 'Wallet Tally Team');
            $this->mailer->addReplyTo(defined('REPLY_TO') ? REPLY_TO : SMTP_USERNAME, 'Wallet Tally Team');
            
        } catch (Exception $e) {
            $this->log('PHPMailer initialization failed: ' . $e->getMessage(), 'error');
            throw new Exception("Email service configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Send OTP verification email
     */
    public function sendOTP($email, $username, $otp) {
        $subject = 'Verify Your Wallet Tally Account';
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3da7f0, #a96bc6); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: white; border: 2px dashed #3da7f0; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #3da7f0; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Wallet Tally!</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                    <p>Thank you for registering with Wallet Tally! To complete your registration, please verify your email address using the code below:</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; color: #666;'>Your Verification Code</p>
                        <div class='otp-code'>" . $otp . "</div>
                    </div>
                    
                    <p><strong>Important:</strong> This code will expire in 10 minutes.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                    
                    <p>Best regards,<br>The Wallet Tally Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($email, $subject, $body);
    }
    
    /**
     * Send PDF report via email
     */
    public function sendPDFReport($email, $username, $pdfPath, $summary) {
        $subject = 'Your Wallet Tally Transaction Report';
        
        $body = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3da7f0, #a96bc6); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .summary { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .summary-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .summary-item:last-child { border-bottom: none; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Transaction Report</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                    <p>Please find attached your transaction report for the period <strong>" . htmlspecialchars($summary['start_date']) . "</strong> to <strong>" . htmlspecialchars($summary['end_date']) . "</strong>.</p>
                    
                    <div class='summary'>
                        <h3 style='margin-top: 0;'>Summary</h3>
                        <div class='summary-item'>
                            <span>Total Income:</span>
                            <span style='color: #27AE60;'>" . $summary['currency'] . " " . number_format($summary['total_income'], 2) . "</span>
                        </div>
                        <div class='summary-item'>
                            <span>Total Expenses:</span>
                            <span style='color: #E74C3C;'>" . $summary['currency'] . " " . number_format($summary['total_expenses'], 2) . "</span>
                        </div>
                        <div class='summary-item'>
                            <span>Net Balance:</span>
                            <span style='color: #3da7f0;'>" . $summary['currency'] . " " . number_format($summary['net_balance'], 2) . "</span>
                        </div>
                    </div>
                    
                    <p>Thank you for using Wallet Tally!</p>
                    
                    <p>Best regards,<br>The Wallet Tally Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($email, $subject, $body, $pdfPath);
    }
    
    /**
     * Generic email sending method
     */
    private function sendEmail($to, $subject, $body, $attachment = null) {
        try {
            return $this->sendWithPHPMailer($to, $subject, $body, $attachment);
        } catch (Exception $e) {
            $this->log('Email sending failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendWithPHPMailer($to, $subject, $body, $attachment = null) {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            // Attachment
            if ($attachment && file_exists($attachment)) {
                $filename = 'Transaction_Report_' . date('Y-m-d') . '.html';
                $this->mailer->addAttachment($attachment, $filename);
                $this->log("Attachment added: $attachment as $filename", 'info');
            } else if ($attachment) {
                $this->log("Attachment file not found: $attachment", 'error');
            }
            
            // Log email attempt
            error_log("Attempting to send OTP email to: " . $to);
            
            // Send
            $result = $this->mailer->send();
            
            if ($result) {
                $this->log("Email sent successfully to: $to", 'info');
                error_log("OTP email sent successfully to: " . $to);
            } else {
                error_log("OTP email failed to send to: " . $to);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("PHPMailer Error: " . $e->getMessage(), 'error');
            error_log("OTP email sending error: " . $e->getMessage());
            error_log("SMTP Error Info: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Log email activities
     */
    private function log($message, $level = 'info') {
        // Always log to error_log for debugging
        error_log("EmailService [$level]: $message");
        
        // Also log to file if logging is enabled
        if (defined('ENABLE_EMAIL_LOGGING') && ENABLE_EMAIL_LOGGING) {
            $logFile = defined('EMAIL_LOG_FILE') ? EMAIL_LOG_FILE : __DIR__ . '/../logs/email.log';
            
            $logDir = dirname($logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
            
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
    
    /**
     * Test email configuration
     */
    public function testConnection() {
        try {
            // Test SMTP connection
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return [
                'success' => true,
                'message' => 'SMTP connection successful',
                'method' => 'PHPMailer'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'method' => 'PHPMailer'
            ];
        }
    }
}
