<?php
session_start();

// Check if user has verified OTP
if (!isset($_SESSION['password_reset_verified']) || !isset($_SESSION['password_reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['password_reset_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages/login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="auth-title">Reset Password</h1>
                <p class="auth-subtitle">Create a new secure password for your account</p>
            </div>
            
            <div class="auth-body">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
                
                <form id="resetPasswordForm">
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>New Password</label>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                        <label for="confirmPassword"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="password-requirements mb-4">
                        <p class="mb-2"><small class="text-muted">Password must contain:</small></p>
                        <ul class="list-unstyled">
                            <li id="length" class="requirement"><i class="fas fa-times text-danger me-2"></i>At least 8 characters</li>
                            <li id="uppercase" class="requirement"><i class="fas fa-times text-danger me-2"></i>One uppercase letter</li>
                            <li id="lowercase" class="requirement"><i class="fas fa-times text-danger me-2"></i>One lowercase letter</li>
                            <li id="number" class="requirement"><i class="fas fa-times text-danger me-2"></i>One number</li>
                        </ul>
                    </div>
                    
                    <button type="submit" class="btn btn-login" id="submitBtn" disabled>
                        <i class="fas fa-save me-2"></i>Update Password
                    </button>
                </form>
            </div>
            
            <div class="auth-footer">
                <p class="mb-0">Remember your password? <a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('submitBtn');
        
        // Password visibility toggles
        document.getElementById('togglePassword').addEventListener('click', function() {
            togglePasswordVisibility('password', this);
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            togglePasswordVisibility('confirmPassword', this);
        });
        
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password validation
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePassword);
        
        function validatePassword() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Check requirements
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(req);
                const icon = element.querySelector('i');
                
                if (requirements[req]) {
                    icon.classList.remove('fa-times', 'text-danger');
                    icon.classList.add('fa-check', 'text-success');
                } else {
                    icon.classList.remove('fa-check', 'text-success');
                    icon.classList.add('fa-times', 'text-danger');
                }
            });
            
            // Check if all requirements are met and passwords match
            const allRequirementsMet = Object.values(requirements).every(req => req);
            const passwordsMatch = password === confirmPassword && password.length > 0;
            
            // Update confirm password field styling
            if (confirmPassword.length > 0) {
                if (passwordsMatch) {
                    confirmPasswordInput.classList.remove('is-invalid');
                    confirmPasswordInput.classList.add('is-valid');
                } else {
                    confirmPasswordInput.classList.remove('is-valid');
                    confirmPasswordInput.classList.add('is-invalid');
                }
            } else {
                confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
            }
            
            // Enable/disable submit button
            submitBtn.disabled = !(allRequirementsMet && passwordsMatch);
        }
        
        // Form submission
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match. Please try again.',
                    confirmButtonColor: '#1A237E'
                });
                return;
            }
            
            // Show loading
            Swal.fire({
                title: 'Updating Password...',
                text: 'Please wait while we update your password.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('process_password_reset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Updated!',
                        text: 'Your password has been successfully updated. You can now login with your new password.',
                        confirmButtonColor: '#1A237E'
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: data.error,
                        confirmButtonColor: '#1A237E'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#1A237E'
                });
            });
        });
    </script>
    
    <style>
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e9ecef;
        }
        
        .requirement {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .requirement:last-child {
            margin-bottom: 0;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #1A237E;
        }
        
        .form-floating {
            position: relative;
        }
        
        .forgot-password-link {
            float: right;
            font-size: 14px;
            color: #1A237E;
            text-decoration: none;
        }
        
        .forgot-password-link:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>