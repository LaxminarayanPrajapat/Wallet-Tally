<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$feedback_id = isset($input['feedback_id']) ? (int)$input['feedback_id'] : 0;

if ($feedback_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
    exit;
}

// Check if feedback exists and has 5 stars
$check_sql = "SELECT rating FROM user_feedback WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $feedback_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Feedback not found']);
    exit;
}

$feedback = $result->fetch_assoc();
if ($feedback['rating'] != 5) {
    echo json_encode(['success' => false, 'error' => 'Only 5-star reviews can be approved for display']);
    exit;
}

// Update feedback to approved for display
$sql = "UPDATE user_feedback SET display_approved = TRUE WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $feedback_id);

if ($stmt->execute()) {
    // Try to send appreciation email
    $emailSent = false;
    try {
        require_once('includes/feedback_email_service.php');
        $emailService = new FeedbackEmailService($conn);
        $emailSent = $emailService->sendApprovalEmail($feedback_id);
    } catch (Exception $e) {
        // Log the error but don't fail the approval
        error_log("Email sending failed: " . $e->getMessage());
        $emailSent = false;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Feedback approved for display',
        'email_sent' => $emailSent
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to approve feedback: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>