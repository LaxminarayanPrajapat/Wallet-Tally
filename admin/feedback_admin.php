<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

// Get filter parameters
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest'; // latest, rating, oldest

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($rating_filter)) {
    $where_conditions[] = "f.rating = ?";
    $params[] = $rating_filter;
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    if ($status_filter === 'approved') {
        $where_conditions[] = "f.display_approved = 1";
    } elseif ($status_filter === 'not_approved') {
        $where_conditions[] = "(f.display_approved = 0 OR f.display_approved IS NULL)";
    }
}

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR f.feedback LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Determine ORDER BY clause
$order_clause = '';
switch ($sort_by) {
    case 'latest':
        $order_clause = 'ORDER BY COALESCE(f.updated_at, f.created_at) DESC';
        break;
    case 'oldest':
        $order_clause = 'ORDER BY f.created_at ASC';
        break;
    case 'rating':
        $order_clause = 'ORDER BY f.rating DESC, COALESCE(f.updated_at, f.created_at) DESC';
        break;
    default:
        $order_clause = 'ORDER BY COALESCE(f.updated_at, f.created_at) DESC';
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM user_feedback f JOIN users u ON f.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_feedback = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_feedback / $limit);

// Get feedback with filters
$feedback_query = "SELECT f.*, u.username, u.email, 
                   COALESCE(f.display_approved, 0) as display_approved,
                   COALESCE(f.updated_at, f.created_at) as last_modified
                   FROM user_feedback f 
                   JOIN users u ON f.user_id = u.id 
                   $where_clause
                   $order_clause
                   LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($feedback_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$feedback_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Moderation - Wallet Tally Admin</title>
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
                <h2 class="gradient-text mb-0">Review Moderation</h2>
                <p class="text-muted mb-0">Total: <?php echo $total_feedback; ?> reviews • Fresh feedback shown first</p>
                <small class="text-info">Only 5-star reviews can be approved for homepage display</small>
            </div>
            <a href="dashboard.php" class="btn btn-gradient">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Filters -->
        <div class="table-card">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-select">
                        <option value="">All Ratings</option>
                        <option value="5" <?php echo $rating_filter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $rating_filter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="not_approved" <?php echo $status_filter === 'not_approved' ? 'selected' : ''; ?>>Not Approved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="latest" <?php echo $sort_by === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                        <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>By Rating</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Username, email, or feedback..." value="<?php echo htmlspecialchars($search); ?>">
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
                            <th>User</th>
                            <th>Email</th>
                            <th>Rating</th>
                            <th>Feedback</th>
                            <th>Date</th>
                            <th>Display Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($feedback_list && $feedback_list->num_rows > 0): ?>
                            <?php while($feedback = $feedback_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $feedback['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($feedback['username']); ?>
                                    <?php 
                                    // Check if feedback was updated after creation
                                    $is_updated = isset($feedback['updated_at']) && $feedback['updated_at'] != $feedback['created_at'];
                                    
                                    if ($is_updated): 
                                        // If updated, only show UPDATED badge
                                    ?>
                                        <span class="badge bg-warning text-dark ms-1">UPDATED</span>
                                    <?php else: 
                                        // If not updated, show FRESH/NEW based on creation time
                                        $hours_since_creation = (time() - strtotime($feedback['created_at'])) / 3600;
                                        
                                        if ($hours_since_creation <= 24): 
                                    ?>
                                            <span class="badge bg-primary ms-1">FRESH</span>
                                        <?php elseif ($hours_since_creation <= 168): // 7 days ?>
                                            <span class="badge bg-info ms-1">NEW</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($feedback['email']); ?></td>
                                <td>
                                    <div class="star-rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? '' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($feedback['feedback'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($feedback['feedback'] ?? 'No comment'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $last_modified = $feedback['last_modified'] ?? $feedback['created_at'];
                                    echo date('M d, Y', strtotime($last_modified)); 
                                    ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($last_modified)); ?></small>
                                    <?php if (isset($feedback['updated_at']) && $feedback['updated_at'] != $feedback['created_at']): ?>
                                        <br>
                                        <small class="text-warning">
                                            <i class="fas fa-edit me-1"></i>Updated
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(isset($feedback['display_approved']) && $feedback['display_approved']): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Approved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-view action-btn" onclick="viewFeedback(<?php echo $feedback['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($feedback['rating'] == 5): ?>
                                        <?php if(isset($feedback['display_approved']) && $feedback['display_approved']): ?>
                                            <button class="btn btn-sm btn-warning action-btn" onclick="disapproveFeedback(<?php echo $feedback['id']; ?>)" title="Remove from display">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success action-btn" onclick="approveFeedback(<?php echo $feedback['id']; ?>)" title="Approve for display">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-delete action-btn" onclick="deleteFeedback(<?php echo $feedback['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No reviews found</p>
                                    <?php if(!empty($rating_filter) || !empty($status_filter) || !empty($search)): ?>
                                        <a href="feedback_admin.php" class="btn btn-gradient btn-sm">Clear Filters</a>
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
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&rating=<?php echo $rating_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_by; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&rating=<?php echo $rating_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_by; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&rating=<?php echo $rating_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_by; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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
    </style>
    <script>
        function viewFeedback(feedbackId) {
            fetch(`get_feedback_details.php?id=${feedbackId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const f = data.feedback;
                        Swal.fire({
                            title: 'Feedback Details',
                            html: `
                                <div class="text-start">
                                    <p><strong>User:</strong> ${f.username}</p>
                                    <p><strong>Email:</strong> ${f.email}</p>
                                    <p><strong>Rating:</strong> ${'★'.repeat(f.rating)}${'☆'.repeat(5-f.rating)}</p>
                                    <p><strong>Feedback:</strong><br>${f.feedback || 'No comment provided'}</p>
                                    <p><strong>Date:</strong> ${new Date(f.created_at).toLocaleString()}</p>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonColor: '#1A237E'
                        });
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Failed to fetch feedback details', 'error');
                });
        }
        
        function deleteFeedback(feedbackId) {
            Swal.fire({
                title: 'Delete Feedback?',
                html: `
                    <p>Are you sure you want to delete this feedback? This action cannot be undone!</p>
                    <div class="mt-3">
                        <label for="deletion-reason" class="form-label"><strong>Reason for deletion:</strong></label>
                        <textarea id="deletion-reason" class="form-control" rows="3" placeholder="Please provide a reason for deleting this feedback (required)"></textarea>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c62828',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const reason = document.getElementById('deletion-reason').value.trim();
                    if (!reason) {
                        Swal.showValidationMessage('Please provide a reason for deletion');
                        return false;
                    }
                    if (reason.length < 10) {
                        Swal.showValidationMessage('Reason must be at least 10 characters long');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const reason = result.value;
                    
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we process your request.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('delete_feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            feedback_id: feedbackId,
                            reason: reason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = 'Feedback has been deleted successfully.';
                            if (data.email_sent) {
                                message += ' The user has been notified via email.';
                            } else {
                                message += ' (Note: Email notification could not be sent)';
                            }
                            
                            Swal.fire({
                                title: 'Deleted!',
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
                        Swal.fire('Error', 'Failed to delete feedback', 'error');
                    });
                }
            });
        }
        
        function approveFeedback(feedbackId) {
            Swal.fire({
                title: 'Approve for Display?',
                text: 'This 5-star review will be displayed on the homepage testimonials section.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Approving...',
                        text: 'Please wait while we process your request.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('approve_feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ feedback_id: feedbackId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = 'Feedback has been approved for display.';
                            if (data.email_sent) {
                                message += ' The user has been notified via email.';
                            } else {
                                message += ' (Note: Email notification could not be sent)';
                            }
                            
                            Swal.fire({
                                title: 'Approved!',
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
                        Swal.fire('Error', 'Failed to approve feedback', 'error');
                    });
                }
            });
        }
        
        function disapproveFeedback(feedbackId) {
            Swal.fire({
                title: 'Remove from Display?',
                text: 'This review will no longer be shown on the homepage testimonials section.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('disapprove_feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ feedback_id: feedbackId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Removed!',
                                text: 'Feedback has been removed from display.',
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
                        Swal.fire('Error', 'Failed to remove feedback from display', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>
