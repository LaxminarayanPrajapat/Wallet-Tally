// Dashboard specific JavaScript functionality

// Global variables
let pieChart = null;

// Transaction Modal Functions
function openTransactionModal(type) {
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    document.getElementById('transactionType').value = type;
    document.getElementById('transactionModalLabel').textContent = `Add ${type.charAt(0).toUpperCase() + type.slice(1)}`;
    document.getElementById('transactionForm').reset();

    // Load categories based on transaction type
    loadCategoriesForType(type);

    modal.show();
}

function loadCategoriesForType(type) {
    const container = document.getElementById('categoryButtonsContainer');
    const categories = type === 'income' ? window.incomeCategories : window.expenseCategories;

    // Clear existing buttons
    container.innerHTML = '';

    // Create buttons for each category
    if (categories.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0">No categories available. Type to create a new one.</p>';
    } else {
        categories.forEach(category => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-primary suggestion-btn';
            button.textContent = category;
            button.onclick = () => selectCategory(category);
            container.appendChild(button);
        });
    }
}

function toggleCategorySuggestions() {
    const suggestions = document.getElementById('categorySuggestions');
    const currentType = document.getElementById('transactionType').value;

    // Reload categories for current type before showing
    if (suggestions.style.display === 'none') {
        loadCategoriesForType(currentType);
    }

    suggestions.style.display = suggestions.style.display === 'none' ? 'block' : 'none';
}

function selectCategory(categoryName) {
    document.querySelector('input[name="category_name"]').value = categoryName;
    document.getElementById('categorySuggestions').style.display = 'none';
}

function editTransaction(id) {
    // Fetch transaction details
    fetch(`get_transaction.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate modal with transaction data
                document.getElementById('transactionType').value = data.transaction.type;
                document.querySelector('.modal-title').textContent = 'Edit Transaction';
                document.querySelector('input[name="category_name"]').value = data.transaction.category_name;
                document.querySelector('input[name="amount"]').value = data.transaction.amount;
                document.querySelector('textarea[name="description"]').value = data.transaction.description;

                // Load categories for the transaction type
                loadCategoriesForType(data.transaction.type);

                // Add transaction ID to form
                let form = document.getElementById('transactionForm');
                let transactionIdInput = document.createElement('input');
                transactionIdInput.type = 'hidden';
                transactionIdInput.name = 'transaction_id';
                transactionIdInput.value = id;
                form.appendChild(transactionIdInput);

                // Show modal
                new bootstrap.Modal(document.getElementById('transactionModal')).show();
            } else {
                throw new Error(data.message || 'Failed to load transaction details');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to load transaction details'
            });
        });
}

function deleteTransaction(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send delete request with POST data
            fetch('delete_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Transaction has been deleted.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Failed to delete transaction');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to delete transaction'
                    });
                });
        }
    });
}

// Function to show export modal
function showExportModal() {
    // Set max date to today (prevent future dates)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').setAttribute('max', today);
    document.getElementById('endDate').setAttribute('max', today);

    // Clear date fields
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';

    // Reset checkbox to checked
    document.getElementById('sendEmail').checked = true;
    updateExportButtonText();

    new bootstrap.Modal(document.getElementById('exportModal')).show();
}

// Update export button text based on checkbox
function updateExportButtonText() {
    const sendEmail = document.getElementById('sendEmail');
    const btnText = document.getElementById('exportBtnText');
    const btnIcon = document.querySelector('#exportSubmitBtn i');

    if (sendEmail && btnText && btnIcon) {
        if (sendEmail.checked) {
            btnIcon.className = 'fas fa-paper-plane me-2';
            btnText.textContent = 'Generate & Send Report';
        } else {
            btnIcon.className = 'fas fa-download me-2';
            btnText.textContent = 'Download Report';
        }
    }
}

// Function to generate colors for categories
function generateCategoryColors(count, baseColor) {
    const colors = [];
    const isIncome = baseColor === 'income';

    if (isIncome) {
        // Green shades for income
        const greenShades = [
            '#10B981', '#059669', '#047857', '#065F46', '#064E3B',
            '#34D399', '#6EE7B7', '#A7F3D0', '#D1FAE5'
        ];
        return greenShades.slice(0, count);
    } else {
        // Red shades for expenses
        const redShades = [
            '#EF4444', '#DC2626', '#B91C1C', '#991B1B', '#7F1D1D',
            '#F87171', '#FCA5A5', '#FECACA', '#FEE2E2'
        ];
        return redShades.slice(0, count);
    }
}

// Function to load chart data
function loadChartData() {
    fetch('get_chart_data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Prepare data for category-based pie chart
                const labels = [];
                const amounts = [];
                const colors = [];
                const categoryTypes = []; // Track if it's income or expense

                // Add income categories
                if (data.income_categories && data.income_categories.length > 0) {
                    const incomeColors = generateCategoryColors(data.income_categories.length, 'income');
                    data.income_categories.forEach((cat, index) => {
                        labels.push(`${cat.category} (Income)`);
                        amounts.push(parseFloat(cat.amount));
                        colors.push(incomeColors[index]);
                        categoryTypes.push('income');
                    });
                }

                // Add expense categories
                if (data.expense_categories && data.expense_categories.length > 0) {
                    const expenseColors = generateCategoryColors(data.expense_categories.length, 'expense');
                    data.expense_categories.forEach((cat, index) => {
                        labels.push(`${cat.category} (Expense)`);
                        amounts.push(parseFloat(cat.amount));
                        colors.push(expenseColors[index]);
                        categoryTypes.push('expense');
                    });
                }

                // Update pie chart with category data
                if (labels.length > 0) {
                    pieChart.data.labels = labels;
                    pieChart.data.datasets[0].data = amounts;
                    pieChart.data.datasets[0].backgroundColor = colors;
                    pieChart.data.datasets[0].categoryTypes = categoryTypes;
                } else {
                    // No data available
                    pieChart.data.labels = ['No Data'];
                    pieChart.data.datasets[0].data = [1];
                    pieChart.data.datasets[0].backgroundColor = ['#E5E7EB'];
                }

                pieChart.update('active'); // Use 'active' mode for smooth animation
            } else {
                console.error('Failed to load chart data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
        });
}

// Function to refresh chart data
function refreshChartData() {
    loadChartData();
}

function handleTransactionSubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const transactionType = formData.get('type');
    const amount = parseFloat(formData.get('amount'));
    const currentBalance = window.currentBalance || 0;

    // Validate expense against balance
    if (transactionType === 'expense' && amount > currentBalance) {
        Swal.fire({
            icon: 'error',
            title: 'Insufficient Balance',
            text: `You cannot record an expense of ${window.currencySymbol}${amount.toFixed(2)} as it exceeds your current balance of ${window.currencySymbol}${currentBalance.toFixed(2)}`,
            confirmButtonColor: '#EF4444'
        });
        return;
    }

    fetch('process_transaction.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('transactionModal'));
                modal.hide();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Transaction added successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Reload the page to update the dashboard
                    location.reload();
                });
            } else {
                throw new Error(data.message || 'Failed to add transaction');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to add transaction'
            });
        });

    return false;
}

// Rating functionality
function initializeRating() {
    // Log existing stored rating (for debugging)
    console.log('Stored rating for user:', document.querySelector('.stars')?.dataset.existingRating || 0);

    document.querySelectorAll('.star-rating').forEach(star => {
        // Add hover effect
        star.addEventListener('mouseover', function () {
            const rating = parseInt(this.dataset.rating, 10);
            document.querySelectorAll('.star-rating').forEach(s => {
                const sr = parseInt(s.dataset.rating, 10);
                if (sr <= rating) {
                    s.style.color = '#ffc107';
                    s.style.transform = 'scale(1.2)';
                } else {
                    s.style.color = '#ddd';
                    s.style.transform = 'scale(1)';
                }
            });
        });

        // Reset on mouseout
        star.addEventListener('mouseout', function () {
            const activeStars = document.querySelectorAll('.star-rating.active');
            const activeRating = activeStars.length > 0 ? parseInt(activeStars[activeStars.length - 1].dataset.rating, 10) : 0;
            document.querySelectorAll('.star-rating').forEach(s => {
                const sr = parseInt(s.dataset.rating, 10);
                if (sr <= activeRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
                s.style.transform = 'scale(1)';
            });
        });

        // Click to rate
        star.addEventListener('click', function () {
            const rating = parseInt(this.dataset.rating, 10);
            document.querySelectorAll('.star-rating').forEach(s => {
                s.classList.remove('active');
                const sr = parseInt(s.dataset.rating, 10);
                if (sr <= rating) {
                    s.classList.add('active');
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });

            // Show rating confirmation
            const ratingText = rating === 1 ? 'star' : 'stars';
            Swal.fire({
                icon: 'success',
                title: `You rated ${rating} ${ratingText}`,
                showConfirmButton: false,
                timer: 1500,
                position: 'top-end',
                toast: true
            });
        });
    });
}

function submitFeedback() {
    const activeStars = document.querySelectorAll('.star-rating.active');
    const rating = activeStars.length > 0 ? parseInt(activeStars[activeStars.length - 1].dataset.rating, 10) : 0;
    const feedback = document.getElementById('feedbackText').value;

    if (rating === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Please Rate',
            text: 'Please select a rating before submitting feedback'
        });
        return;
    }

    // Show loading state
    Swal.fire({
        title: 'Submitting Feedback',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send feedback to server
    fetch('submit_feedback.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `rating=${rating}&feedback=${encodeURIComponent(feedback)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thank You!',
                    text: 'Your feedback has been submitted successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Keep the selected stars and feedback as-is so review remains until user changes it
                    // Update stored rating attribute so future mouseouts/restores use this value
                    const starsContainer = document.querySelector('.stars');
                    if (starsContainer) {
                        starsContainer.dataset.existingRating = rating;
                    }

                    // Update submit button text to 'Update Feedback' to indicate the review is saved
                    const submitBtn = document.querySelector('.feedback-section button');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Update Feedback';
                    }

                    // Leave the textarea content intact so the user can edit it if desired
                });
            } else {
                throw new Error(data.message || 'Failed to submit feedback');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to submit feedback'
            });
        });
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Chart.js with enhanced design
    const pieCtx = document.getElementById('pieChart');
    if (pieCtx) {
        pieChart = new Chart(pieCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 12,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#ffffff',
                    categoryTypes: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '0%', // Makes it a complete pie chart (no hole)
                rotation: -90, // Start from top
                circumference: 360,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false // Hide the legend
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        boxWidth: 15,
                        boxHeight: 15,
                        usePointStyle: true,
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                const currencySymbol = window.currencySymbol || '$';
                                return `${label}: ${currencySymbol}${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: true
                }
            },
            plugins: []
        });

        // Load initial data
        loadChartData();
    }

    // Initialize rating functionality
    initializeRating();

    // Add event listener for start date to update end date minimum
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function () {
            // Set end date minimum to start date
            endDateInput.setAttribute('min', this.value);

            // If end date is before start date, clear it
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = '';
            }
        });
    }

    // Add event listener for checkbox change
    const sendEmailCheckbox = document.getElementById('sendEmail');
    if (sendEmailCheckbox) {
        sendEmailCheckbox.addEventListener('change', updateExportButtonText);
    }

    // Handle export form submission with AJAX
    const exportForm = document.getElementById('exportForm');
    if (exportForm) {
        exportForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Always prevent default form submission

            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            const sendEmail = document.getElementById('sendEmail').checked;

            if (startDate > endDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Start date cannot be after end date',
                    confirmButtonColor: '#EF4444'
                });
                return false;
            }

            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            if (modal) {
                modal.hide();
            }

            // Show loading indicator
            Swal.fire({
                title: sendEmail ? 'Generating & Sending PDF...' : 'Generating PDF...',
                html: sendEmail ? 'Please wait while we prepare and email your report<br><small>This may take a few moments</small>' : 'Please wait while we prepare your download',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Prepare form data
            const formData = new FormData(exportForm);

            // Send AJAX request
            fetch('export_pdf.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    const contentType = response.headers.get('content-type');

                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else if (contentType && contentType.includes('text/html')) {
                        // HTML response for print/download
                        return response.text().then(html => {
                            // Open in new window for printing
                            const printWindow = window.open('', '_blank');
                            printWindow.document.write(html);
                            printWindow.document.close();
                            setTimeout(() => printWindow.print(), 500);
                            return { success: true, download: true };
                        });
                    } else {
                        console.error('Unexpected Content-Type:', contentType);
                        throw new Error('Unable to generate report. Please try again.');
                    }
                })
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: data.download ? 'Download Started!' : 'Email Sent!',
                            text: data.download ? 'Your PDF report is downloading' : data.message || 'PDF report has been sent to your email',
                            timer: 3000,
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Export Failed',
                            text: data.message || 'Failed to generate PDF report',
                            showConfirmButton: true
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'An error occurred while generating the report',
                        showConfirmButton: true
                    });
                });
        });
    }

    // Close navbar when clicking outside
    document.addEventListener('click', function (event) {
        const navbar = document.querySelector('.navbar-collapse');
        const navbarToggler = document.querySelector('.navbar-toggler');

        if (navbar && navbarToggler && !navbar.contains(event.target) && !navbarToggler.contains(event.target)) {
            navbar.classList.remove('show');
        }
    });

    // Close navbar when clicking on a link or button
    const navbarLinks = document.querySelectorAll('.navbar a, .navbar button');
    navbarLinks.forEach(link => {
        link.addEventListener('click', function () {
            const navbar = document.querySelector('.navbar-collapse');
            if (navbar) {
                navbar.classList.remove('show');
            }
        });
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function (event) {
        const suggestions = document.getElementById('categorySuggestions');
        const categoryInput = document.querySelector('input[name="category_name"]');
        const suggestionsButton = document.querySelector('.btn-outline-secondary');

        if (suggestions && !suggestions.contains(event.target) &&
            !categoryInput?.contains(event.target) &&
            !suggestionsButton?.contains(event.target)) {
            suggestions.style.display = 'none';
        }
    });

    // Modal focus management
    const transactionModal = document.getElementById('transactionModal');

    if (transactionModal) {
        transactionModal.addEventListener('shown.bs.modal', function () {
            // Set focus to first input when modal opens
            const firstInput = this.querySelector('input:not([type="hidden"])');
            if (firstInput) {
                firstInput.focus();
            }
        });

        transactionModal.addEventListener('hide.bs.modal', function () {
            // Remove focus from any element inside modal before hiding
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });
    }
});
