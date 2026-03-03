<?php
/**
 * Admin Authentication Check
 * Verifies admin session and handles timeout
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout duration (30 minutes)
$sessionTimeout = 1800;

// Check if admin is logged in
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to admin login
    header('Location: ../login.php');
    exit();
}

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    
    if ($elapsed > $sessionTimeout) {
        // Session expired
        session_unset();
        session_destroy();
        header('Location: ../login.php?msg=timeout');
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
