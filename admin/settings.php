<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate current password if trying to change password
    if (!empty($new_password)) {
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if (!password_verify($current_password, $admin['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $admin_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully";
            header('Location: settings.php');
            exit();
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

// Get current admin data
$stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Get current settings
$settings = [];
$stmt = $conn->prepare("SELECT name, value FROM settings");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

// Set default values if settings don't exist
$default_settings = [
    'site_name' => 'Wallet Tally',
    'site_description' => 'Your Personal Cash Manager',
    'contact_email' => '',
    'maintenance_mode' => '0'
];

// Merge default settings with database settings
$settings = array_merge($default_settings, $settings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Wallet Tally</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #1A237E;
            --secondary-color: #1B5E20;
            --accent-color: #0D47A1;
            --dark-blue: #0D47A1;
            --light-blue: #E8EAF6;
            --success-green: #2E7D32;
            --background-light: #F5F7FA;
            --text-dark: #1A237E;
            --dark-green: #1B5E20;
            --navy-blue: #0D47A1;
            --gradient-start: #1A237E;
            --gradient-end: #1B5E20;
            --gradient-angle: 135deg;
        }

        body {
            padding-top: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--background-light);
            overflow-x: hidden;
        }

        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 1.5rem;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
            transform: translateX(0);
            border-radius: 15px;
        }

        .sidebar.collapsed {
            transform: translateX(-250px);
        }

        .sidebar-header {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header i {
            font-size: 2rem;
            color: white;
            margin-bottom: 10px;
        }

        .sidebar-header h5 {
            color: white;
            margin: 0;
            font-weight: 600;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            font-size: 0.9rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .sidebar .nav-link.text-danger {
            color: #ff6b6b;
        }

        .sidebar .nav-link.text-danger:hover {
            background: rgba(255,107,107,0.1);
            color: #ff6b6b;
        }

        /* Content styles */
        .content {
            margin-left: 250px;
            padding: 80px 40px 40px;
            min-height: 100vh;
            background: var(--background-light);
            max-width: 1600px;
            margin-right: auto;
            margin-left: auto;
            transition: margin-left 0.3s ease-in-out;
        }

        .content.expanded {
            margin-left: 0;
        }

        /* Navbar styles */
        .navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 999;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(26, 35, 126, 0.1);
            transition: left 0.3s ease-in-out;
        }

        .navbar.expanded {
            left: 0;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 200px;
        }

        .navbar-center {
            flex: 1;
            text-align: center;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 200px;
            justify-content: flex-end;
        }

        .navbar-title {
            font-weight: 600;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .user-dropdown:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .user-dropdown i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .user-dropdown span {
            font-weight: 500;
        }

        /* Toggle button styles */
        .toggle-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 1rem;
        }

        .toggle-btn:hover {
            color: var(--secondary-color);
        }

        /* Other styles */
        .gradient-text {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-gradient {
            background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
            border: none;
            color: white !important;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
            color: white !important;
        }

        .btn-gradient i {
            color: white !important;
        }

        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(26, 35, 126, 0.1);
        }

        .settings-card h5 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
        }

        .form-label {
            color: var(--text-dark);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .content {
                margin-left: 0;
                padding: 70px 15px 20px;
            }
            .navbar {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-shield"></i>
            <h5>
                <i class="fas fa-wallet me-2"></i>
                <span>Wallet Tally</span>
            </h5>
            <small>Admin Panel</small>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a class="nav-link" href="transactions.php">
                <i class="fas fa-exchange-alt"></i> Transactions
            </a>
            <a class="nav-link" href="feedback.php">
                <i class="fas fa-comments"></i> Feedback
            </a>
            <a class="nav-link active" href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main content -->
    <div class="content" id="content">
        <!-- Top Navbar -->
        <nav class="navbar" id="navbar">
            <div class="navbar-left">
                <a href="index.php" class="btn btn-gradient">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </div>
            <div class="navbar-center">
                <h5 class="mb-0 gradient-text navbar-title">Settings</h5>
            </div>
            <div class="navbar-right">
                <div class="dropdown">
                    <a class="user-dropdown dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Admin Settings</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Profile Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
                    </div>

                    <hr>
                    <h5>Change Password</h5>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>

                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">System Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="update_settings.php">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const navbar = document.getElementById('navbar');
        const toggleBtn = document.getElementById('toggleBtn');
        
        // Toggle sidebar function
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            navbar.classList.toggle('expanded');
        }
        
        // Add event listener to toggle button
        toggleBtn.addEventListener('click', toggleSidebar);
        
        // Handle mobile view
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
            navbar.classList.add('expanded');
        }
    });
    </script>
</body>
</html> 