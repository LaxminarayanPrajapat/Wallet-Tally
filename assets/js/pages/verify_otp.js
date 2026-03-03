// Check for error messages
document.addEventListener('DOMContentLoaded', function () {
    checkForMessages();
    initializeOTPInputs();
    startCountdown();
});

function checkForMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const resend = urlParams.get('resend');

    // Check for resend notification
    if (resend === '1') {
        Swal.fire({
            icon: 'info',
            title: 'Pending Verification',
            text: 'You already have a pending registration. Please check your email for the OTP code or click "Resend Code".',
            confirmButtonColor: '#1A237E'
        });

        // Clear URL parameter
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    }
}

function initializeOTPInputs() {
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpForm = document.getElementById('otpForm');
    const otpValue = document.getElementById('otpValue');

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;

            // Only allow numbers
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }

            // Move to next input
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }

            // Update hidden input
            updateOTPValue();
        });

        input.addEventListener('keydown', (e) => {
            // Handle backspace
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }

            // Handle paste
            if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                navigator.clipboard.readText().then(text => {
                    const digits = text.replace(/\D/g, '').slice(0, 6);
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
            }
        });
    });

    function updateOTPValue() {
        const otp = Array.from(otpInputs).map(input => input.value).join('');
        otpValue.value = otp;
    }

    // Form submission
    otpForm.addEventListener('submit', (e) => {
        e.preventDefault();
        updateOTPValue();

        if (otpValue.value.length !== 6) {
            Swal.fire({
                icon: 'error',
                title: 'Incomplete Code',
                text: 'Please enter all 6 digits',
                confirmButtonColor: '#1A237E'
            });
            return;
        }

        otpForm.submit();
    });

    // Focus first input on load
    otpInputs[0].focus();
}

function startCountdown() {
    let timeLeft = 600; // 10 minutes in seconds
    const countdownElement = document.getElementById('countdown');
    const timerElement = document.getElementById('timer');

    function updateCountdown() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (timeLeft <= 60) {
            timerElement.classList.add('warning');
        }

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            Swal.fire({
                icon: 'warning',
                title: 'Code Expired',
                text: 'Your verification code has expired. Please request a new one.',
                confirmButtonColor: '#1A237E'
            });
        }

        timeLeft--;
    }

    const countdownInterval = setInterval(updateCountdown, 1000);
}

// Resend OTP
let resendCooldown = 0;

function resendOTP() {
    const resendBtn = document.getElementById('resendBtn');
    const otpInputs = document.querySelectorAll('.otp-input');
    const timerElement = document.getElementById('timer');

    if (resendCooldown > 0) {
        return;
    }

    resendBtn.disabled = true;

    fetch('resend_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Code Resent',
                    text: 'A new verification code has been sent to your email',
                    confirmButtonColor: '#1A237E'
                });

                // Reset timer
                startCountdown();
                timerElement.classList.remove('warning');

                // Clear inputs
                otpInputs.forEach(input => input.value = '');
                otpInputs[0].focus();

                // Cooldown for resend button
                resendCooldown = 60;
                updateResendButton();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Resend Failed',
                    text: data.message || 'Failed to resend code. Please try again.',
                    confirmButtonColor: '#1A237E'
                });
                resendBtn.disabled = false;
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.',
                confirmButtonColor: '#1A237E'
            });
            resendBtn.disabled = false;
        });
}

function updateResendButton() {
    const resendBtn = document.getElementById('resendBtn');

    if (resendCooldown > 0) {
        resendBtn.innerHTML = `<i class="fas fa-clock me-2"></i>Resend in ${resendCooldown}s`;
        resendCooldown--;
        setTimeout(updateResendButton, 1000);
    } else {
        resendBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Resend Code';
        resendBtn.disabled = false;
    }
}
