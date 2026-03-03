<?php
require_once('config/db.php');

// Get total number of users (with error handling)
$total_users = 0;
try {
    $query = "SELECT COUNT(*) as total_users FROM users";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $total_users = $row['total_users'];
    }
} catch (Exception $e) {
    // Database tables don't exist yet
    $total_users = 0;
}

// Format the number (e.g., 1000 becomes 1K)
function formatNumber($num) {
    if($num >= 1000) {
        return round($num/1000, 1) . 'K+';
    }
    return $num . '+';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Tally - Your Personal Cash Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages/index.css">
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-wallet" style="background: linear-gradient(45deg, var(--primary-color), var(--dark-green)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i> 
                <span class="gradient-text">Wallet Tally</span>
            </a>
            <div class="navbar-buttons">
                <a class="nav-link" href="#features" id="featuresBtn">Features</a>
                <a class="nav-link" href="#testimonials" id="wousBtn">WOUS</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <!-- Add the marquee container before hero-content -->
        <div class="marquee-container">
            <div class="marquee-row">
                <img src="https://img.icons8.com/color/96/000000/wallet.png" alt="Wallet">
                <img src="https://img.icons8.com/color/96/000000/money-bag.png" alt="Money Bag">
                <img src="https://img.icons8.com/color/96/000000/card-security.png" alt="Security">
                <img src="https://img.icons8.com/color/96/000000/bank-cards.png" alt="Cards">
                <img src="https://img.icons8.com/color/96/000000/money-transfer.png" alt="Transfer">
                <img src="https://img.icons8.com/color/96/000000/deposit.png" alt="Deposit">
                <!-- Repeat the images to ensure continuous flow -->
                <img src="https://img.icons8.com/color/96/000000/wallet.png" alt="Wallet">
                <img src="https://img.icons8.com/color/96/000000/money-bag.png" alt="Money Bag">
                <img src="https://img.icons8.com/color/96/000000/card-security.png" alt="Security">
                <img src="https://img.icons8.com/color/96/000000/bank-cards.png" alt="Cards">
                <img src="https://img.icons8.com/color/96/000000/money-transfer.png" alt="Transfer">
                <img src="https://img.icons8.com/color/96/000000/deposit.png" alt="Deposit">
            </div>
            <div class="marquee-row">
                <img src="https://img.icons8.com/color/96/000000/money-box.png" alt="Money Box">
                <img src="https://img.icons8.com/color/96/000000/cash-in-hand.png" alt="Cash">
                <img src="https://img.icons8.com/color/96/000000/merchant-account.png" alt="Account">
                <img src="https://img.icons8.com/color/96/000000/receive-cash.png" alt="Receive">
                <img src="https://img.icons8.com/color/96/000000/budget.png" alt="Budget">
                <img src="https://img.icons8.com/color/96/000000/card-in-use.png" alt="Card Use">
                <!-- Repeat the images to ensure continuous flow -->
                <img src="https://img.icons8.com/color/96/000000/money-box.png" alt="Money Box">
                <img src="https://img.icons8.com/color/96/000000/cash-in-hand.png" alt="Cash">
                <img src="https://img.icons8.com/color/96/000000/merchant-account.png" alt="Account">
                <img src="https://img.icons8.com/color/96/000000/receive-cash.png" alt="Receive">
                <img src="https://img.icons8.com/color/96/000000/budget.png" alt="Budget">
                <img src="https://img.icons8.com/color/96/000000/card-in-use.png" alt="Card Use">
            </div>
        </div>
        
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-4">Track Your Cash Flow</h1>
            <p class="lead mb-4"><strong>Simple, fast, and efficient way to record your daily income and expenses. Keep track of where your money goes!</strong></p>
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary btn-lg me-3" style="background: linear-gradient(45deg, var(--primary-color), var(--dark-green)); color: white;">Start Saving</a>
                <a href="login.php" class="btn btn-primary btn-lg" style="background: linear-gradient(45deg, var(--primary-color), var(--dark-green)); color: white;">Login</a>
            </div>
        </div>
    </div>

    <!-- Why Choose Section -->
    <section id="features" class="why-choose-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Wallet Tally?</h2>
            <div class="row g-4">
                <!-- Feature 1: Simple Recording -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-plus-circle" style="color: white;"></i>
                        </div>
                        <h3>Simple Recording</h3>
                        <p>Add your daily cash transactions with just a few clicks. Whether it's income or expense, recording is quick and easy.</p>
                    </div>
                </div>

                <!-- Feature 2: Instant Balance -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-wallet" style="color: white;"></i>
                        </div>
                        <h3>Instant Balance</h3>
                        <p>Get your current cash balance instantly. See how much money you have after each transaction.</p>
                    </div>
                </div>

                <!-- Feature 3: Easy Tracking -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-list" style="color: white;"></i>
                        </div>
                        <h3>Easy Tracking</h3>
                        <p>Keep track of all your cash movements. Know exactly where your money comes from and where it goes.</p>
                    </div>
                </div>

                <!-- Feature 4: Basic Categories -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-tag text-white fa-2x"></i>
                        </div>
                        <h3>Custom Categories</h3>
                        <p>Create your own personalized categories for both income and expenses. You have the freedom to organize transactions exactly how you want them.</p>
                    </div>
                </div>

                <!-- Feature 5: Clear Overview -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-eye text-white fa-2x"></i>
                        </div>
                        <h3>Clear Overview</h3>
                        <p>See your total income and expenses at a glance. Simple dashboard shows your financial status clearly.</p>
                    </div>
                </div>

                <!-- Feature 6: Transaction History -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-history text-white fa-2x"></i>
                        </div>
                        <h3>Transaction History</h3>
                        <p>Access all your past transactions easily. Never forget any cash movement with complete transaction records.</p>
                    </div>
                </div>

                <!-- Feature 7: PDF Export -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-file-pdf text-white fa-2x"></i>
                        </div>
                        <h3>PDF Export</h3>
                        <p>Export your transactions to PDF with custom date ranges. Perfect for record-keeping.</p>
                    </div>
                </div>

                <!-- Feature 8: 24 Hours Edit Window -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-clock text-white fa-2x"></i>
                        </div>
                        <h3>24 Hours to Edit</h3>
                        <p>Made a mistake? No problem! Edit your transactions within 24 hours of creation to keep your records accurate.</p>
                    </div>
                </div>

                <!-- Feature 9: Graphical Representation -->
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="icon-wrapper gradient-bg mb-3">
                            <i class="fas fa-chart-pie text-white fa-2x"></i>
                        </div>
                        <h3>Pie Chart Visualization</h3>
                        <p>Visualize your spending patterns with interactive pie charts. See where your money goes at a glance with beautiful graphical representations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials-section py-5" style="background-color: var(--background-light);">
        <div class="container">
            <h2 class="text-center mb-5">What Our Users Say</h2>
            <div class="row g-4" id="testimonialsContainer">
                <!-- Testimonials will be loaded here dynamically -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="py-4" style="background: linear-gradient(135deg, var(--primary-color), var(--dark-green)); color: white;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Wallet Tally. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="terms-and-conditions.php" class="text-white text-decoration-none me-3">Terms & Conditions</a>
                    <a href="privacy-policy.php" class="text-white text-decoration-none me-3">Privacy Policy</a>
                    <a href="mailto:support@wallettally.com" class="text-white text-decoration-none">Support</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Smooth Scroll Script -->
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/testimonials.js"></script>
    <script src="assets/js/pages/index.js"></script>
</body>
</html> 