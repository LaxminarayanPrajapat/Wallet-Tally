<?php
session_start();

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    header('Location: register.php');
    exit();
}

$email = $_SESSION['pending_registration']['email'];
$username = $_SESSION['pending_registration']['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Wallet Tally</title>
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
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h1 class="verify-title">Verify Your Email</h1>
            <p class="verify-subtitle">Enter the 6-digit code sent to your email</p>
        </div>

        <?php if(isset($_SESSION['error_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    text: '<?php echo addslashes($_SESSION['error_message']); ?>',
                    confirmButtonColor: '#1A237E'
                });
            });
        </script>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="form-section">
            <div class="info-box">
                <p>We've sent a verification code to</p>
                <p class="email"><?php echo htmlspecialchars($email); ?></p>
            </div>

            <form id="otpForm" action="process_otp_verification.php" method="POST">
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
                    <i class="fas fa-check-circle me-2"></i>Verify Email
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
                <a href="register.php">Go back to registration</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="assets/js/pages/verify_otp.js?v=<?php echo time(); ?>"></script>
</body>
</html>
