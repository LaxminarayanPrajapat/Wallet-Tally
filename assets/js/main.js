// Main JavaScript file for common functionality across all pages

// Session timeout handling
let timeout;
const timeoutDuration = 30 * 60 * 1000; // 30 minutes in milliseconds

function resetTimeout() {
    clearTimeout(timeout);
    timeout = setTimeout(logout, timeoutDuration);
}

function logout() {
    window.location.href = 'logout.php?msg=timeout';
}

// Reset timeout on user activity
document.addEventListener('mousemove', resetTimeout);
document.addEventListener('keypress', resetTimeout);
document.addEventListener('click', resetTimeout);
document.addEventListener('scroll', resetTimeout);

// Start the timeout when page loads
document.addEventListener('DOMContentLoaded', function () {
    resetTimeout();
});

// Handle visibility change
document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
        clearTimeout(timeout);
    } else {
        resetTimeout();
    }
});

// Handle beforeunload event
window.addEventListener('beforeunload', function () {
    clearTimeout(timeout);
});

// Smooth scroll functionality for navigation links
document.addEventListener('DOMContentLoaded', function () {
    const featuresBtn = document.getElementById('featuresBtn');
    const wousBtn = document.getElementById('wousBtn');

    // Features button scroll
    if (featuresBtn) {
        featuresBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const featuresSection = document.getElementById('features');

            if (featuresSection) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = featuresSection.offsetTop - navbarHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    }

    // WOUS button scroll
    if (wousBtn) {
        wousBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const testimonialsSection = document.getElementById('testimonials');

            if (testimonialsSection) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = testimonialsSection.offsetTop - navbarHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    }
});
