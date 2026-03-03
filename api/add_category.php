<?php
session_start();
require_once('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['category_name']) || !isset($_POST['category_type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_name = trim($_POST['category_name']);
$category_type = trim($_POST['category_type']);

// Validate category type
if (!in_array($category_type, ['income', 'expense'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid category type']);
    exit;
}

// Check if category name is empty
if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name cannot be empty']);
    exit;
}

// Check if category already exists for this user and type
$check_stmt = $conn->prepare("SELECT id, name, type FROM categories WHERE user_id = ? AND name = ? AND type = ?");
$check_stmt->bind_param("iss", $user_id, $category_name, $category_type);
$check_stmt->execute();
$result = $check_stmt->get_result();

// If category exists, return it
if ($result->num_rows > 0) {
    $category = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'message' => 'Category already exists',
        'category' => $category
    ]);
    exit;
}

// Insert new category
$stmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $category_name, $category_type);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'category' => [
            'id' => $stmt->insert_id,
            'name' => $category_name,
            'type' => $category_type
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add category']);
}

$stmt->close();
$conn->close(); 