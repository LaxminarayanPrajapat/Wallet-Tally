<?php
session_start();
require_once('config/db.php');
require_once('includes/email_service.php');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, currency_symbol FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currency_symbol = isset($user['currency_symbol']) ? $user['currency_symbol'] : '$';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

if (strtotime($start_date) > strtotime($end_date)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
    exit();
}

$stmt = $conn->prepare("SELECT t.*, c.name as category_name FROM transactions t LEFT JOIN categories c ON t.category_id = c.id WHERE t.user_id = ? AND DATE(t.created_at) BETWEEN ? AND ? ORDER BY t.created_at DESC");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_income = 0;
$total_expenses = 0;
foreach ($transactions as $transaction) {
    if ($transaction['type'] === 'income') {
        $total_income += $transaction['amount'];
    } else {
        $total_expenses += $transaction['amount'];
    }
}

$send_email = isset($_POST['send_email']) && $_POST['send_email'] == '1';

// Generate HTML content
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transaction Report</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            color: #333; 
            background: #fff;
        }
        .header { 
            background: linear-gradient(135deg, #1A237E, #1B5E20); 
            color: white; 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 { 
            margin: 0; 
            font-size: 24px; 
        }
        .header p { 
            margin: 5px 0 0 0; 
            opacity: 0.9; 
        }
        .header-logo {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            opacity: 0.3;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            background: white;
        }
        th { 
            background: #f5f5f5; 
            padding: 12px; 
            text-align: left; 
            border-bottom: 2px solid #ddd; 
            font-weight: 600; 
        }
        td { 
            padding: 10px 12px; 
            border-bottom: 1px solid #eee; 
        }
        .income { 
            color: #10B981; 
            font-weight: 500; 
        }
        .expense { 
            color: #EF4444; 
            font-weight: 500; 
        }
        .summary { 
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 10px; 
            margin-top: 20px; 
        }
        .summary-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0; 
            border-bottom: 1px solid #ddd; 
        }
        .summary-row:last-child { 
            border-bottom: none; 
            font-weight: bold; 
            font-size: 18px; 
        }
        .no-data { 
            text-align: center; 
            padding: 40px; 
            color: #999; 
            font-style: italic; 
        }
        @media print { 
            body { margin: 0; } 
            .header { border-radius: 0; } 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 8px;">
                <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
            </svg>
            Wallet Tally - Transaction Report
        </h1>
        <p>User: <?php echo htmlspecialchars($user['username']); ?> | Period: <?php echo date('d M Y', strtotime($start_date)); ?> to <?php echo date('d M Y', strtotime($end_date)); ?></p>
        <svg class="header-logo" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
            <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
        </svg>
    </div>
    
    <?php if (empty($transactions)): ?>
        <div class="no-data">No transactions found for the selected period.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $row): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td><span class="<?php echo $row['type']; ?>"><?php echo ucfirst($row['type']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td style="text-align: right;" class="<?php echo $row['type']; ?>">
                        <?php echo $row['type'] === 'income' ? '+' : '-'; ?>
                        <?php echo $currency_symbol . number_format($row['amount'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <h3 style="margin-top: 0;">Summary</h3>
            <div class="summary-row">
                <span>Total Income:</span>
                <span class="income"><?php echo $currency_symbol . number_format($total_income, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Total Expenses:</span>
                <span class="expense"><?php echo $currency_symbol . number_format($total_expenses, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Net Balance:</span>
                <span><?php echo $currency_symbol . number_format($total_income - $total_expenses, 2); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        // Auto-print functionality
        if (window.location.search.includes('print=1')) {
            window.print();
        }
    </script>
</body>
</html>
<?php
$html_content = ob_get_clean();

if ($send_email) {
    // For email: save HTML and convert or send as attachment
    $temp_file = sys_get_temp_dir() . '/Wallet_Tally_Report_' . date('Y-m-d_His') . '.html';
    file_put_contents($temp_file, $html_content);
    
    $summary = [
        'start_date' => date('d M Y', strtotime($start_date)),
        'end_date' => date('d M Y', strtotime($end_date)),
        'currency' => $currency_symbol,
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_balance' => $total_income - $total_expenses
    ];
    
    $emailService = new EmailService();
    $email_sent = $emailService->sendPDFReport($user['email'], $user['username'], $temp_file, $summary);
    
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $email_sent,
        'message' => $email_sent ? 'Report has been sent to your email address.' : 'Failed to send email. Please try again.'
    ]);
} else {
    // For download: output HTML that will trigger print dialog
    header('Content-Type: text/html; charset=UTF-8');
    echo $html_content;
}
?>
