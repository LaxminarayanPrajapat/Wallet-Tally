<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if transaction ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID is required']);
    exit();
}

$transaction_id = (int)$_GET['id'];

// Fetch transaction details with user information
$query = "SELECT t.*, u.username, u.email, c.name as category_name 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          LEFT JOIN categories c ON t.category_id = c.id 
          WHERE t.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Transaction not found']);
    exit();
}

$transaction = $result->fetch_assoc();

// Format the date
$transaction['created_at'] = date('Y-m-d H:i:s', strtotime($transaction['created_at']));

// Return transaction details
echo json_encode($transaction); 