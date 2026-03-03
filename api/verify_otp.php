<?php
/**
 * Verify OTP API
 * Uses consolidated OTPService
 */

require_once('../config/db.php');
require_once('../includes/otp_service.php');
require_once('../includes/utility_functions.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (!isset($data['type'])) {
        throw new Exception('Verification type not specified');
    }

    $type = $data['type'];
    $otpService = new OTPService($conn);

    if ($type === 'email') {
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $otp = sanitizeInput($data['otp']);

        if (!isValidEmail($email)) {
            throw new Exception('Invalid email address');
        }

        // Validate OTP
        $result = $otpService->validateOTP($email, $otp);
        
        if ($result['success']) {
            jsonResponse([
                'success' => true, 
                'message' => 'Email verified successfully'
            ]);
        } else {
            throw new Exception($result['message']);
        }
        
    } elseif ($type === 'phone') {
        $phone = sanitizeInput($data['phone']);
        $otp = sanitizeInput($data['otp']);

        // Validate OTP
        $result = $otpService->validateOTP($phone, $otp);
        
        if ($result['success']) {
            jsonResponse([
                'success' => true, 
                'message' => 'Phone verified successfully'
            ]);
        } else {
            throw new Exception($result['message']);
        }
        
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
 