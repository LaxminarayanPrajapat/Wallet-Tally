<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1A237E, #1B5E20);">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-wallet me-2"></i>Wallet Tally Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="feedback_admin.php">
                        <i class="fas fa-comments me-2"></i>Feedback
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="toolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-tools me-2"></i>Tools
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="toolsDropdown">
                        <li><a class="dropdown-item" href="analyze_database.php">
                            <i class="fas fa-database me-2"></i>Database Analysis
                        </a></li>
                        <li><a class="dropdown-item" href="cleanup_database.php">
                            <i class="fas fa-broom me-2"></i>Database Cleanup
                        </a></li>
                        <li><a class="dropdown-item" href="remove_reviews_table.php">
                            <i class="fas fa-trash-alt me-2"></i>Remove Reviews Table
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="cleanup_files.php">
                            <i class="fas fa-file-code me-2"></i>File Cleanup
                        </a></li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Logout Button (Always Visible) -->
<div class="mobile-logout-btn d-lg-none">
    <a href="../logout.php" class="btn btn-danger w-100">
        <i class="fas fa-sign-out-alt me-2"></i>Logout
    </a>
</div>

<style>
/* Mobile Logout Button Styles */
.mobile-logout-btn {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1030;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
}

.mobile-logout-btn .btn {
    border-radius: 8px;
    padding: 10px;
    font-weight: 600;
}

/* Add padding to body to prevent content from being hidden behind the fixed logout button */
@media (max-width: 991.98px) {
    body {
        padding-bottom: 60px;
    }
}

@media (max-width: 991.98px) {
    .navbar-collapse {
        background: linear-gradient(135deg, #1A237E, #1B5E20);
        padding: 1rem;
        border-radius: 0 0 15px 15px;
        margin-top: 0.5rem;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .navbar-nav {
        margin: 0.5rem 0;
        width: 100%;
    }
    
    .nav-item {
        margin: 0.5rem 0;
        width: 100%;
    }
    
    .nav-link {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: background-color 0.3s;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 100%;
        display: block;
    }
    
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .d-flex {
        display: none !important; /* Hide the original logout button in collapsed menu */
    }
    
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .navbar-brand {
        max-width: 70%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

@media (max-width: 575.98px) {
    .navbar-collapse {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        width: 100%;
    }
    
    .container-fluid {
        padding-left: 5px;
        padding-right: 5px;
    }
}
</style> 