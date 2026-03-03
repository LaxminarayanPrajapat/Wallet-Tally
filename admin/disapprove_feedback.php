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

// Update feedback to not approved for display
$sql = "UPDATE user_feedback SET display_approved = FALSE WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $feedback_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback disapproved for display']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to disapprove feedback']);
}

$stmt->close();
$conn->close();
?>