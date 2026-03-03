<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_transactions = $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'];
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM user_feedback")->fetch_assoc()['count'];
$avg_rating = round($conn->query("SELECT COALESCE(AVG(rating), 0) as avg FROM user_feedback")->fetch_assoc()['avg'], 1);

// Get testimonials statistics (4+ star ratings with feedback)
$testimonials_count = $conn->query("SELECT COUNT(*) as count FROM user_feedback WHERE rating >= 4 AND feedback IS NOT NULL AND feedback != ''")->fetch_assoc()['count'];

// Get rating distribution
$rating_dist = $conn->query("SELECT rating, COUNT(*) as count FROM user_feedback GROUP BY rating ORDER BY rating DESC");
$ratings = [];
while($r = $rating_dist->fetch_assoc()) {
    $ratings[$r['rating']] = $r['count'];
}

// Get recent users
$recent_users = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Get recent feedback
$recent_feedback = $conn->query("SELECT f.*, u.username FROM user_feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5");

// Get top testimonials
$top_testimonials = $conn->query("SELECT f.*, u.username, u.profile_picture FROM user_feedback f JOIN users u ON f.user_id = u.id WHERE f.rating >= 4 AND f.feedback IS NOT NULL AND f.feedback != '' ORDER BY f.rating DESC, f.created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <div class="page-header">
            <h2 class="gradient-text mb-0">Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-users icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_users; ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-exchange-alt icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_transactions; ?></h3>
                    <p class="text-muted mb-0">Transactions</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-comments icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $total_feedback; ?></h3>
                    <p class="text-muted mb-0">Feedback</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-star icon-gradient"></i>
                    <h3 class="gradient-text"><?php echo $avg_rating; ?></h3>
                    <p class="text-muted mb-0">Avg Rating</p>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-globe me-2"></i>Users by Country</h5>
                    <div style="height: 300px;">
                        <canvas id="usersByCountryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="table-card">
                    <h5 class="gradient-text mb-3"><i class="fas fa-chart-pie me-2"></i>Feedback Distribution</h5>
                    <div style="height: 300px;">
                        <canvas id="feedbackDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information Panel -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-card">
                    <h5 class="gradient-text mb-4"><i class="fas fa-server me-2"></i>System Information</h5>
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fas fa-database icon-gradient mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-muted mb-2">Database Status</h6>
                                <p class="mb-1"><strong id="dbStatus">Checking...</strong></p>
                                <span id="dbStatusBadge" class="badge bg-secondary">Loading...</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fab fa-php icon-gradient mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-muted mb-2">PHP Version</h6>
                                <p class="mb-0"><strong id="phpVersion">Loading...</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fas fa-database icon-gradient mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-muted mb-2">MySQL Version</h6>
                                <p class="mb-0"><strong id="mysqlVersion">Loading...</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <i class="fas fa-hdd icon-gradient mb-2" style="font-size: 2rem;"></i>
                                <h6 class="text-muted mb-2">Database Size</h6>
                                <p class="mb-0"><strong id="dbSize">Loading...</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Testimonials Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-card">
                    <h5 class="gradient-text mb-4"><i class="fas fa-quote-right me-2"></i>Testimonials Overview</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-star icon-gradient" style="font-size: 3rem;"></i>
                                <h3 class="gradient-text mt-2"><?php echo $testimonials_count; ?></h3>
                                <p class="text-muted mb-0">Public Testimonials</p>
                                <small class="text-muted">(4+ stars with feedback)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h6 class="gradient-text mb-3">Rating Distribution</h6>
                                <?php for($i = 5; $i >= 1; $i--): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2"><?php echo $i; ?> <i class="fas fa-star text-warning"></i></span>
                                    <div class="progress flex-grow-1" style="height: 20px;">
                                        <div class="progress-bar" style="width: <?php echo $total_feedback > 0 ? (($ratings[$i] ?? 0) / $total_feedback * 100) : 0; ?>%; background: linear-gradient(135deg, #1A237E, #1B5E20);">
                                            <?php echo $ratings[$i] ?? 0; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h6 class="gradient-text mb-3">Top Testimonials</h6>
                                <?php while($test = $top_testimonials->fetch_assoc()): ?>
                                <div class="mb-3 p-2 border-start border-3" style="border-color: #1A237E !important;">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2"><?php echo htmlspecialchars($test['username']); ?></strong>
                                        <span class="text-warning">
                                            <?php for($i = 1; $i <= $test['rating']; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($test['feedback']); ?>
                                    </small>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="gradient-text"><i class="fas fa-users me-2"></i>Recent Users</h5>
                        <a href="users.php" class="btn btn-sm btn-gradient">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Feedback -->
            <div class="col-md-6">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="gradient-text"><i class="fas fa-comments me-2"></i>Recent Feedback</h5>
                        <a href="feedback_admin.php" class="btn btn-sm btn-gradient">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($feedback = $recent_feedback->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($feedback['username']); ?></td>
                                    <td>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
