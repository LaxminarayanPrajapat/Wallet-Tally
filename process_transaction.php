<?php
require_once('config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if required fields are provided
if (!isset($_POST['type']) || !isset($_POST['category_name']) || !isset($_POST['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'];
$category_name = $_POST['category_name'];
$amount = $_POST['amount'];
$description = $_POST['description'] ?? '';
$transaction_id = $_POST['transaction_id'] ?? null;

// Validate expense against balance
if ($type === 'expense') {
    // Calculate current balance
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as total_balance
        FROM transactions 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $balance_result = $stmt->get_result()->fetch_assoc();
    $current_balance = $balance_result['total_balance'];
    
    // If editing, add back the old transaction amount to get accurate balance
    if ($transaction_id) {
        $stmt = $conn->prepare("SELECT amount, type FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $transaction_id, $user_id);
        $stmt->execute();
        $old_transaction = $stmt->get_result()->fetch_assoc();
        if ($old_transaction) {
            // Add back the old amount to balance
            if ($old_transaction['type'] === 'expense') {
                $current_balance += $old_transaction['amount'];
            } else {
                $current_balance -= $old_transaction['amount'];
            }
        }
    }
    
    // Check if expense exceeds balance
    if ($amount > $current_balance) {
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient balance. Your current balance is ' . number_format($current_balance, 2) . '. Cannot record expense of ' . number_format($amount, 2)
        ]);
        exit();
    }
}

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

    if ($transaction_id) {
        // Check if transaction is within 24 hours for editing
        $stmt = $conn->prepare("SELECT created_at FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $transaction_id, $user_id);
        $stmt->execute();
        $transaction_data = $stmt->get_result()->fetch_assoc();
        
        if ($transaction_data) {
            $transaction_time = strtotime($transaction_data['created_at']);
            $current_time = time();
            $hours_passed = ($current_time - $transaction_time) / 3600;
            
            if ($hours_passed >= 24) {
                $conn->rollback();
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot edit transaction after 24 hours. This transaction was created more than 24 hours ago and is now locked.'
                ]);
                exit();
            }
        }
        
        // Update existing transaction
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET type = ?, category_id = ?, amount = ?, description = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("sidsii", $type, $category_id, $amount, $description, $transaction_id, $user_id);
        $stmt->execute();
        $message = 'Transaction updated successfully';
    } else {
        // Insert new transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, type, category_id, amount, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isids", $user_id, $type, $category_id, $amount, $description);
        $stmt->execute();
        $message = 'Transaction added successfully';
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error processing transaction: ' . $e->getMessage()]);
}
?> 