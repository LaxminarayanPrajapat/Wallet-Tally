<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// Get all transactions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total transactions count
$total_transactions_query = "SELECT COUNT(*) as total FROM transactions";
$total_transactions_result = $conn->query($total_transactions_query);
$total_transactions = $total_transactions_result->fetch_assoc()['total'];
$total_pages = ceil($total_transactions / $limit);

// Get transactions with pagination and user details
$query = "SELECT t.*, u.username, u.email, c.name as category_name 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          LEFT JOIN categories c ON t.category_id = c.id 
          ORDER BY t.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions Management - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        :root {
            --primary-color: #1A237E;
            --secondary-color: #1B5E20;
            --accent-color: #0D47A1;
            --dark-blue: #0D47A1;
            --light-blue: #E8EAF6;
            --success-green: #2E7D32;
            --background-light: #F5F7FA;
            --text-dark: #1A237E;
            --dark-green: #1B5E20;
            --navy-blue: #0D47A1;
            --gradient-start: #1A237E;
            --gradient-end: #1B5E20;
            --gradient-angle: 135deg;
        }

        body {
            padding-top: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--background-light);
            overflow-x: hidden;
        }

        .admin-navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 999;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(26, 35, 126, 0.1);
            transition: all 0.3s ease;
        }

        .admin-navbar.expanded {
            left: 0;
        }

        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 1.5rem;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            transform: translateX(0);
            border-radius: 15px;
        }

        .admin-sidebar.collapsed {
            transform: translateX(-100%);
        }

        .admin-sidebar .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .admin-sidebar .sidebar-header i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--light-blue);
        }

        .admin-sidebar .sidebar-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .admin-sidebar .sidebar-header small {
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
        }

        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: 500;
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        .admin-sidebar .nav-link.text-danger {
            color: #ff6b6b;
        }

        .admin-sidebar .nav-link.text-danger:hover {
            background: rgba(255,107,107,0.1);
            color: #ff6b6b;
        }

        .admin-content {
            margin-left: 250px;
            padding: 80px 40px 40px;
            min-height: 100vh;
            background: var(--background-light);
            max-width: 1600px;
            margin-right: auto;
            margin-left: auto;
            transition: all 0.3s ease;
        }

        .admin-content.expanded {
            margin-left: 0;
        }

        .gradient-text {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .transaction-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid rgba(26, 35, 126, 0.1);
            margin-bottom: 30px;
        }

        .transaction-table .table-header {
            padding: 25px 30px;
            border-bottom: 1px solid rgba(26, 35, 126, 0.1);
            background: var(--light-blue);
        }

        .transaction-table th {
            padding: 20px 25px;
            font-weight: 600;
            color: var(--text-dark);
            background: var(--light-blue);
            border-bottom: 2px solid var(--primary-color);
        }

        .transaction-table td {
            padding: 20px 25px;
            vertical-align: middle;
            color: var(--text-dark);
            border-bottom: 1px solid rgba(26, 35, 126, 0.1);
        }

        .action-btn {
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
            margin: 0 3px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .search-box {
            max-width: 300px;
            margin-bottom: 20px;
        }

        .pagination {
            margin-top: 20px;
        }

        .page-link {
            color: var(--primary-color);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .transaction-amount {
            font-weight: 600;
        }

        .transaction-amount.income {
            color: var(--success-green);
        }

        .transaction-amount.expense {
            color: #dc3545;
        }

        .transaction-description {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-content {
                margin-left: 0;
                padding: 70px 15px 20px;
            }
            .admin-navbar {
                left: 0;
            }
        }

        .toggle-sidebar {
            display: block !important;
            margin-right: 15px;
            font-size: 1.2rem;
            color: var(--primary-color);
            padding: 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: none;
            border: none;
        }

        .toggle-sidebar:hover {
            background-color: var(--light-blue);
        }

        .btn-gradient {
            background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
            border: none;
            color: white !important;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
            color: white !important;
        }

        .btn-gradient i {
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="admin-sidebar">
                <div class="sidebar-header">
                    <i class="fas fa-user-shield"></i>
                    <h5>
                        <i class="fas fa-wallet me-2"></i>
                        <span>Wallet Tally</span>
                    </h5>
                    <small>Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a class="nav-link active" href="transactions.php">
                        <i class="fas fa-exchange-alt"></i> Transactions
                    </a>
                    <a class="nav-link" href="feedback.php">
                        <i class="fas fa-comments"></i> Feedback
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="admin-content">
                <!-- Top Navbar -->
                <nav class="admin-navbar">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-link toggle-sidebar" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h5 class="mb-0 gradient-text">Transactions Management</h5>
                    </div>
                    <div class="user-info">
                        <div class="dropdown">
                            <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield"></i>
                                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <div class="container-fluid">
                    <!-- Search Box -->
                    <div class="search-box">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search transactions...">
                    </div>

                    <!-- Transactions Table -->
                    <div class="transaction-table">
                        <div class="table-header d-flex justify-content-between align-items-center">
                            <h5 class="gradient-text">All Transactions</h5>
                            <div>
                                <span class="me-3">Total Transactions: <?php echo $total_transactions; ?></span>
                                <a href="index.php" class="btn btn-gradient">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="transactionsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($transaction = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $transaction['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($transaction['username']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($transaction['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $transaction['type'] === 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($transaction['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                        <td>
                                            <span class="transaction-amount <?php echo $transaction['type'] === 'income' ? 'income' : 'expense'; ?>">
                                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                                <?php echo number_format($transaction['amount'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="transaction-description" title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger action-btn" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const content = document.querySelector('.admin-content');
            const navbar = document.querySelector('.admin-navbar');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
                navbar.classList.toggle('expanded');
            }
        }

        // Add click event listener to close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.admin-sidebar');
            const toggleButton = document.querySelector('.toggle-sidebar');
            
            if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                } else {
                    sidebar.classList.add('collapsed');
                    document.querySelector('.admin-content').classList.add('expanded');
                    document.querySelector('.admin-navbar').classList.add('expanded');
                }
            }
        });

        // Prevent sidebar from closing when clicking inside it
        document.querySelector('.admin-sidebar').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const table = document.getElementById('transactionsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().includes(searchText)) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        });

        function viewTransaction(transactionId) {
            fetch(`get_transaction_details.php?id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: 'Transaction Details',
                        html: `
                            <div class="text-start">
                                <p><strong>User:</strong> ${data.username}</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                                <p><strong>Type:</strong> ${data.type}</p>
                                <p><strong>Category:</strong> ${data.category_name}</p>
                                <p><strong>Amount:</strong> ${data.type === 'income' ? '+' : '-'}${data.amount}</p>
                                <p><strong>Description:</strong> ${data.description}</p>
                                <p><strong>Date:</strong> ${data.created_at}</p>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonColor: '#1A237E'
                    });
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch transaction details',
                        icon: 'error',
                        confirmButtonColor: '#1A237E'
                    });
                });
        }

        function deleteTransaction(transactionId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1A237E',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`delete_transaction.php?id=${transactionId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Deleted!',
                                'Transaction has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                'Failed to delete transaction.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'Error!',
                            'Failed to delete transaction.',
                            'error'
                        );
                    });
                }
            });
        }
    </script>
</body>
</html> 