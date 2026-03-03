<?php
session_start();
require_once('../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID not provided']);
    exit();
}

$transaction_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT id, amount, type, category, description, 
               DATE_FORMAT(date, '%Y-%m-%d %H:%i') as formatted_date
        FROM transactions 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($transaction = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'transaction' => $transaction]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close(); 