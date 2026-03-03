<?php
session_start();
require_once('../config/db.php');
require_once('../includes/auto_increment_helper.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = $_GET['id'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete associated transactions first
    $stmt = $conn->prepare("DELETE FROM transactions WHERE category_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    
    // Then delete the category
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    
    $conn->commit();
    
    // Reset AUTO_INCREMENT if tables are now empty
    resetAutoIncrementIfEmpty($conn, 'transactions');
    resetAutoIncrementIfEmpty($conn, 'categories');
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
} 