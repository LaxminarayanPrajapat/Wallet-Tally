<?php
session_start();
require_once('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';

// Validate type
if (!in_array($type, ['income', 'expense'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit;
}

try {
    // Get categories specific to the user and type
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE user_id = ? AND type = ? ORDER BY name");
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch categories'
    ]);
}

$conn->close();
?> 