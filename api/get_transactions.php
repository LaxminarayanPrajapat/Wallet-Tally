<?php
// Turn off error display, log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once('../config/db.php');
require_once('../includes/currency_formatter.php');

// Clear any previous output
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$currency = $_SESSION['currency'];
$currency_symbol = $_SESSION['currency_symbol'];

try {
    // Get all transactions ordered by date
    $stmt = $conn->prepare("
        SELECT 
            t.*,
            c.name as category_name
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ? 
        ORDER BY t.date ASC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    $total_income = 0;
    $total_expenses = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Ensure amount is converted to float
        $row['amount'] = floatval($row['amount']);
        $row['formatted_amount'] = $currency_symbol . number_format($row['amount'], 2);
        $transactions[] = $row;
        
        if ($row['type'] === 'income') {
            $total_income += $row['amount'];
        } else {
            $total_expenses += $row['amount'];
        }
    }
    
    $balance = $total_income - $total_expenses;
    
    // Debug output
    error_log('Transaction Data: ' . print_r([
        'count' => count($transactions),
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'currency_symbol' => $currency_symbol
    ], true));
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'balance' => $balance,
        'currency' => $currency,
        'currency_symbol' => $currency_symbol,
        'formatted_total_income' => $currency_symbol . number_format($total_income, 2),
        'formatted_total_expenses' => $currency_symbol . number_format($total_expenses, 2),
        'formatted_balance' => $currency_symbol . number_format($balance, 2)
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_transactions.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($stmt)) $stmt->close();
$conn->close();
?> 