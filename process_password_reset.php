<?php
require_once('config/db.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['password_reset_verified']) || !isset($_SESSION['password_reset_email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid password reset session']);
    exit;
}

$email = $_SESSION['password_reset_email'];
$password = trim($_POST['password'] ?? '');

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Password is required']);
    exit;
}

// Validate password strength
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long']);
    exit;
}

if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one uppercase letter']);
    exit;
}

if (!preg_match('/[a-z]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one lowercase letter']);
    exit;
}

if (!preg_match('/\d/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one number']);
    exit;
}

try {
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user's password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found or password update failed']);
        exit;
    }
    
    // Clear remember tokens for security
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Clear password reset session
    unset($_SESSION['password_reset_email']);
    unset($_SESSION['password_reset_username']);
    unset($_SESSION['password_reset_verified']);
    
    // Set success message for login page
    $_SESSION['success_message'] = 'Password updated successfully! You can now login with your new password.';
    
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to update password. Please try again.']);
}
?>