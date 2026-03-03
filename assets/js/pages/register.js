// Countries and their currencies
const countries = {
    'IN': { name: 'India', currency: 'INR', currencySymbol: '₹' },
    'US': { name: 'United States', currency: 'USD', currencySymbol: '$' },
    'GB': { name: 'United Kingdom', currency: 'GBP', currencySymbol: '£' },
    'EU': { name: 'European Union', currency: 'EUR', currencySymbol: '€' },
    'JP': { name: 'Japan', currency: 'JPY', currencySymbol: '¥' },
    'AU': { name: 'Australia', currency: 'AUD', currencySymbol: 'A$' },
    'CA': { name: 'Canada', currency: 'CAD', currencySymbol: 'C$' },
    'CH': { name: 'Switzerland', currency: 'CHF', currencySymbol: 'Fr' },
    'CN': { name: 'China', currency: 'CNY', currencySymbol: '¥' },
    'HK': { name: 'Hong Kong', currency: 'HKD', currencySymbol: 'HK$' },
    'SG': { name: 'Singapore', currency: 'SGD', currencySymbol: 'S$' },
    'AE': { name: 'United Arab Emirates', currency: 'AED', currencySymbol: 'د.إ' },
    'SA': { name: 'Saudi Arabia', currency: 'SAR', currencySymbol: '﷼' },
    'KR': { name: 'South Korea', currency: 'KRW', currencySymbol: '₩' },
    'MY': { name: 'Malaysia', currency: 'MYR', currencySymbol: 'RM' },
    'TH': { name: 'Thailand', currency: 'THB', currencySymbol: '฿' },
    'ID': { name: 'Indonesia', currency: 'IDR', currencySymbol: 'Rp' },
    'PH': { name: 'Philippines', currency: 'PHP', currencySymbol: '₱' },
    'VN': { name: 'Vietnam', currency: 'VND', currencySymbol: '₫' },
    'PK': { name: 'Pakistan', currency: 'PKR', currencySymbol: '₨' },
    'BD': { name: 'Bangladesh', currency: 'BDT', currencySymbol: '৳' },
    'LK': { name: 'Sri Lanka', currency: 'LKR', currencySymbol: '₨' },
    'NP': { name: 'Nepal', currency: 'NPR', currencySymbol: '₨' }
};

// Initialize form
document.addEventListener('DOMContentLoaded', function () {
    console.log('Register.js loaded successfully');
    populateCountries();
    initializeValidation();
    checkForErrors();
    console.log('Registration form initialized');
});

function populateCountries() {
    const countrySelect = document.getElementById('country');
    const currencySelect = document.getElementById('currency');

    console.log('Populating countries...');

    Object.entries(countries).forEach(([code, data]) => {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = data.name;
        countrySelect.appendChild(option);
    });

    console.log('Countries populated:', Object.keys(countries).length);

    countrySelect.addEventListener('change', function () {
        const selectedCountry = countries[this.value];
        if (selectedCountry) {
            currencySelect.innerHTML = '<option value="">Select your currency</option>';
            const option = document.createElement('option');
            option.value = selectedCountry.currency;
            option.textContent = `${selectedCountry.currency} (${selectedCountry.currencySymbol})`;
            option.selected = true;
            currencySelect.appendChild(option);
        }
    });

    // Auto-select detected country if available
    if (window.detectedCountry && countries[window.detectedCountry]) {
        console.log('Auto-selecting detected country:', window.detectedCountry);
        countrySelect.value = window.detectedCountry;
        // Trigger change event to auto-populate currency field
        countrySelect.dispatchEvent(new Event('change'));
    } else {
        console.log('No valid detected country, manual selection required');
        if (window.detectedCountry === null) {
            console.log('GeoIP detection failed - this is expected for localhost/private IPs');
        }
    }
}

function initializeValidation() {
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('passwordMatch');

    confirmPassword.addEventListener('input', function () {
        const password = document.getElementById('password').value;
        const confirm = this.value;

        if (confirm === '') {
            passwordMatch.textContent = '';
            passwordMatch.className = 'validation-message';
        } else if (password === confirm) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.className = 'validation-message text-success';
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.className = 'validation-message text-danger';
        }
    });

    const email = document.getElementById('email');
    const emailError = document.getElementById('emailError');

    email.addEventListener('blur', function () {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailRegex.test(this.value)) {
            emailError.textContent = '✗ Please enter a valid email address';
        } else {
            emailError.textContent = '';
        }
    });
}

function togglePassword(inputId, button) {
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

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function checkForErrors() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');

    if (error) {
        // Clear URL parameters
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }

        let errorMessage = '';
        switch (error) {
            case 'username_taken':
                errorMessage = "This username is already taken. Please choose another.";
                break;
            case 'email_taken':
                errorMessage = "This email is already registered. Please use another email or login.";
                break;
            case 'password_mismatch':
                errorMessage = "Passwords do not match. Please try again.";
                break;
            case 'invalid_email':
                errorMessage = "Please enter a valid email address.";
                break;
            case 'required_fields':
                errorMessage = "Please fill in all required fields.";
                break;
            case 'db_error':
                errorMessage = "Registration failed due to a system error. Please try again.";
                break;
            default:
                errorMessage = "An error occurred during registration. Please try again.";
        }

        Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: errorMessage,
            confirmButtonColor: '#1A237E'
        });
    }
}
