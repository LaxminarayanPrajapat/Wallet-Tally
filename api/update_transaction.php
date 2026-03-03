<?php
session_start();
require_once('../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if all required fields are present
if (!isset($_POST['transaction_id']) || !isset($_POST['amount']) || !isset($_POST['type']) || !isset($_POST['category'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$transaction_id = $_POST['transaction_id'];
$user_id = $_SESSION['user_id'];
$amount = floatval($_POST['amount']);
$type = $_POST['type'];
$category = $_POST['category'];
$description = isset($_POST['description']) ? $_POST['description'] : '';

try {
    // First verify that the transaction belongs to the user
    $verify = $conn->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
    $verify->bind_param("ii", $transaction_id, $user_id);
    $verify->execute();
    $result = $verify->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Transaction not found or unauthorized");
    }
    
    // Update the transaction
    $stmt = $conn->prepare("
        UPDATE transactions 
        SET amount = ?, 
            type = ?, 
            category = ?, 
            description = ?
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param("dsssii", 
        $amount, 
        $type, 
        $category, 
        $description, 
        $transaction_id, 
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update transaction");
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($verify)) $verify->close();
if (isset($stmt)) $stmt->close();
$conn->close(); 