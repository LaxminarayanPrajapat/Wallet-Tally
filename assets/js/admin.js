// Admin panel specific JavaScript functionality

document.addEventListener('DOMContentLoaded', function () {
    console.log('Admin panel JavaScript loaded');

    // Initialize charts if on dashboard page
    if (document.getElementById('usersByCountryChart')) {
        initializeCharts();
    }
});

// Chart color scheme matching the admin theme
const chartColors = {
    primary: '#1A237E',
    secondary: '#1B5E20',
    success: '#2E7D32',
    info: '#0277BD',
    warning: '#F57C00',
    danger: '#C62828',
    gradient: ['#1A237E', '#283593', '#3949AB', '#5C6BC0', '#7986CB']
};

// Initialize all dashboard charts
async function initializeCharts() {
    try {
        await Promise.all([
            createUsersByCountryChart(),
            createFeedbackDistributionChart(),
            loadSystemInfo()
        ]);
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

// Users by Country Pie Chart
async function createUsersByCountryChart() {
    try {
        console.log('Fetching users by country data...');
        const response = await fetch('api/get_chart_data.php?type=users_by_country');

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Users by country data:', result);

        if (!result.success) {
            console.error('API returned error:', result.message);
            document.getElementById('usersByCountryChart').parentElement.innerHTML =
                '<p class="text-danger text-center py-5">Error: ' + (result.message || 'Failed to load data') + '</p>';
            return;
        }

        if (!result.data || result.data.length === 0) {
            console.warn('No users by country data available');
            document.getElementById('usersByCountryChart').parentElement.innerHTML =
                '<p class="text-muted text-center py-5"><i class="fas fa-globe fa-3x mb-3 d-block"></i>No user data available</p>';
            return;
        }

        // Country code to name mapping
        const countryNames = {
            'US': 'United States',
            'IN': 'India',
            'GB': 'United Kingdom',
            'CA': 'Canada',
            'AU': 'Australia',
            'DE': 'Germany',
            'FR': 'France',
            'JP': 'Japan',
            'CN': 'China',
            'BR': 'Brazil',
            'MX': 'Mexico',
            'ES': 'Spain',
            'IT': 'Italy',
            'NL': 'Netherlands',
            'SE': 'Sweden',
            'NO': 'Norway',
            'DK': 'Denmark',
            'FI': 'Finland',
            'PL': 'Poland',
            'RU': 'Russia'
        };

        const labels = result.data.map(item => countryNames[item.country] || item.country);
        const data = result.data.map(item => parseInt(item.count));

        const ctx = document.getElementById('usersByCountryChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: chartColors.gradient,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' users (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating users by country chart:', error);
        document.getElementById('usersByCountryChart').parentElement.innerHTML =
            '<p class="text-danger text-center py-5"><i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>Error loading chart: ' + error.message + '</p>';
    }
}

// Transaction Volume Bar Chart
async function createTransactionVolumeChart() {
    try {
        const response = await fetch('api/get_chart_data.php?type=transaction_volume');
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            document.getElementById('transactionVolumeChart').parentElement.innerHTML =
                '<p class="text-muted text-center py-5">No transaction data available</p>';
            return;
        }

        // Process data to separate income and expense
        const monthsMap = {};
        result.data.forEach(item => {
            const date = new Date(item.month + '-01');
            const monthLabel = date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });

            if (!monthsMap[monthLabel]) {
                monthsMap[monthLabel] = { income: 0, expense: 0 };
            }
            monthsMap[monthLabel][item.type] = parseFloat(item.total);
        });

        const labels = Object.keys(monthsMap);
        const incomeData = labels.map(label => monthsMap[label].income);
        const expenseData = labels.map(label => monthsMap[label].expense);

        const ctx = document.getElementById('transactionVolumeChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        backgroundColor: chartColors.success,
                        borderRadius: 6
                    },
                    {
                        label: 'Expense',
                        data: expenseData,
                        backgroundColor: chartColors.danger,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating transaction volume chart:', error);
        document.getElementById('transactionVolumeChart').parentElement.innerHTML =
            '<p class="text-danger text-center py-5">Error loading chart data</p>';
    }
}

// Feedback Distribution Pie Chart
async function createFeedbackDistributionChart() {
    try {
        const response = await fetch('api/get_chart_data.php?type=feedback_distribution');
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            document.getElementById('feedbackDistributionChart').parentElement.innerHTML =
                '<p class="text-muted text-center py-5">No feedback data available</p>';
            return;
        }

        const labels = result.data.map(item => item.rating + ' Stars');
        const data = result.data.map(item => parseInt(item.count));

        const ctx = document.getElementById('feedbackDistributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        chartColors.success,
                        chartColors.info,
                        chartColors.warning,
                        chartColors.danger,
                        '#757575'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating feedback distribution chart:', error);
        document.getElementById('feedbackDistributionChart').parentElement.innerHTML =
            '<p class="text-danger text-center py-5">Error loading chart data</p>';
    }
}

// Category Breakdown Doughnut Chart
async function createCategoryBreakdownChart() {
    try {
        const response = await fetch('api/get_chart_data.php?type=category_breakdown');
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            document.getElementById('categoryBreakdownChart').parentElement.innerHTML =
                '<p class="text-muted text-center py-5">No category data available</p>';
            return;
        }

        const labels = result.data.map(item => item.category);
        const data = result.data.map(item => parseFloat(item.total));

        const ctx = document.getElementById('categoryBreakdownChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: chartColors.gradient,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 10,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': $' + context.parsed.toFixed(2) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating category breakdown chart:', error);
        document.getElementById('categoryBreakdownChart').parentElement.innerHTML =
            '<p class="text-danger text-center py-5">Error loading chart data</p>';
    }
}

// Load System Information
async function loadSystemInfo() {
    try {
        const response = await fetch('api/get_chart_data.php?type=system_info');
        const result = await response.json();

        if (result.success && result.data) {
            document.getElementById('dbStatus').textContent = result.data.db_status;
            document.getElementById('phpVersion').textContent = result.data.php_version;
            document.getElementById('mysqlVersion').textContent = result.data.mysql_version;
            document.getElementById('dbSize').textContent = result.data.db_size;

            // Update status indicator
            const statusBadge = document.getElementById('dbStatusBadge');
            if (result.data.db_status === 'Connected') {
                statusBadge.className = 'badge bg-success';
                statusBadge.textContent = 'Online';
            } else {
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'Offline';
            }
        }
    } catch (error) {
        console.error('Error loading system info:', error);
    }
}
