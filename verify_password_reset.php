<?php
session_start();

// Check if user has pending password reset
if (!isset($_SESSION['password_reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['password_reset_email'];
$username = $_SESSION['password_reset_username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password Reset - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="assets/css/pages/verify_otp.css">
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <div class="verify-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="verify-title">Verify Password Reset</h1>
            <p class="verify-subtitle">Enter the 6-digit code sent to your email</p>
        </div>

        <div class="form-section">
            <div class="info-box">
                <p>We've sent a verification code to</p>
                <p class="email"><?php echo htmlspecialchars($email); ?></p>
            </div>

            <form id="otpForm">
                <div class="form-group">
                    <label class="form-label text-center d-block">Enter Verification Code</label>
                    <div class="otp-input-group">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    </div>
                    <input type="hidden" name="otp" id="otpValue">
                </div>

                <button type="submit" class="btn-verify">
                    <i class="fas fa-check-circle me-2"></i>Verify Code
                </button>
            </form>

            <button type="button" class="btn-resend" id="resendBtn" onclick="resendOTP()">
                <i class="fas fa-redo me-2"></i>Resend Code
            </button>

            <div class="timer" id="timer">
                Code expires in <span id="countdown">10:00</span>
            </div>
        </div>

        <div class="verify-footer">
            <p class="mb-0">Wrong email? 
                <a href="forgot_password.php">Go back</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // OTP Input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpValue = document.getElementById('otpValue');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                const value = e.target.value;
                
                if (value.length === 1 && /^[0-9]$/.test(value)) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
                
                updateOTPValue();
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text');
                const digits = pastedData.replace(/\D/g, '').slice(0, 6);
                
                digits.split('').forEach((digit, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = digit;
                    }
                });
                
                updateOTPValue();
                
                if (digits.length === 6) {
                    otpInputs[5].focus();
                }
            });
        });

        function updateOTPValue() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            otpValue.value = otp;
        }

        // Form submission
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const otp = otpValue.value;
            
            if (otp.length !== 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Code',
                    text: 'Please enter the complete 6-digit verification code.',
                    confirmButtonColor: '#1A237E'
                });
                return;
            }
            
            // Show loading
            Swal.fire({
                title: 'Verifying...',
                text: 'Please wait while we verify your code.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('process_password_reset_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'otp=' + encodeURIComponent(otp)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Verified!',
                        text: 'Redirecting to password reset page...',
                        confirmButtonColor: '#1A237E',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'reset_password.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: data.error,
                        confirmButtonColor: '#1A237E'
                    });
                    
                    // Clear inputs on error
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
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

        // Countdown timer
        let timeLeft = 600; // 10 minutes in seconds
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                Swal.fire({
                    icon: 'warning',
                    title: 'Code Expired',
                    text: 'Your verification code has expired. Please request a new one.',
                    confirmButtonColor: '#1A237E'
                }).then(() => {
                    window.location.href = 'forgot_password.php';
                });
            }
            
            timeLeft--;
        }

        const countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();

        // Resend OTP function
        function resendOTP() {
            Swal.fire({
                title: 'Resending Code...',
                text: 'Please wait while we send a new verification code.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('resend_password_reset_otp.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent!',
                        text: 'A new verification code has been sent to your email.',
                        confirmButtonColor: '#1A237E'
                    });
                    
                    // Reset timer
                    timeLeft = 600;
                    updateCountdown();
                    
                    // Clear inputs
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error,
                        confirmButtonColor: '#1A237E'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to resend code. Please try again.',
                    confirmButtonColor: '#1A237E'
                });
            });
        }

        // Focus first input on load
        otpInputs[0].focus();
    </script>
</body>
</html>