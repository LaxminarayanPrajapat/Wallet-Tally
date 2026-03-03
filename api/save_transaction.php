<?php
// Turn off error display, log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once('../config/db.php');

// Clear any previous output
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        
        // Validate and sanitize inputs
        $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
        $category = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

        // Validate required fields
        if (empty($amount) || empty($type) || empty($category)) {
            throw new Exception('Required fields are missing');
        }

        // Validate transaction type
        if (!in_array($type, ['income', 'expense'])) {
            throw new Exception('Invalid transaction type');
        }

        // Verify database connection
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }

        // Save the transaction
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, category, description) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("idsss", $user_id, $amount, $type, $category, $description);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        echo json_encode(['success' => true, 'message' => 'Transaction saved successfully']);

    } catch (Exception $e) {
        error_log("Transaction error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
exit();
?> 