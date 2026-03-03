<?php
/**
 * Session Timeout Handler
 * Handles session timeout for user authentication
 */

// Session timeout duration (30 minutes)
$sessionTimeout = 1800;

// Check if user is logged in
// Note: Individual pages should handle their own login checks
// This file only handles session timeout, not initial authentication
if (!isset($_SESSION['user_id'])) {
    // Don't redirect here - let the calling page handle it
    return;
}

// Check if last activity is set
if (isset($_SESSION['last_activity'])) {
    // Calculate time elapsed since last activity
    $elapsed = time() - $_SESSION['last_activity'];
    
    // If session has expired
    if ($elapsed > $sessionTimeout) {
        // Clear session
        session_unset();
        session_destroy();
        
        // Redirect to login with timeout message
        header('Location: login.php?msg=timeout');
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
