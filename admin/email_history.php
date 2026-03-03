<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

// Create email_logs table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) DEFAULT NULL,
    email_type ENUM('appreciation', 'warning', 'feedback_deletion', 'user_deletion') NOT NULL,
    subject VARCHAR(500) NOT NULL,
    status ENUM('SUCCESS', 'FAILED', 'PENDING') DEFAULT 'PENDING',
    error_message TEXT DEFAULT NULL,
    admin_name VARCHAR(100) DEFAULT 'System',
    user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_type (email_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_recipient (recipient_email)
)";

$conn->query($create_table_sql);

// Get filter parameters
$email_type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($email_type_filter)) {
    $where_conditions[] = "email_type = ?";
    $params[] = $email_type_filter;
    $param_types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(recipient_email LIKE ? OR recipient_name LIKE ? OR subject LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM email_logs $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_emails = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_emails / $limit);

// Get email logs
$sql = "SELECT * FROM email_logs $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$email_logs = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    email_type,
    status,
    COUNT(*) as count
FROM email_logs 
GROUP BY email_type, status
ORDER BY email_type, status";
$stats_result = $conn->query($stats_sql);
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['email_type']][$row['status']] = $row['count'];
}

// Calculate totals
$total_stats = [
    'appreciation' => array_sum($stats['appreciation'] ?? []),
    'warning' => array_sum($stats['warning'] ?? []),
    'feedback_deletion' => array_sum($stats['feedback_deletion'] ?? []),
    'user_deletion' => array_sum($stats['user_deletion'] ?? [])
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email History - Wallet Tally Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="gradient-text mb-0">Email History</h2>
                <p class="text-muted mb-0">Total: <?php echo $total_emails; ?> emails sent</p>
                <small class="text-info">Track all system emails sent to users</small>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-star icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_stats['appreciation'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Appreciation Emails</p>
                    <small class="text-success">Feedback Approvals</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-exclamation-triangle icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_stats['warning'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Warning Emails</p>
                    <small class="text-warning">User Warnings</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-comment-slash icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_stats['feedback_deletion'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Feedback Deletion</p>
                    <small class="text-info">Review Removals</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-user-times icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_stats['user_deletion'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Account Deletion</p>
                    <small class="text-danger">User Removals</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-card">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Email Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="appreciation" <?php echo $email_type_filter === 'appreciation' ? 'selected' : ''; ?>>Appreciation</option>
                        <option value="warning" <?php echo $email_type_filter === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="feedback_deletion" <?php echo $email_type_filter === 'feedback_deletion' ? 'selected' : ''; ?>>Feedback Deletion</option>
                        <option value="user_deletion" <?php echo $email_type_filter === 'user_deletion' ? 'selected' : ''; ?>>User Deletion</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="SUCCESS" <?php echo $status_filter === 'SUCCESS' ? 'selected' : ''; ?>>Success</option>
                        <option value="FAILED" <?php echo $status_filter === 'FAILED' ? 'selected' : ''; ?>>Failed</option>
                        <option value="PENDING" <?php echo $status_filter === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Email, name, or subject..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-gradient">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                </div>
            </form>

            <!-- Email Logs Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Admin</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($email_logs->num_rows > 0): ?>
                            <?php while($log = $email_logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <?php
                                    $type_badges = [
                                        'appreciation' => '<span class="badge bg-success"><i class="fas fa-star me-1"></i>Appreciation</span>',
                                        'warning' => '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Warning</span>',
                                        'feedback_deletion' => '<span class="badge bg-info"><i class="fas fa-comment-slash me-1"></i>Feedback Deletion</span>',
                                        'user_deletion' => '<span class="badge bg-danger"><i class="fas fa-user-times me-1"></i>User Deletion</span>'
                                    ];
                                    echo $type_badges[$log['email_type']] ?? '<span class="badge bg-secondary">Unknown</span>';
                                    ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($log['recipient_name'] ?? 'Unknown'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['recipient_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($log['subject']); ?>">
                                        <?php echo htmlspecialchars($log['subject']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if($log['status'] === 'SUCCESS'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Success</span>
                                    <?php elseif($log['status'] === 'FAILED'): ?>
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                                <td>
                                    <div>
                                        <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                        <br>
                                        <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-view action-btn" onclick="viewEmailDetails(<?php echo $log['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($log['status'] === 'FAILED'): ?>
                                    <button class="btn btn-sm btn-warning action-btn" onclick="retryEmail(<?php echo $log['id']; ?>)" title="Retry Send">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No email logs found</p>
                                    <?php if(!empty($email_type_filter) || !empty($status_filter) || !empty($search)): ?>
                                        <a href="email_history.php" class="btn btn-gradient btn-sm">Clear Filters</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&type=<?php echo $email_type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $email_type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&type=<?php echo $email_type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Email Details Modal -->
    <div class="modal fade" id="emailDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title gradient-text">Email Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="emailDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewEmailDetails(emailId) {
            const modal = new bootstrap.Modal(document.getElementById('emailDetailsModal'));
            const content = document.getElementById('emailDetailsContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Fetch email details
            fetch('get_email_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email_id: emailId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading email details: ${data.error}
                        </div>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading email details: ${error.message}
                    </div>
                `;
            });
        }

        function retryEmail(emailId) {
            if (confirm('Are you sure you want to retry sending this email?')) {
                fetch('retry_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email_id: emailId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Email retry initiated successfully!');
                        location.reload();
                    } else {
                        alert('Error retrying email: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error retrying email: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>