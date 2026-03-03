<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Wallet Tally</title>
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
        
        .privacy-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .privacy-hero {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .privacy-hero h1 {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .privacy-hero .subtitle {
            color: #666;
            font-size: 1.2rem;
            font-weight: 300;
        }
        
        .privacy-card {
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
        
        .success-card {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
        }
        
        .success-card h4 {
            color: #155724;
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
        
        .email-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .email-type-card {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
            border-radius: 15px;
            padding: 25px;
            border-left: 5px solid var(--dark-green);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .email-type-card h5 {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .email-type-card h5 i {
            margin-right: 10px;
            color: var(--primary-color);
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
            .privacy-hero {
                padding: 40px 20px;
            }
            
            .privacy-hero h1 {
                font-size: 2rem;
            }
            
            .privacy-card {
                padding: 30px 20px;
            }
            
            .floating-nav {
                display: none;
            }
            
            .email-types {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="privacy-wrapper">
        <!-- Hero Section -->
        <div class="privacy-hero">
            <h1><i class="fas fa-shield-alt me-3"></i>Privacy Policy</h1>
            <p class="subtitle">How we protect and handle your personal information with care</p>
        </div>

        <!-- Last Updated -->
        <div class="last-updated">
            <i class="fas fa-calendar-alt me-2"></i>
            <strong>Last Updated:</strong> <?php echo date('F d, Y'); ?>
        </div>

        <!-- Main Content -->
        <div class="privacy-card">
            <!-- Our Commitment -->
            <div class="highlight-card">
                <h4><i class="fas fa-heart me-2"></i>Our Privacy Commitment</h4>
                <p class="mb-0">At Wallet Tally, we are committed to protecting your privacy and ensuring the security of your personal and financial information. This policy explains how we collect, use, and protect your data with complete transparency.</p>
            </div>

            <!-- Section 1: Information Collection -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-database"></i></div>
                <h2>1. Information We Collect</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-user-circle"></i>Account Information</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-id-card"></i>Username and email address</li>
                    <li><i class="fas fa-lock"></i>Password (encrypted and hashed)</li>
                    <li><i class="fas fa-globe"></i>Country and currency preferences</li>
                    <li><i class="fas fa-camera"></i>Profile picture (optional)</li>
                    <li><i class="fas fa-birthday-cake"></i>Date of birth</li>
                </ul>
            </div>

            <div class="subsection">
                <h4><i class="fas fa-chart-line"></i>Financial Data</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-receipt"></i>Transaction records (income and expenses)</li>
                    <li><i class="fas fa-tags"></i>Custom categories you create</li>
                    <li><i class="fas fa-chart-pie"></i>Financial summaries and reports</li>
                    <li><i class="fas fa-money-bill"></i>Currency and amount information</li>
                </ul>
            </div>

            <div class="subsection">
                <h4><i class="fas fa-analytics"></i>Usage Information</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-clock"></i>Login timestamps and activity logs</li>
                    <li><i class="fas fa-mouse-pointer"></i>Feature usage patterns</li>
                    <li><i class="fas fa-desktop"></i>Device and browser information</li>
                    <li><i class="fas fa-map-marker-alt"></i>IP address for security purposes</li>
                </ul>
            </div>

            <!-- Section 2: How We Use Information -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <h2>2. How We Use Your Information</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-tools"></i>Service Provision</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-wallet"></i>Provide personal cash management features</li>
                    <li><i class="fas fa-file-alt"></i>Generate financial reports and summaries</li>
                    <li><i class="fas fa-user-cog"></i>Maintain your account and preferences</li>
                    <li><i class="fas fa-download"></i>Enable data export functionality</li>
                </ul>
            </div>

            <!-- Section 3: Email Communications -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-envelope"></i></div>
                <h2>3. Email Communications</h2>
            </div>
            
            <div class="success-card">
                <h4><i class="fas fa-email me-2"></i>Our Email Promise</h4>
                <p class="mb-0">We use email to enhance your experience and keep you informed. All emails are sent with your privacy and preferences in mind. You have full control over what communications you receive.</p>
            </div>

            <div class="email-types">
                <div class="email-type-card">
                    <h5><i class="fas fa-bell"></i>Account Notifications</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-user-plus"></i>Welcome emails for new accounts</li>
                        <li><i class="fas fa-key"></i>Password reset confirmations</li>
                        <li><i class="fas fa-shield-alt"></i>Security alerts and login notifications</li>
                        <li><i class="fas fa-exclamation-triangle"></i>Important account changes</li>
                    </ul>
                </div>
                
                <div class="email-type-card">
                    <h5><i class="fas fa-star"></i>Feedback Appreciation</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-trophy"></i>Congratulations when your review is featured</li>
                        <li><i class="fas fa-thumbs-up"></i>Thank you messages for 5-star reviews</li>
                        <li><i class="fas fa-home"></i>Notification when review appears on homepage</li>
                        <li><i class="fas fa-community"></i>Recognition as valued community member</li>
                    </ul>
                </div>
                
                <div class="email-type-card">
                    <h5><i class="fas fa-info-circle"></i>Administrative Notices</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-trash"></i>Account deletion notifications with reasons</li>
                        <li><i class="fas fa-comment-slash"></i>Feedback removal explanations</li>
                        <li><i class="fas fa-gavel"></i>Policy violation notices</li>
                        <li><i class="fas fa-life-ring"></i>Support and appeal information</li>
                    </ul>
                </div>
                
                <div class="email-type-card">
                    <h5><i class="fas fa-newspaper"></i>Service Updates</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-rocket"></i>New feature announcements</li>
                        <li><i class="fas fa-wrench"></i>Maintenance notifications</li>
                        <li><i class="fas fa-file-contract"></i>Terms and policy updates</li>
                        <li><i class="fas fa-bug"></i>Important bug fixes and improvements</li>
                    </ul>
                </div>
            </div>

            <div class="info-card">
                <h4><i class="fas fa-user-cog me-2"></i>Email Preferences Control</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-toggle-on"></i>Choose which types of emails you want to receive</li>
                    <li><i class="fas fa-times-circle"></i>Unsubscribe from non-essential communications</li>
                    <li><i class="fas fa-shield-check"></i>Security emails cannot be disabled for your protection</li>
                    <li><i class="fas fa-cog"></i>Manage preferences through your account settings</li>
                </ul>
            </div>

            <!-- Section 4: Information Sharing -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-user-secret"></i></div>
                <h2>4. Information Sharing</h2>
            </div>
            
            <div class="warning-card">
                <h4><i class="fas fa-lock me-2"></i>We Do NOT Share Your Data</h4>
                <p class="mb-0">We do not sell, rent, or share your personal or financial information with third parties for marketing purposes. Your financial data remains private and is only accessible to you. Your trust is our priority.</p>
            </div>

            <!-- Section 5: Data Security -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-shield-virus"></i></div>
                <h2>5. Data Security</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-lock"></i>Security Measures</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-key"></i>Password encryption using industry-standard hashing</li>
                    <li><i class="fas fa-certificate"></i>Secure HTTPS connections for all data transmission</li>
                    <li><i class="fas fa-sync"></i>Regular security updates and monitoring</li>
                    <li><i class="fas fa-user-shield"></i>Access controls and authentication systems</li>
                </ul>
            </div>

            <!-- Section 6: Your Rights -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-balance-scale"></i></div>
                <h2>6. Your Rights</h2>
            </div>
            
            <div class="info-card">
                <h4><i class="fas fa-crown me-2"></i>You Are In Control</h4>
                <ul class="feature-list">
                    <li><i class="fas fa-eye"></i>View and update your account information</li>
                    <li><i class="fas fa-download"></i>Export your financial data at any time</li>
                    <li><i class="fas fa-trash-alt"></i>Delete your account and all associated data</li>
                    <li><i class="fas fa-cog"></i>Control your privacy and email settings</li>
                    <li><i class="fas fa-question-circle"></i>Request information about data we hold</li>
                </ul>
            </div>

            <!-- Section 7: Contact Information -->
            <div class="section-header">
                <div class="icon"><i class="fas fa-phone"></i></div>
                <h2>7. Contact Us</h2>
            </div>
            
            <div class="subsection">
                <h4><i class="fas fa-envelope"></i>Get In Touch</h4>
                <p>If you have questions about this Privacy Policy or our data practices, please contact us:</p>
                <ul class="feature-list">
                    <li><i class="fas fa-at"></i><strong>Privacy Email:</strong> privacy@wallettally.com</li>
                    <li><i class="fas fa-life-ring"></i><strong>General Support:</strong> support@wallettally.com</li>
                    <li><i class="fas fa-globe"></i><strong>Website:</strong> www.wallettally.com</li>
                </ul>
            </div>

            <!-- Final Trust Message -->
            <div class="highlight-card mt-5">
                <h4><i class="fas fa-handshake me-2"></i>Your Trust Matters</h4>
                <p class="mb-0">Your privacy and trust are important to us. We are committed to being transparent about our data practices and protecting your personal information. Thank you for trusting Wallet Tally with your financial data and allowing us to serve you better through thoughtful communication.</p>
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
        <a href="terms-and-conditions.php" title="Terms & Conditions"><i class="fas fa-file-contract"></i></a>
        <a href="mailto:privacy@wallettally.com" title="Privacy Email"><i class="fas fa-envelope"></i></a>
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