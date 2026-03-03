<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if feedback ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Feedback ID is required']);
    exit();
}

$feedback_id = (int)$_GET['id'];

// Get feedback details with user information
$query = "SELECT f.*, u.username, u.email 
          FROM user_feedback f 
          JOIN users u ON f.user_id = u.id 
          WHERE f.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Feedback not found']);
    exit();
}

$feedback = $result->fetch_assoc();

// Format the date
$feedback['created_at'] = date('Y-m-d H:i:s', strtotime($feedback['created_at']));

// Return feedback data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'feedback' => $feedback
]); 