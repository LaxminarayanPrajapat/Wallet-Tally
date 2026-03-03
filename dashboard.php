<?php
session_start();
require_once('config/db.php');
require_once('includes/currency_formatter.php');
require_once('includes/session_timeout.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, currency, currency_symbol FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if user exists
if (!$user) {
    // User not found, clear session and redirect to login
    session_destroy();
    header('Location: login.php');
    exit();
}

// Ensure we have the user's currency symbol
$currency_symbol = $user['currency_symbol'] ?? '';
if (empty($currency_symbol)) {
    // If currency_symbol is empty, update it based on the currency
    $currency_map = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'AED' => 'د.إ',
        'JPY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'Fr',
        'CNY' => '¥',
        'HKD' => 'HK$',
        'SGD' => 'S$',
        'SAR' => '﷼',
        'KRW' => '₩',
        'MYR' => 'RM',
        'THB' => '฿',
        'IDR' => 'Rp',
        'PHP' => '₱',
        'VND' => '₫',
        'PKR' => '₨',
        'BDT' => '৳',
        'LKR' => '₨',
        'NPR' => '₨',
        'MMK' => 'K',
        'KHR' => '៛',
        'LAK' => '₭',
        'BND' => 'B$',
        'MVR' => 'ރ',
        'BTN' => 'Nu.',
        'MNT' => '₮',
        'AFN' => '؋'
    ];
    
    $currency_symbol = $currency_map[$user['currency'] ?? 'USD'] ?? '$';
    
    // Update the user's currency symbol in the database
    $update_stmt = $conn->prepare("UPDATE users SET currency_symbol = ? WHERE id = ?");
    $update_stmt->bind_param("si", $currency_symbol, $user_id);
    $update_stmt->execute();
}

// Store the currency in session for consistent use throughout the dashboard
$_SESSION['currency'] = $user['currency'] ?? 'USD';
$_SESSION['currency_symbol'] = $currency_symbol;

// Calculate total balance
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as total_balance
    FROM transactions 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_balance = $stmt->get_result()->fetch_assoc()['total_balance'];

// Calculate total income (all time)
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_income
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'income'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_income = $stmt->get_result()->fetch_assoc()['total_income'];

// Calculate monthly income for comparison
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as monthly_income,
        COALESCE(
            (SELECT SUM(amount) 
             FROM transactions 
             WHERE user_id = ? 
             AND type = 'income' 
             AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
             AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            ), 0
        ) as last_month_income
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'income' 
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$income_result = $stmt->get_result()->fetch_assoc();
$monthly_income = $income_result['monthly_income'];
$last_month_income = $income_result['last_month_income'];
$income_change = $last_month_income > 0 
    ? round((($monthly_income - $last_month_income) / $last_month_income) * 100, 1)
    : ($monthly_income > 0 ? 100 : 0);

// Calculate total expenses (all time)
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_expenses
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'expense'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_expenses = $stmt->get_result()->fetch_assoc()['total_expenses'];

// Calculate monthly expenses for comparison
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as monthly_expenses,
        COALESCE(
            (SELECT SUM(amount) 
             FROM transactions 
             WHERE user_id = ? 
             AND type = 'expense' 
             AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
             AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            ), 0
        ) as last_month_expenses
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'expense' 
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$expense_result = $stmt->get_result()->fetch_assoc();
$monthly_expenses = $expense_result['monthly_expenses'];
$last_month_expenses = $expense_result['last_month_expenses'];
$expense_change = $last_month_expenses > 0 
    ? round((($monthly_expenses - $last_month_expenses) / $last_month_expenses) * 100, 1)
    : ($monthly_expenses > 0 ? 100 : 0);

// Get recent transactions
$stmt = $conn->prepare("
    SELECT t.*, c.name as category_name 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for the form (only user's actual categories)
$stmt = $conn->prepare("SELECT id, name, type FROM categories WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's existing feedback
$existing_review = null;
try {
    $stmt = $conn->prepare("SELECT rating, feedback FROM user_feedback WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $review_result = $stmt->get_result();
    $existing_review = $review_result->fetch_assoc();
} catch (Exception $e) {
    error_log("Error fetching user feedback: " . $e->getMessage());
    $existing_review = null;
}

// Session messages will be handled by inline JavaScript after page loads
$show_login_success = isset($_SESSION['login_success']);
$show_export_success = isset($_SESSION['export_success']);
$export_success_msg = $_SESSION['export_success'] ?? '';
$show_export_error = isset($_SESSION['export_error']);
$export_error_msg = $_SESSION['export_error'] ?? '';

// Clear session messages
unset($_SESSION['login_success']);
unset($_SESSION['export_success']);
unset($_SESSION['export_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>
<body>
    <div class="dashboard-container">
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg fixed-top">
                <div class="container-fluid">
                    <!-- Logo -->
                    <a class="navbar-brand" href="dashboard.php">
                        <i class="fas fa-wallet"></i> 
                        <span class="gradient-text">Wallet Tally</span>
                    </a>

                    <!-- Hamburger Menu Button -->
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Navbar Content -->
                    <div class="collapse navbar-collapse" id="navbarContent">
                        <!-- Right side elements -->
                        <div class="navbar-right">
                            <!-- Income/Expense Buttons -->
                            <div class="transaction-buttons">
                                <button class="btn btn-success" onclick="openTransactionModal('income')">
                                    <i class="fas fa-plus-circle"></i> <span>Income</span>
                                </button>
                                <button class="btn btn-danger" onclick="openTransactionModal('expense')">
                                    <i class="fas fa-minus-circle"></i> <span>Expense</span>
                                </button>
                            </div>

                            <!-- User Profile -->
                            <div class="user-profile">
                                <?php
                                // Get user's profile picture from database
                                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $profile_result = $stmt->get_result()->fetch_assoc();
                                $profile_picture = $profile_result['profile_picture'] ?? 'default.png';
                                
                                // Define the profile picture path
                                $profile_path = 'uploads/profile_pictures/' . $profile_picture;
                                
                                // Check if the file exists, if not use default
                                if (!file_exists($profile_path)) {
                                    $profile_path = 'uploads/profile_pictures/default.png';
                                }
                                ?>
                                <div class="profile-info">
                                    <img src="<?php echo $profile_path; ?>" 
                                         alt="Profile" 
                                         class="profile-picture"
                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23e5e7eb%22/%3E%3Cpath d=%22M50 45c8.284 0 15-6.716 15-15s-6.716-15-15-15-15 6.716-15 15 6.716 15 15 15zm0 5c-10 0-30 5-30 15v10h60V65c0-10-20-15-30-15z%22 fill=%22%239ca3af%22/%3E%3C/svg%3E'">
                                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                </div>
                                <a href="profile.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-edit"></i> <span>Profile</span>
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Add padding to account for fixed navbar -->
            <div class="content-wrapper">
                <!-- Dashboard Content -->
                <div class="dashboard-content">
                    <!-- Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card summary-card balance-card">
                                <div class="card-body">
                                    <div class="card-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="card-info">
                                        <h6>Total Balance</h6>
                                        <h3><?php echo $currency_symbol . number_format($total_balance, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card summary-card income-card">
                                <div class="card-body">
                                    <div class="card-icon">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div class="card-info">
                                        <h6>Total Income</h6>
                                        <h3><?php echo $currency_symbol . number_format($total_income, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card summary-card expense-card">
                                <div class="card-body">
                                    <div class="card-icon">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div class="card-info">
                                        <h6>Total Expenses</h6>
                                        <h3><?php echo $currency_symbol . number_format($total_expenses, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Recent Transactions</h5>
                                <button class="btn btn-gradient btn-sm" onclick="showExportModal()">
                                    <i class="fas fa-download me-2"></i>Export
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $transaction['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                                    <?php echo ucfirst($transaction['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($transaction['category_name']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td class="<?php echo $transaction['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                                <?php echo $currency_symbol . number_format($transaction['amount'], 2); ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Check if transaction is within 24 hours
                                                $transaction_time = strtotime($transaction['created_at']);
                                                $current_time = time();
                                                $hours_passed = ($current_time - $transaction_time) / 3600;
                                                $is_editable = $hours_passed < 24;
                                                ?>
                                                
                                                <?php if ($is_editable): ?>
                                                    <button class="btn btn-action" onclick="editTransaction(<?php echo $transaction['id']; ?>)" title="Edit transaction">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-action text-danger" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" title="Delete transaction">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-action" disabled title="Cannot edit after 24 hours" style="opacity: 0.5; cursor: not-allowed;">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                    <span class="text-muted small">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row g-4 mt-4">
                        <!-- Pie Chart -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Income vs Expenses Distribution</h5>
                                    <div style="height: 300px;">
                                        <canvas id="pieChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Rate Your Experience -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Rate Your Experience</h5>
                                    <div class="d-flex flex-column gap-3">
                                        <div class="rating-section text-center mb-3">
                                            <div class="stars mb-2" data-existing-rating="<?php echo isset($existing_review['rating']) ? intval($existing_review['rating']) : 0; ?>">
                                                <?php
                                                // Render exactly 5 stars, mark as active based on existing review
                                                for ($i = 1; $i <= 5; $i++) {
                                                    $active = ($existing_review && isset($existing_review['rating']) && $existing_review['rating'] >= $i) ? ' active' : '';
                                                    echo "<i class=\"fas fa-star star-rating$active\" data-rating=\"$i\"></i>";
                                                }
                                                ?>
                                            </div>
                                            <p class="text-muted mb-0">Click to rate</p>
                                        </div>
                                        <div class="feedback-section">
                                            <textarea class="form-control mb-3" id="feedbackText" rows="3" placeholder="Share your feedback with us..."><?php echo isset($existing_review['feedback']) ? htmlspecialchars($existing_review['feedback']) : ''; ?></textarea>
                                            <?php $feedbackButtonText = (isset($existing_review['rating']) && $existing_review['rating'] > 0) ? 'Update Feedback' : 'Submit Feedback'; ?>
                                            <button class="btn btn-gradient w-100" onclick="submitFeedback()">
                                                <i class="fas fa-paper-plane me-2"></i><?php echo $feedbackButtonText; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1" role="dialog" aria-labelledby="transactionModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transactionModalLabel">Add Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="transactionForm" onsubmit="return handleTransactionSubmit(event)">
                        <input type="hidden" name="type" id="transactionType">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <div class="input-group">
                                <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleCategorySuggestions()">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                            <div id="categorySuggestions" class="mt-2" style="display: none;">
                                <div class="d-flex flex-wrap gap-2" id="categoryButtonsContainer">
                                    <!-- Categories will be dynamically loaded based on transaction type -->
                                </div>
                            </div>
                            <!-- Hidden data for JavaScript -->
                            
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" id="currency-symbol"><?php echo htmlspecialchars($currency_symbol); ?></span>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Transaction</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Date Range Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Transactions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm" action="/Wallet%20Tally/Wallet%20Tally/export_pdf.php" method="POST">
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sendEmail" name="send_email" value="1" checked>
                                <label class="form-check-label" for="sendEmail">
                                    Send PDF to my email (<?php echo htmlspecialchars($user['email'] ?? 'your registered email'); ?>)
                                </label>
                            </div>
                            <small class="text-muted">Uncheck to download directly instead</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="exportSubmitBtn">
                                <i class="fas fa-paper-plane me-2"></i><span id="exportBtnText">Generate & Send Report</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Buttons for Mobile -->
    <div class="fab-container">
        <button type="button" class="fab-button fab-income" onclick="openTransactionModal('income')">
            <i class="fas fa-plus"></i>
        </button>
        <button type="button" class="fab-button fab-expense" onclick="openTransactionModal('expense')">
            <i class="fas fa-minus"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize Categories and Balance for JavaScript -->
    <script>
        // Define default categories (shown dynamically, not stored in DB until used)
        const defaultIncomeCategories = ['Salary', 'Freelance', 'Investments', 'Gifts', 'Business', 'Rental Income'];
        const defaultExpenseCategories = ['Food & Dining', 'Transportation', 'Shopping', 'Bills & Utilities', 'Entertainment', 'Healthcare', 'Education', 'Travel'];
        
        // Get user's actual categories from database
        const userIncomeCategories = <?php 
            $income_cats = array_filter($categories, function($cat) { return $cat['type'] === 'income'; });
            echo json_encode(array_values(array_map(function($cat) { return $cat['name']; }, $income_cats)));
        ?>;
        
        const userExpenseCategories = <?php 
            $expense_cats = array_filter($categories, function($cat) { return $cat['type'] === 'expense'; });
            echo json_encode(array_values(array_map(function($cat) { return $cat['name']; }, $expense_cats)));
        ?>;
        
        // Combine default and user categories (remove duplicates)
        window.incomeCategories = [...new Set([...defaultIncomeCategories, ...userIncomeCategories])];
        window.expenseCategories = [...new Set([...defaultExpenseCategories, ...userExpenseCategories])];
        
        // Pass balance and currency to JavaScript
        window.currentBalance = <?php echo $total_balance; ?>;
        window.currencySymbol = '<?php echo addslashes($currency_symbol); ?>';
        
        console.log('Income categories (default + user):', window.incomeCategories);
        console.log('Expense categories (default + user):', window.expenseCategories);
        console.log('Current balance:', window.currentBalance);
        console.log('Currency symbol:', window.currencySymbol);
    </script>
    
    <!-- Session Messages -->
    <?php if ($show_login_success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Welcome Back!',
                text: 'You have successfully logged in',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        });
    </script>
    <?php endif; ?>
    
    <?php if ($show_export_success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Export Successful!',
                text: '<?php echo addslashes($export_success_msg); ?>',
                timer: 3000,
                showConfirmButton: true,
                confirmButtonText: 'OK'
            });
        });
    </script>
    <?php endif; ?>
    
    <?php if ($show_export_error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: '<?php echo addslashes($export_error_msg); ?>',
                showConfirmButton: true,
                confirmButtonText: 'Try Again',
                showCancelButton: true,
                cancelButtonText: 'Download Instead'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    showExportModal();
                }
            });
        });
    </script>
    <?php endif; ?>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/pages/dashboard.js"></script>
</body>
</html> 