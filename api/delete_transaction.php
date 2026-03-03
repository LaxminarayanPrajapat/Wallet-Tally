<?php
session_start();
require_once('../config/db.php');
require_once('../includes/auto_increment_helper.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID not provided']);
    exit();
}

$transaction_id = $data['id'];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    
    if ($stmt->execute()) {
        // Reset AUTO_INCREMENT if table is now empty
        resetAutoIncrementIfEmpty($conn, 'transactions');
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to delete transaction");
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close(); 