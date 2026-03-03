<?php
session_start();
require_once('config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if transaction ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID not provided']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['id'];

// Fetch transaction details
$stmt = $conn->prepare("
    SELECT t.*, c.name as category_name 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.id = ? AND t.user_id = ?
");

$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit();
}

$transaction = $result->fetch_assoc();

// Return transaction data
echo json_encode([
    'success' => true,
    'transaction' => [
        'id' => $transaction['id'],
        'type' => $transaction['type'],
        'category_name' => $transaction['category_name'],
        'amount' => $transaction['amount'],
        'description' => $transaction['description']
    ]
]); 