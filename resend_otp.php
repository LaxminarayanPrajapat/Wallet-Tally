<?php
session_start();
require_once('config/db.php');
require_once('includes/otp_service.php');
require_once('includes/email_service.php');

header('Content-Type: application/json');

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No pending registration found'
    ]);
    exit();
}

$email = $_SESSION['pending_registration']['email'];
$username = $_SESSION['pending_registration']['username'];

try {
    // Resend OTP
    $otpService = new OTPService($conn);
    $result = $otpService->resendOTP($email);
    
    if ($result['success']) {
        // Send OTP email
        try {
            $emailService = new EmailService();
            $emailSent = $emailService->sendOTP($email, $username, $result['otp']);
            
            if ($emailSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'OTP resent successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send email. Please try again.'
                ]);
            }
        } catch (Exception $e) {
            error_log("Email service error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email. Please try again.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    error_log("Resend OTP error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
