<?php
session_start();
require_once('config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if required fields are provided
if (!isset($_POST['transaction_id']) || !isset($_POST['type']) || !isset($_POST['category_name']) || !isset($_POST['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_POST['transaction_id'];
$type = $_POST['type'];
$category_name = $_POST['category_name'];
$amount = $_POST['amount'];
$description = $_POST['description'] ?? '';

// Start transaction
$conn->begin_transaction();

try {
    // Check if category exists
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ? AND type = ?");
    $stmt->bind_param("sis", $category_name, $user_id, $type);
    $stmt->execute();
    $category_result = $stmt->get_result();

    if ($category_result->num_rows === 0) {
        // Create new category if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO categories (name, user_id, type) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $category_name, $user_id, $type);
        $stmt->execute();
        $category_id = $conn->insert_id;
    } else {
        $category_id = $category_result->fetch_assoc()['id'];
    }

    // Update transaction
    $stmt = $conn->prepare("
        UPDATE transactions 
        SET type = ?, category_id = ?, amount = ?, description = ? 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("sidsii", $type, $category_id, $amount, $description, $transaction_id, $user_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error updating transaction: ' . $e->getMessage()]);
} 