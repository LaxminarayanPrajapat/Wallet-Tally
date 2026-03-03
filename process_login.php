<?php
session_start();
require_once('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // First check if it's an admin login
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Admin login successful
            $_SESSION['admin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regeneration'] = time();
            
            // Handle remember me for admin
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('admin_remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days
                setcookie('admin_username', $username, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }
            
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid username or password!";
        }
    } else {
        // Check regular user login
        $stmt = $conn->prepare("SELECT id, username, password, currency, currency_symbol FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['currency'] = $user['currency'];
                $_SESSION['currency_symbol'] = $user['currency_symbol'];
                $_SESSION['login_success'] = true;
                
                // Handle remember me for regular users
                if ($remember) {
                    // Create a secure token
                    $token = bin2hex(random_bytes(32));
                    
                    // Store token in cookie (30 days)
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    
                    // Store token hash in database for security
                    $token_hash = hash('sha256', $token);
                    $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
                    
                    // Update or insert remember token
                    $update_token = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
                    $update_token->bind_param("ssi", $token_hash, $expiry, $user['id']);
                    $update_token->execute();
                    $update_token->close();
                } else {
                    // Clear remember me cookies if not checked
                    setcookie('remember_token', '', time() - 3600, '/');
                    setcookie('user_id', '', time() - 3600, '/');
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid username or password!";
            }
        } else {
            $_SESSION['error'] = "Invalid username or password!";
        }
    }
    
    header("Location: login.php");
    exit();
} else {
    // If accessed directly without POST
    header('Location: login.php');
    exit();
}

$conn->close();
?> 