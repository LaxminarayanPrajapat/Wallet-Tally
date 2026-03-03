<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');
require_once('includes/warning_email_service.php');

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $user_id = (int)$input['user_id'];
    $category = trim($input['category']);
    $description = trim($input['description']);

    if ($user_id <= 0 || empty($category) || empty($description)) {
        throw new Exception('Invalid input data');
    }

    // Get user details
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    
    // Get admin name
    $admin_name = 'System Administrator';
    if (isset($_SESSION['user_id'])) {
        $admin_stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
        if ($admin_stmt) {
            $admin_stmt->bind_param("i", $_SESSION['user_id']);
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            if ($admin_result->num_rows > 0) {
                $admin_name = $admin_result->fetch_assoc()['username'];
            }
        }
    }
    
    // Create warnings table if needed
    $conn->query("CREATE TABLE IF NOT EXISTS user_warnings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        admin_name VARCHAR(100) DEFAULT 'System Administrator',
        category VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Log warning
    $log_stmt = $conn->prepare("INSERT INTO user_warnings (user_id, admin_name, category, description) VALUES (?, ?, ?, ?)");
    if (!$log_stmt) {
        throw new Exception('Failed to prepare warning log');
    }
    
    $log_stmt->bind_param("isss", $user_id, $admin_name, $category, $description);
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log warning');
    }
    
    // Send email using the SAME service as the working test email
    $emailService = new WarningEmailService($conn);
    $email_sent = $emailService->sendWarningEmail($user, $category, $description, $admin_name);
    
    if ($email_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Warning sent successfully to ' . $user['username'] . ' (' . $user['email'] . ')'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Warning logged but failed to send email to ' . $user['email']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>