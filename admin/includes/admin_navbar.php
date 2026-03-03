<?php
// Ensure session is started and admin is authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, #1A237E, #1B5E20); box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-wallet me-2"></i>
            <span class="fw-bold">Wallet Tally Admin</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['index.php', 'dashboard.php']) ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users me-1"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'feedback_admin.php' ? 'active' : ''; ?>" href="feedback_admin.php">
                        <i class="fas fa-comments me-1"></i> Feedback
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'email_history.php' ? 'active' : ''; ?>" href="email_history.php">
                        <i class="fas fa-envelope-open-text me-1"></i> Email History
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="toolsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-tools me-1"></i> Tools
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header">System Checks</h6></li>
                        <li><a class="dropdown-item" href="check_database.php">
                            <i class="fas fa-database me-2"></i>Database Connection
                        </a></li>
                        <li><a class="dropdown-item" href="check_email.php">
                            <i class="fas fa-envelope me-2"></i>Email Connection
                        </a></li>
                        <li><a class="dropdown-item" href="system_info.php">
                            <i class="fas fa-server me-2"></i>System Information
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Maintenance</h6></li>
                        <li><a class="dropdown-item" href="analyze_database.php">
                            <i class="fas fa-chart-bar me-2"></i>Database Analysis
                        </a></li>
                        <li><a class="dropdown-item" href="cleanup_database.php">
                            <i class="fas fa-broom me-2"></i>Database Cleanup
                        </a></li>
                    </ul>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar-dark .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.9);
        padding: 0.5rem 1rem;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .navbar-dark .navbar-nav .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .navbar-dark .navbar-nav .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        font-weight: 500;
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .dropdown-item {
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .dropdown-item:hover {
        background: #E8EAF6;
        color: #1A237E;
    }
    
    .dropdown-item.text-danger:hover {
        background: #ffebee;
        color: #c62828;
    }
    
    @media (max-width: 991.98px) {
        .navbar-nav {
            padding: 1rem 0;
        }
        
        .navbar-nav .nav-link {
            margin: 0.25rem 0;
        }
    }
</style>
