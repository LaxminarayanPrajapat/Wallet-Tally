<?php
require_once('config/db.php');
require_once('config/email_config.php');
require_once('vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email address is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address']);
    exit;
}

try {
    // Check if email constants are defined
    if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        throw new Exception("Email configuration not found. Please check email_config.php");
    }
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'No account found with this email address']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes expiry
    
    // Store OTP in database (reuse existing otp_verifications table)
    $stmt = $conn->prepare("INSERT INTO otp_verifications (email, otp, expires_at, purpose) VALUES (?, ?, ?, 'password_reset') ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), purpose = VALUES(purpose)");
    $stmt->bind_param("sss", $email, $otp, $expires_at);
    $stmt->execute();
    
    // Send OTP email using constants
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    // Recipients
    $mail->setFrom(SMTP_USERNAME, 'Wallet Tally Team');
    $mail->addAddress($email, $user['username']);
    $mail->addReplyTo(defined('REPLY_TO') ? REPLY_TO : SMTP_USERNAME, 'Wallet Tally Team');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Verification Code - Wallet Tally';
    $mail->Body = getPasswordResetEmailTemplate($user['username'], $otp);
    
    // Log email attempt
    error_log("Attempting to send password reset OTP to: " . $email);
    
    $result = $mail->send();
    
    if ($result) {
        error_log("Password reset OTP sent successfully to: " . $email);
        
        // Store email in session for verification page
        $_SESSION['password_reset_email'] = $email;
        $_SESSION['password_reset_username'] = $user['username'];
        
        echo json_encode(['success' => true, 'message' => 'Verification code sent successfully']);
    } else {
        error_log("Password reset OTP failed to send to: " . $email);
        echo json_encode(['success' => false, 'error' => 'Failed to send verification code. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    error_log("SMTP Error Info: " . (isset($mail) ? $mail->ErrorInfo : 'No mail object'));
    echo json_encode(['success' => false, 'error' => 'Failed to send verification code. Please try again.']);
}

function getPasswordResetEmailTemplate($username, $otp) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1A237E, #2E7D32); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; border-left: 4px solid #1976d2; }
            .otp-code { font-size: 32px; font-weight: bold; color: #1A237E; letter-spacing: 5px; margin: 10px 0; }
            .warning { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
                <p>Wallet Tally Security Code</p>
            </div>
            <div class='content'>
                <p>Hello {$username},</p>
                
                <p>We received a request to reset your Wallet Tally account password. Use the verification code below to proceed with resetting your password:</p>
                
                <div class='otp-box'>
                    <p><strong>Your Verification Code:</strong></p>
                    <div class='otp-code'>{$otp}</div>
                    <p><small>This code will expire in 10 minutes</small></p>
                </div>
                
                <div class='warning'>
                    <p><strong>Security Notice:</strong></p>
                    <ul>
                        <li>🔒 Never share this code with anyone</li>
                        <li>⏰ This code expires in 10 minutes</li>
                        <li>🚫 If you didn't request this, please ignore this email</li>
                        <li>🛡️ Consider changing your password if you suspect unauthorized access</li>
                    </ul>
                </div>
                
                <p>If you didn't request a password reset, please ignore this email. Your account remains secure.</p>
                
                <div class='footer'>
                    <p>Best regards,<br>
                    <strong>The Wallet Tally Team</strong></p>
                    <p><small>This is an automated email. Please do not reply to this message.</small></p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?>