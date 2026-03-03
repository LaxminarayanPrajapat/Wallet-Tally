<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any remember me cookies
setcookie('admin_remember_token', '', time() - 3600, '/');
setcookie('admin_username', '', time() - 3600, '/');

// Destroy the session
session_destroy();

// Redirect to common login page
header('Location: ../login.php?msg=logout');
exit();
?> 