<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

// Get filter parameters
$country_filter = isset($_GET['country']) ? $_GET['country'] : '';
$currency_filter = isset($_GET['currency']) ? $_GET['currency'] : '';
$warning_filter = isset($_GET['warnings']) ? $_GET['warnings'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($country_filter)) {
    $where_conditions[] = "u.country = ?";
    $params[] = $country_filter;
    $param_types .= 's';
}

if (!empty($currency_filter)) {
    $where_conditions[] = "u.currency = ?";
    $params[] = $currency_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Handle warning filter
$having_clause = '';
if (!empty($warning_filter)) {
    if ($warning_filter === 'with_warnings') {
        $having_clause = 'HAVING warning_count > 0';
    } elseif ($warning_filter === 'no_warnings') {
        $having_clause = 'HAVING warning_count = 0';
    }
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM (
    SELECT u.id,
           (SELECT COUNT(*) FROM user_warnings w WHERE w.user_id = u.id) as warning_count
    FROM users u 
    $where_clause
    $having_clause
) as filtered_users";

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users with filters
$sql = "SELECT u.id, u.username, u.email, u.country, u.currency, u.created_at, u.profile_picture,
               (SELECT COUNT(*) FROM user_warnings w WHERE w.user_id = u.id) as warning_count
        FROM users u 
        $where_clause
        GROUP BY u.id
        $having_clause
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get filter options
$countries = $conn->query("SELECT DISTINCT country FROM users ORDER BY country");
$currencies = $conn->query("SELECT DISTINCT currency FROM users ORDER BY currency");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account Management - Wallet Tally Admin</title>
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
                <h2 class="gradient-text mb-0">User Account Management</h2>
                <p class="text-muted mb-0">Total: <?php echo $total_users; ?> users</p>
                <small class="text-info">Latest registered users shown first • Send warnings for violations • Account deletions require admin reason</small>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="table-card">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <select name="country" class="form-select">
                        <option value="">All Countries</option>
                        <?php while($country = $countries->fetch_assoc()): ?>
                            <option value="<?php echo $country['country']; ?>" <?php echo $country_filter === $country['country'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($country['country']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="">All Currencies</option>
                        <?php while($currency = $currencies->fetch_assoc()): ?>
                            <option value="<?php echo $currency['currency']; ?>" <?php echo $currency_filter === $currency['currency'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['currency']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Warnings</label>
                    <select name="warnings" class="form-select">
                        <option value="">All Users</option>
                        <option value="with_warnings" <?php echo $warning_filter === 'with_warnings' ? 'selected' : ''; ?>>With Warnings</option>
                        <option value="no_warnings" <?php echo $warning_filter === 'no_warnings' ? 'selected' : ''; ?>>No Warnings</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Username or email..." value="<?php echo htmlspecialchars($search); ?>">
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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Country</th>
                            <th>Currency</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users->num_rows > 0): ?>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php 
                                    // Show "NEW" badge for users registered in last 7 days
                                    $days_since_registration = (time() - strtotime($user['created_at'])) / (60 * 60 * 24);
                                    if ($days_since_registration <= 7): 
                                    ?>
                                        <span class="badge bg-success ms-1">NEW</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['country']); ?></td>
                                <td><?php echo htmlspecialchars($user['currency']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-view action-btn" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning action-btn" onclick="sendWarning(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>')" title="Send Warning<?php echo $user['warning_count'] > 0 ? ' (Already sent: ' . $user['warning_count'] . ')' : ''; ?>">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?php if ($user['warning_count'] > 0): ?>
                                            <span class="badge bg-light text-dark ms-1"><?php echo $user['warning_count']; ?></span>
                                        <?php endif; ?>
                                    </button>
                                    <button class="btn btn-sm btn-delete action-btn" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No users found</p>
                                    <?php if(!empty($country_filter) || !empty($currency_filter) || !empty($warning_filter) || !empty($search)): ?>
                                        <a href="users.php" class="btn btn-gradient btn-sm">Clear Filters</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&country=<?php echo $country_filter; ?>&currency=<?php echo $currency_filter; ?>&warnings=<?php echo $warning_filter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&country=<?php echo $country_filter; ?>&currency=<?php echo $currency_filter; ?>&warnings=<?php echo $warning_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&country=<?php echo $country_filter; ?>&currency=<?php echo $currency_filter; ?>&warnings=<?php echo $warning_filter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-textarea {
            border: 2px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 12px !important;
            font-size: 14px !important;
            resize: vertical !important;
        }
        .swal2-textarea:focus {
            border-color: #1A237E !important;
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25) !important;
        }
        .swal2-validation-message {
            background: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb !important;
        }
        .alert-warning {
            background-color: #fff3cd !important;
            border-color: #ffeaa7 !important;
            color: #856404 !important;
        }
        
        /* Custom styles for user details modal */
        .user-details-modal .swal2-html-container {
            padding: 0 !important;
        }
        
        .user-details-modal .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .user-details-modal .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }
        
        .user-details-modal .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .user-details-modal .card-body p {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .user-details-modal .text-primary {
            color: var(--primary-color, #1A237E) !important;
        }
        
        .user-details-modal .text-success {
            color: #28a745 !important;
        }
        
        .user-details-modal .text-warning {
            color: #ffc107 !important;
        }
        
        /* Warning button styling */
        .btn-warning.action-btn {
            background: linear-gradient(135deg, #ff9800, #f57c00) !important;
            border-color: #ff9800 !important;
            color: white !important;
        }
        
        .btn-warning.action-btn:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00) !important;
            border-color: #f57c00 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3) !important;
        }
        
        /* Warning count badge styling */
        .btn-warning.action-btn .badge {
            font-size: 0.7rem;
            padding: 2px 4px;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* SweetAlert2 custom styling for warning modal */
        .swal2-warning-modal .swal2-html-container {
            text-align: left !important;
        }
        
        .swal2-warning-modal .alert-danger {
            background-color: #f8d7da !important;
            border-color: #f5c6cb !important;
            color: #721c24 !important;
            border-radius: 8px !important;
            padding: 15px !important;
        }
    </style>
    <script>
        function viewUser(userId) {
            fetch(`get_user_details.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        // Create profile picture HTML
                        const profilePictureHtml = `
                            <div class="text-center mb-4">
                                <img src="${user.profile_picture_url}" 
                                     alt="Profile Picture" 
                                     class="rounded-circle border border-3 border-primary"
                                     style="width: 120px; height: 120px; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.2);"
                                     onerror="this.src='../assets/images/default-avatar.svg';">
                                <h4 class="mt-3 mb-0 text-primary">${user.username}</h4>
                                <p class="text-muted mb-0">${user.email}</p>
                            </div>
                        `;
                        
                        Swal.fire({
                            title: 'User Profile Details',
                            html: `
                                ${profilePictureHtml}
                                <div class="text-start">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="card border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title text-primary mb-2">
                                                        <i class="fas fa-id-card me-2"></i>Account Info
                                                    </h6>
                                                    <p class="mb-1"><strong>ID:</strong> ${user.id}</p>
                                                    <p class="mb-1"><strong>Country:</strong> ${user.country}</p>
                                                    <p class="mb-0"><strong>Currency:</strong> ${user.currency}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title text-success mb-2">
                                                        <i class="fas fa-chart-line me-2"></i>Activity Stats
                                                    </h6>
                                                    <p class="mb-1"><strong>Transactions:</strong> ${user.transaction_count}</p>
                                                    <p class="mb-1"><strong>Categories:</strong> ${user.category_count}</p>
                                                    <p class="mb-0"><strong>Joined:</strong> ${new Date(user.created_at).toLocaleDateString()}</p>
                                                </div>
                                            </div>
                                        </div>
                                        ${user.feedback ? `
                                        <div class="col-12">
                                            <div class="card border-0 bg-warning bg-opacity-10">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title text-warning mb-2">
                                                        <i class="fas fa-star me-2"></i>User Feedback
                                                    </h6>
                                                    <p class="mb-1"><strong>Rating:</strong> ${'★'.repeat(user.feedback.rating)}<span class="text-muted">${'☆'.repeat(5-user.feedback.rating)}</span></p>
                                                    ${user.feedback.feedback ? `<p class="mb-0"><strong>Comment:</strong> "${user.feedback.feedback}"</p>` : ''}
                                                </div>
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `,
                            icon: null, // Remove the default icon since we're showing profile picture
                            confirmButtonColor: '#1A237E',
                            confirmButtonText: 'Close',
                            width: '600px',
                            customClass: {
                                popup: 'user-details-modal'
                            }
                        });
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    Swal.fire('Error', 'Failed to fetch user details', 'error');
                });
        }
        
        function deleteUser(userId, username) {
            Swal.fire({
                title: 'Delete User Account?',
                html: `
                    <p>Are you sure you want to delete user <strong>${username}</strong>?</p>
                    <div class="alert alert-warning text-start mt-3">
                        <strong>This will permanently delete:</strong>
                        <ul class="mb-0 mt-2">
                            <li>All transactions and financial data</li>
                            <li>All categories and settings</li>
                            <li>All feedback and reviews</li>
                            <li>Complete account access</li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <label for="user-deletion-reason" class="form-label"><strong>Reason for account deletion:</strong></label>
                        <textarea id="user-deletion-reason" class="form-control" rows="3" placeholder="Please provide a detailed reason for deleting this user account (required)"></textarea>
                        <small class="text-muted">Common reasons: Multiple false feedback, Terms of Service violation, Fraudulent activity, etc.</small>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c62828',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete account!',
                cancelButtonText: 'Cancel',
                width: '600px',
                preConfirm: () => {
                    const reason = document.getElementById('user-deletion-reason').value.trim();
                    if (!reason) {
                        Swal.showValidationMessage('Please provide a reason for account deletion');
                        return false;
                    }
                    if (reason.length < 15) {
                        Swal.showValidationMessage('Reason must be at least 15 characters long');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const reason = result.value;
                    
                    // Show loading
                    Swal.fire({
                        title: 'Deleting Account...',
                        text: 'Please wait while we process the account deletion.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('delete_user_admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            user_id: userId,
                            reason: reason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = 'User account has been deleted successfully.';
                            if (data.email_sent) {
                                message += ' The user has been notified via email.';
                            } else {
                                message += ' (Note: Email notification could not be sent)';
                            }
                            
                            Swal.fire({
                                title: 'Account Deleted!',
                                text: message,
                                icon: 'success',
                                confirmButtonColor: '#1A237E'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to delete user account', 'error');
                    });
                }
            });
        }
        
        function sendWarning(userId, username, email) {
            Swal.fire({
                title: 'Send Warning Email',
                html: `
                    <div class="text-start">
                        <p>Send a warning email to user <strong>${username}</strong> (${email})</p>
                        <div class="alert alert-danger text-start mt-3">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Warning Notice:</strong>
                            <p class="mb-0 mt-2">This email will include a strict warning that any future violations will result in immediate account deletion.</p>
                        </div>
                        <div class="mt-3">
                            <label for="warning-reason" class="form-label"><strong>Reason for warning:</strong></label>
                            <select id="warning-category" class="form-select mb-3">
                                <option value="">Select violation type...</option>
                                <option value="False Feedback">False or Misleading Feedback</option>
                                <option value="Terms Violation">Terms of Service Violation</option>
                                <option value="Inappropriate Behavior">Inappropriate Behavior</option>
                                <option value="Spam Activity">Spam or Excessive Activity</option>
                                <option value="Data Misuse">Data Misuse or Manipulation</option>
                                <option value="Security Violation">Security Policy Violation</option>
                                <option value="Other">Other Violation</option>
                            </select>
                            <label for="warning-description" class="form-label"><strong>Detailed description:</strong></label>
                            <textarea id="warning-description" class="form-control" rows="4" placeholder="Please provide a detailed description of the violation and specific actions that led to this warning (required)"></textarea>
                            <small class="text-muted">Be specific about what the user did wrong and what they need to avoid in the future.</small>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff9800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Send Warning Email',
                cancelButtonText: 'Cancel',
                width: '650px',
                customClass: {
                    popup: 'swal2-warning-modal'
                },
                preConfirm: () => {
                    const category = document.getElementById('warning-category').value.trim();
                    const description = document.getElementById('warning-description').value.trim();
                    
                    if (!category) {
                        Swal.showValidationMessage('Please select a violation type');
                        return false;
                    }
                    if (!description) {
                        Swal.showValidationMessage('Please provide a detailed description');
                        return false;
                    }
                    if (description.length < 20) {
                        Swal.showValidationMessage('Description must be at least 20 characters long');
                        return false;
                    }
                    return { category, description };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { category, description } = result.value;
                    
                    // Show loading
                    Swal.fire({
                        title: 'Sending Warning Email...',
                        text: 'Please wait while we send the warning email to the user.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('send_warning_final.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            user_id: userId,
                            category: category,
                            description: description
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Warning Sent!',
                                text: `Warning email has been sent successfully to ${username}. The user has been notified about the violation and the consequences of future violations.`,
                                icon: 'success',
                                confirmButtonColor: '#1A237E'
                            }).then(() => {
                                // Refresh page to update warning button state
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error || 'Failed to send warning email', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Warning email error:', error);
                        Swal.fire('Error', 'Failed to send warning email', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>
