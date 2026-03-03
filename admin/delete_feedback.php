<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$feedback_id = isset($input['feedback_id']) ? (int)$input['feedback_id'] : 0;
$deletion_reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($feedback_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
    exit;
}

if (empty($deletion_reason)) {
    echo json_encode(['success' => false, 'error' => 'Deletion reason is required']);
    exit;
}

// Get feedback and user details before deletion
$get_sql = "SELECT f.*, u.username, u.email 
            FROM user_feedback f 
            JOIN users u ON f.user_id = u.id 
            WHERE f.id = ?";
$get_stmt = $conn->prepare($get_sql);

if (!$get_stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$get_stmt->bind_param("i", $feedback_id);
$get_stmt->execute();
$result = $get_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Feedback not found']);
    exit;
}

$feedback_data = $result->fetch_assoc();
$user_name = $feedback_data['username']; // Just use username since first_name doesn't exist

// Delete the feedback
$delete_sql = "DELETE FROM user_feedback WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);

if (!$delete_stmt) {
    echo json_encode(['success' => false, 'error' => 'Delete prepare failed: ' . $conn->error]);
    exit;
}

$delete_stmt->bind_param("i", $feedback_id);

if ($delete_stmt->execute()) {
    // Try to send deletion notification email AND warning email
    $emailSent = false;
    $warningSent = false;
    
    try {
        // Send feedback deletion email
        require_once('includes/feedback_email_service.php');
        $emailService = new FeedbackEmailService($conn);
        $emailSent = $emailService->sendDeletionEmail(
            $feedback_data['email'],
            $user_name,
            $feedback_data['feedback'],
            $feedback_data['rating'],
            $deletion_reason,
            $feedback_data['user_id']  // Pass the user_id
        );
        
        // Also send warning email with the same reason
        require_once('includes/warning_email_service.php');
        
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
        
        // Log warning in database
        $log_stmt = $conn->prepare("INSERT INTO user_warnings (user_id, admin_name, category, description) VALUES (?, ?, ?, ?)");
        if ($log_stmt) {
            $warning_category = 'False Feedback';
            $warning_description = 'Feedback deleted by admin. Reason: ' . $deletion_reason;
            $log_stmt->bind_param("isss", $feedback_data['user_id'], $admin_name, $warning_category, $warning_description);
            $log_stmt->execute();
        }
        
        // Send warning email
        $warningEmailService = new WarningEmailService($conn);
        $user_data = [
            'id' => $feedback_data['user_id'],
            'username' => $user_name,
            'email' => $feedback_data['email']
        ];
        
        $warningSent = $warningEmailService->sendWarningEmail(
            $user_data,
            'False Feedback',
            'Your feedback has been deleted by our administrative team. Reason: ' . $deletion_reason . '. This serves as an official warning regarding inappropriate feedback submission.',
            $admin_name
        );
        
    } catch (Exception $e) {
        // Log the error but don't fail the deletion
        error_log("Email sending failed: " . $e->getMessage());
        $emailSent = false;
        $warningSent = false;
    }
    
    $message = 'Feedback deleted successfully';
    if ($emailSent && $warningSent) {
        $message .= '. Both deletion notification and warning email sent to user.';
    } elseif ($emailSent) {
        $message .= '. Deletion notification sent, but warning email failed.';
    } elseif ($warningSent) {
        $message .= '. Warning email sent, but deletion notification failed.';
    } else {
        $message .= '. Emails could not be sent.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'email_sent' => $emailSent,
        'warning_sent' => $warningSent
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete feedback: ' . $conn->error]);
}

$delete_stmt->close();
$get_stmt->close();
$conn->close();
?> 