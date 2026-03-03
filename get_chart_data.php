<?php
session_start();
require_once('config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get data for the last 6 months
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
    FROM transactions 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$income = [];
$expenses = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $income[] = floatval($row['income']);
    $expenses[] = floatval($row['expenses']);
}

// Get total income and expenses
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
    FROM transactions 
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// Get category-wise breakdown for income
$stmt = $conn->prepare("
    SELECT 
        c.name as category,
        SUM(t.amount) as amount
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.type = 'income'
    GROUP BY c.id, c.name
    ORDER BY amount DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$income_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get category-wise breakdown for expenses
$stmt = $conn->prepare("
    SELECT 
        c.name as category,
        SUM(t.amount) as amount
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.type = 'expense'
    GROUP BY c.id, c.name
    ORDER BY amount DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$expense_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'labels' => $labels,
    'income' => $income,
    'expenses' => $expenses,
    'total_income' => floatval($totals['total_income']),
    'total_expenses' => floatval($totals['total_expenses']),
    'income_categories' => $income_categories,
    'expense_categories' => $expense_categories
]); 