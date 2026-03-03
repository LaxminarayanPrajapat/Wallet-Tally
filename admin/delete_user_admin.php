<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$deletion_reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

if (empty($deletion_reason)) {
    echo json_encode(['success' => false, 'error' => 'Deletion reason is required']);
    exit;
}

// Get user details before deletion
$get_sql = "SELECT username, email FROM users WHERE id = ?";
$get_stmt = $conn->prepare($get_sql);

if (!$get_stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$get_stmt->bind_param("i", $user_id);
$get_stmt->execute();
$result = $get_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$user_data = $result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's transactions FIRST (before categories due to foreign key constraint)
    $stmt1 = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    
    // Delete user's categories AFTER transactions
    $stmt2 = $conn->prepare("DELETE FROM categories WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    
    // Delete user's feedback
    $stmt3 = $conn->prepare("DELETE FROM user_feedback WHERE user_id = ?");
    $stmt3->bind_param("i", $user_id);
    $stmt3->execute();
    
    // Delete user's OTP records
    $stmt4 = $conn->prepare("DELETE FROM otp_verifications WHERE email = ?");
    $stmt4->bind_param("s", $user_data['email']);
    $stmt4->execute();
    
    // Delete user
    $stmt5 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt5->bind_param("i", $user_id);
    $stmt5->execute();
    
    // Check if user was actually deleted
    if ($stmt5->affected_rows === 0) {
        throw new Exception("User deletion failed - no rows affected");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Try to send deletion notification email
    $emailSent = false;
    try {
        require_once('includes/feedback_email_service.php');
        $emailService = new FeedbackEmailService($conn);
        $emailSent = $emailService->sendUserDeletionEmail(
            $user_data['email'],
            $user_data['username'],
            $deletion_reason,
            $user_id  // Pass the user_id before it's deleted
        );
    } catch (Exception $e) {
        // Log the error but don't fail the deletion
        error_log("Email sending failed: " . $e->getMessage());
        $emailSent = false;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'User deleted successfully',
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Failed to delete user: ' . $e->getMessage()]);
}

$conn->close();
?>
