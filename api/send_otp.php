<?php
/**
 * Send OTP API
 * Uses consolidated OTPService and EmailService
 */

require_once('../config/db.php');
require_once('../includes/otp_service.php');
require_once('../includes/email_service.php');
require_once('../includes/utility_functions.php');

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

try {
    if (!isset($data['type'])) {
        throw new Exception('Verification type not specified');
    }

    $type = $data['type'];

    if ($type === 'email') {
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        
        if (!isValidEmail($email)) {
            throw new Exception('Invalid email address');
        }

        // Get username if provided
        $username = $data['username'] ?? 'User';

        // Initialize services
        $otpService = new OTPService($conn);
        $emailService = new EmailService();

        // Create OTP
        $result = $otpService->createOTP($email);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        // Send OTP via email
        $emailResult = $emailService->sendOTP($email, $username, $result['otp']);
        
        if (!$emailResult) {
            throw new Exception('Failed to send OTP email');
        }

        jsonResponse([
            'success' => true, 
            'message' => 'OTP sent successfully to your email'
        ]);

    } elseif ($type === 'phone') {
        $phone = sanitizeInput($data['phone']);
        
        // Initialize OTP service
        $otpService = new OTPService($conn);

        // Create OTP
        $result = $otpService->createOTP($phone);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        // TODO: Integrate SMS service (Twilio, etc.)
        // For now, return success
        jsonResponse([
            'success' => true, 
            'message' => 'OTP sent successfully to your phone',
            'note' => 'SMS integration pending'
        ]);
        
    } else {
        throw new Exception('Invalid verification type');
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false, 
        'message' => $e->getMessage()
    ], 400);
}

$conn->close();
?>
 