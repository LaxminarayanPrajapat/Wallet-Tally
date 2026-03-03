<?php
session_start();
require_once('config/db.php');
require_once('includes/otp_service.php');

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    header('Location: register.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['pending_registration']['email'];
    $inputOTP = $_POST['otp'] ?? '';
    
    // Validate OTP format
    if (strlen($inputOTP) !== 6 || !ctype_digit($inputOTP)) {
        $_SESSION['error_message'] = 'Invalid OTP format';
        header('Location: verify_otp.php');
        exit();
    }
    
    // Validate OTP
    $otpService = new OTPService($conn);
    $result = $otpService->validateOTP($email, $inputOTP);
    
    if ($result['success']) {
        // OTP is valid, move user from pending_users to users table
        try {
            // Get pending user data
            $stmt = $conn->prepare("SELECT * FROM pending_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $pendingUser = $stmt->get_result()->fetch_assoc();
            
            if (!$pendingUser) {
                $_SESSION['error_message'] = 'Registration data not found. Please register again.';
                header('Location: register.php');
                exit();
            }
            
            // Get currency symbol based on currency code
            $currencySymbols = [
                'INR' => '₹',
                'USD' => '$',
                'GBP' => '£',
                'EUR' => '€',
                'JPY' => '¥',
                'AUD' => 'A$',
                'CAD' => 'C$',
                'CHF' => 'Fr',
                'CNY' => '¥',
                'HKD' => 'HK$',
                'SGD' => 'S$',
                'AED' => 'د.إ',
                'SAR' => '﷼',
                'KRW' => '₩',
                'MYR' => 'RM',
                'THB' => '฿',
                'IDR' => 'Rp',
                'PHP' => '₱',
                'VND' => '₫',
                'PKR' => '₨',
                'BDT' => '৳',
                'LKR' => '₨',
                'NPR' => '₨'
            ];
            
            $currencySymbol = $currencySymbols[$pendingUser['currency']] ?? '$';
            
            // Insert into users table
            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, password, country, currency, currency_symbol, profile_picture, dob) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, '2000-01-01')"
            );
            $stmt->bind_param(
                "sssssss",
                $pendingUser['username'],
                $pendingUser['email'],
                $pendingUser['password'],
                $pendingUser['country'],
                $pendingUser['currency'],
                $currencySymbol,
                $pendingUser['profile_picture']
            );
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                // Clean up: delete from pending_users and otp_verifications
                $stmt = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                $otpService->deleteOTP($email);
                
                // Clear session
                unset($_SESSION['pending_registration']);
                
                // Set success message
                $_SESSION['success_message'] = 'Registration successful! Please login to continue.';
                header('Location: login.php');
                exit();
            } else {
                throw new Exception("Failed to create user account");
            }
        } catch (Exception $e) {
            error_log("Registration completion error: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred during registration. Please try again.';
            header('Location: verify_otp.php');
            exit();
        }
    } else {
        // OTP validation failed
        $_SESSION['error_message'] = $result['message'];
        header('Location: verify_otp.php');
        exit();
    }
} else {
    header('Location: verify_otp.php');
    exit();
}
?>
