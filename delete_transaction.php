<?php
session_start();
require_once('config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if transaction ID is provided
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID not provided']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_POST['id'];

// Check if transaction is within 24 hours for deletion
$stmt = $conn->prepare("SELECT created_at FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$transaction_data = $stmt->get_result()->fetch_assoc();

if ($transaction_data) {
    $transaction_time = strtotime($transaction_data['created_at']);
    $current_time = time();
    $hours_passed = ($current_time - $transaction_time) / 3600;
    
    if ($hours_passed >= 24) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete transaction after 24 hours. This transaction was created more than 24 hours ago and is now locked.'
        ]);
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete the transaction
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->affected_rows === 0) {
        throw new Exception('Transaction not found or already deleted');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting transaction: ' . $e->getMessage()]);
} 