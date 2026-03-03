<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit feedback']);
    exit;
}

$user_id = $_SESSION['user_id'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

try {
    // Check if user already has feedback
    $stmt = $conn->prepare("SELECT id FROM user_feedback WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing feedback - updated_at will be automatically updated due to ON UPDATE CURRENT_TIMESTAMP
        $stmt = $conn->prepare("UPDATE user_feedback SET rating = ?, feedback = ? WHERE user_id = ?");
        $stmt->bind_param("isi", $rating, $feedback, $user_id);
    } else {
        // Insert new feedback - both created_at and updated_at will be set to current timestamp
        $stmt = $conn->prepare("INSERT INTO user_feedback (user_id, rating, feedback) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $rating, $feedback);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
    } else {
        throw new Exception('Failed to save feedback: ' . $stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 