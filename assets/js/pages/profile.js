$(document).ready(function() {
            // Handle profile form submission
            $('#profileForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'profile.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
            
            // Handle password form submission
            $('#passwordForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'profile.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
            
            // Don't use AJAX for file uploads - let the form submit normally
            $('#pictureForm').on('submit', function() {
                // Show loading indicator
                $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Uploading...').prop('disabled', true);
            });
            
            // Close navbar when clicking outside
            document.addEventListener('click', function(event) {
                const navbar = document.querySelector('.navbar-collapse');
                const navbarToggler = document.querySelector('.navbar-toggler');
                
                if (!navbar.contains(event.target) && !navbarToggler.contains(event.target)) {
                    navbar.classList.remove('show');
                }
            });

            // Close navbar when clicking on a link or button
            const navbarLinks = document.querySelectorAll('.navbar a, .navbar button');
            navbarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const navbar = document.querySelector('.navbar-collapse');
                    navbar.classList.remove('show');
                });
            });
            
            // Toggle password visibility
            $('.toggle-password').on('click', function() {
                const passwordInput = $(this).closest('.input-group').find('input');
                const icon = $(this).find('i');
                
                // Toggle password visibility
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });

