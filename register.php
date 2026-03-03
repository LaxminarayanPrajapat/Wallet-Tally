<?php
session_start();
require_once('config/db.php');

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Country will be selected manually by user
$countryCode = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages/register.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">
                <i class="fas fa-wallet"></i>
            </div>
            <h1 class="register-title">Create Account</h1>
            <p class="register-subtitle">Join Wallet Tally and start managing your finances</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
        
        <?php endif; ?>

        <div class="form-section">
            <form id="registrationForm" action="process_register.php" method="POST" enctype="multipart/form-data">
                
                <!-- Profile Picture Upload -->
                <div class="profile-upload">
                    <input type="file" class="profile-picture-input" name="profile_picture" id="profile_picture" accept="image/*" onchange="previewImage(this)">
                    <img id="imagePreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%23e5e7eb'/%3E%3Cpath d='M50 45c8.284 0 15-6.716 15-15s-6.716-15-15-15-15 6.716-15 15 6.716 15 15 15zm0 5c-10 0-30 5-30 15v10h60V65c0-10-20-15-30-15z' fill='%239ca3af'/%3E%3C/svg%3E" alt="Profile" class="profile-preview" onclick="document.getElementById('profile_picture').click()">
                    <label for="profile_picture" class="profile-upload-label">
                        <i class="fas fa-camera me-1"></i>Click to upload profile picture
                    </label>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control" name="username" required placeholder="Choose a username">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control" name="email" id="email" required placeholder="your@email.com">
                    </div>
                    <span id="emailError" class="validation-message text-danger"></span>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" name="password" id="password" required placeholder="Create a password">
                        <button class="password-toggle" type="button" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required placeholder="Confirm your password">
                        <button class="password-toggle" type="button" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span id="passwordMatch" class="validation-message"></span>
                </div>

                <!-- Country -->
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <div class="input-wrapper">
                        <i class="fas fa-globe input-icon"></i>
                        <select class="form-select" name="country" id="country" required>
                            <option value="">Select your country</option>
                        </select>
                    </div>
                </div>

                <!-- Currency -->
                <div class="form-group">
                    <label class="form-label">Currency</label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill input-icon"></i>
                        <select class="form-select" name="currency" id="currency" required>
                            <option value="">Select your currency</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
        </div>

        <div class="register-footer">
            <p class="mb-2">Already have an account? 
                <a href="login.php">Login here</a>
            </p>
            <p class="mb-0 text-muted small">
                By creating an account, you agree to our 
                <a href="terms-and-conditions.php" class="text-decoration-none">Terms & Conditions</a> and 
                <a href="privacy-policy.php" class="text-decoration-none">Privacy Policy</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Pass detected country code to JavaScript
        window.detectedCountry = <?php echo json_encode($countryCode); ?>;
    </script>
    
    <script src="assets/js/pages/register.js?v=<?php echo time(); ?>"></script>
</body>
</html>
