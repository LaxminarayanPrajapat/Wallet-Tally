<?php
session_start();

// Store the timeout message if it exists
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Clear remember me cookies
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');
setcookie('admin_remember_token', '', time() - 3600, '/');
setcookie('admin_username', '', time() - 3600, '/');

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page with appropriate message
if ($msg === 'timeout') {
    header('Location: login.php?msg=timeout');
} else {
    header('Location: login.php?msg=logout');
}
exit();
?> 