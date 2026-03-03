<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-green) 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .terms-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .terms-hero {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .terms-hero h1 {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .terms-hero .subtitle {
            color: #666;
            font-size: 1.2rem;
            font-weight: 300;
        }
        
        .terms-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-green));
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .section-header .icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.5rem;
        }
        
        .section-header h2 {
            color: white;
            font-weight: 600;
            margin: 0;
            font-size: 1.8rem;
        }
        
        .subsection {
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            border-radius: 15px;
            border-left: 5px solid var(--primary-color);
        }
        
        .subsection h4 {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .subsection h4 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .highlight-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.2);
        }
        
        .highlight-card h4 {
            color: #856404;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .warning-card {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.2);
        }
        
        .warning-card h4 {
            color: #721c24;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .info-card {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(23, 162, 184, 0.2);
        }
        
        .info-card h4 {
            color: #0c5460;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: var(--primary-color);
            margin-right: 15px;
            width: 20px;
        }
        
        .last-updated {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            color: #1976d2;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(25, 118, 210, 0.2);
        }
        
        .back-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-green));
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .back-btn:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .floating-nav {
            position: fixed;
            top: 50%;
            right: 30px;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .floating-nav a {
            display: block;
            padding: 10px;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .floating-nav a:hover {
            background: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .terms-hero {
                padding: 40px 20px;
            }
            
            .terms-hero h1 {
                font-size: 2rem;
            }
            
            .terms-card {
                padding: 30px 20px;
            }
            
            .floating-nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="terms-wrapper">
        <!-- Hero Section -->
        <div class="terms-hero">
            <h1><i class="fas fa-file-contract me-3"></i>Terms & Conditions</h1>
            <p class="subtitle">Your guide to using Wallet Tally responsibly and securely</p>
        </div>

        <!-- Last Updated -->
        <div class="last-updated">
            <i class="fas fa-calendar-alt me-2"></i>
            <strong>Last Updated:</strong> <?php echo date('F d, Y'); ?>
        </div>

        <!-- Main Content -->
        <div class="terms-card">
            <!-- Important Notice -->
            <div class="highlight-card">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Important Notice</h4>
                <p class="mb-0">By accessing and using Wallet Tally, you accept and agree to be bound by the terms and provision of this agreement. Please read these terms carefully before using our service.</p>
            </div>

            <!-- Section 1: Acceptance -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-handshake"></i></div>
                <h2>1. Acceptance of Terms</h2>
            </div>
            <p>By creating an account and using Wallet Tally, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions. If you do not agree to these terms, please do not use our service.</p>

            <!-- Section 2: Service Description -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <h2>2. Service Description</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-check-circle"></i>What We Provide</h4>
                <p>Wallet Tally is a personal cash management system that allows users to:</p>
                <ul class="feature-list">
                    <li><i class="fas fa-chart-line"></i>Track daily income and expenses</li>
                    <li><i class="fas fa-tags"></i>Categorize transactions</li>
                    <li><i class="fas fa-chart-pie"></i>View financial summaries and reports</li>
                    <li><i class="fas fa-file-pdf"></i>Export transaction data to PDF</li>
                    <li><i class="fas fa-database"></i>Manage personal financial records</li>
                </ul>
            </div>

            <div class="subsection">
                <h4><i class="fas fa-info-circle"></i>Service Limitations</h4>
                <p>Wallet Tally is designed for personal cash tracking only. It does not:</p>
                <ul class="feature-list">
                    <li><i class="fas fa-times-circle"></i>Connect to bank accounts or financial institutions</li>
                    <li><i class="fas fa-times-circle"></i>Provide financial advice or investment guidance</li>
                    <li><i class="fas fa-times-circle"></i>Handle actual money transactions</li>
                    <li><i class="fas fa-times-circle"></i>Guarantee data backup or recovery</li>
                </ul>
            </div>

            <!-- Section 3: User Responsibilities -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-user-shield"></i></div>
                <h2>3. User Responsibilities</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-lock"></i>Account Security</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-key"></i>You are responsible for maintaining the confidentiality of your account credentials</li>
                    <li><i class="fas fa-bell"></i>You must notify us immediately of any unauthorized use of your account</li>
                    <li><i class="fas fa-user-check"></i>You are responsible for all activities that occur under your account</li>
                </ul>
            </div>

            <div class="subsection">
                <h4><i class="fas fa-clipboard-check"></i>Accurate Information</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-edit"></i>You agree to provide accurate and complete information when registering</li>
                    <li><i class="fas fa-sync"></i>You are responsible for keeping your account information up to date</li>
                    <li><i class="fas fa-balance-scale"></i>You agree to use the service for legitimate personal financial tracking only</li>
                </ul>
            </div>

            <!-- Section 4: Prohibited Activities -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-ban"></i></div>
                <h2>4. Prohibited Activities</h2>
            </div>
            
            <div class="warning-card">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>You agree not to:</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-gavel"></i>Use the service for any illegal or unauthorized purpose</li>
                    <li><i class="fas fa-user-slash"></i>Attempt to gain unauthorized access to other users' accounts</li>
                    <li><i class="fas fa-virus"></i>Upload malicious code or attempt to disrupt the service</li>
                    <li><i class="fas fa-clone"></i>Create multiple accounts to circumvent system limitations</li>
                    <li><i class="fas fa-share-alt"></i>Share your account credentials with others</li>
                    <li><i class="fas fa-robot"></i>Use automated tools to access the service without permission</li>
                    <li><i class="fas fa-comment-slash"></i>Submit false or misleading feedback or reviews</li>
                </ul>
            </div>

            <!-- Section 5: Data and Privacy -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h2>5. Data and Privacy</h2>
            </div>
            
            <div class="info-card">
                <h4><i class="fas fa-database me-2"></i>Your Data Rights</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-crown"></i>You retain ownership of all financial data you input into the system</li>
                    <li><i class="fas fa-user-secret"></i>We do not share your personal financial information with third parties</li>
                    <li><i class="fas fa-shield-virus"></i>We implement security measures to protect your data</li>
                    <li><i class="fas fa-download"></i>You can export your data at any time</li>
                </ul>
            </div>

            <!-- Continue with remaining sections... -->
            <!-- Section 6: Account Termination -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-user-times"></i></div>
                <h2>6. Account Termination</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-sign-out-alt"></i>Voluntary Termination</h4>
                <p>You may delete your account at any time through your account settings. Upon deletion, all your data will be permanently removed.</p>
            </div>

            <div class="subsection">
                <h4><i class="fas fa-gavel"></i>Administrative Termination</h4>
                <p>We reserve the right to terminate accounts that violate our terms. In case of administrative termination, you will be notified via email with the reason for account closure.</p>
            </div>

            <!-- Final Agreement -->
            <div class="highlight-card mt-5">
                <h4><i class="fas fa-handshake me-2"></i>Your Agreement</h4>
                <p class="mb-0">By using Wallet Tally, you acknowledge that you have read and understood these Terms & Conditions and agree to be bound by them. Thank you for choosing Wallet Tally for your personal cash management needs.</p>
            </div>
        </div>

        <!-- Register Button -->
        <div class="text-center">
            <a href="register.php" class="back-btn" style="background: linear-gradient(135deg, var(--dark-green), var(--primary-color));">
                <i class="fas fa-user-plus"></i>
                Create Account
            </a>
        </div>
    </div>

    <!-- Floating Navigation -->
    <div class="floating-nav d-none d-lg-block">
        <a href="#" onclick="scrollToTop(); return false;" title="Back to Top"><i class="fas fa-arrow-up"></i></a>
        <a href="privacy-policy.php" title="Privacy Policy"><i class="fas fa-shield-alt"></i></a>
        <a href="mailto:support@wallettally.com" title="Support"><i class="fas fa-envelope"></i></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>