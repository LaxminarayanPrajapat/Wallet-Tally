<?php
require_once('config/db.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['password_reset_email'])) {
    echo json_encode(['success' => false, 'error' => 'No password reset session found']);
    exit;
}

$email = $_SESSION['password_reset_email'];
$otp = trim($_POST['otp'] ?? '');

if (empty($otp)) {
    echo json_encode(['success' => false, 'error' => 'Verification code is required']);
    exit;
}

if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid 6-digit code']);
    exit;
}

try {
    // Verify OTP
    $stmt = $conn->prepare("SELECT otp, expires_at FROM otp_verifications WHERE email = ? AND purpose = 'password_reset' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'No verification code found. Please request a new one.']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    // Check if OTP has expired
    if (strtotime($row['expires_at']) < time()) {
        echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please request a new one.']);
        exit;
    }
    
    // Check if OTP matches
    if ($row['otp'] !== $otp) {
        echo json_encode(['success' => false, 'error' => 'Invalid verification code. Please try again.']);
        exit;
    }
    
    // OTP verified successfully
    $_SESSION['password_reset_verified'] = true;
    
    // Delete used OTP
    $stmt = $conn->prepare("DELETE FROM otp_verifications WHERE email = ? AND purpose = 'password_reset'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Verification successful']);
    
} catch (Exception $e) {
    error_log("Password reset verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Verification failed. Please try again.']);
}
?>