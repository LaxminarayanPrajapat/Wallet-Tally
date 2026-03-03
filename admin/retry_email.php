<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email_id = isset($input['email_id']) ? (int)$input['email_id'] : 0;

if ($email_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid email ID']);
    exit;
}

// Get email details
$sql = "SELECT * FROM email_logs WHERE id = ? AND status = 'FAILED'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $email_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Failed email log not found']);
    exit;
}

$email = $result->fetch_assoc();

try {
    $retry_success = false;
    $error_message = '';
    
    // Determine which email service to use based on email type
    switch ($email['email_type']) {
        case 'warning':
            require_once('includes/warning_email_service.php');
            $warningService = new WarningEmailService($conn);
            
            // Get user data
            $user_data = [
                'id' => $email['user_id'],
                'username' => $email['recipient_name'],
                'email' => $email['recipient_email']
            ];
            
            // Extract category and description from subject (basic parsing)
            $category = 'System Warning';
            $description = 'This is a retry of a previously failed warning email.';
            
            $retry_success = $warningService->sendWarningEmail(
                $user_data,
                $category,
                $description,
                $email['admin_name']
            );
            break;
            
        case 'appreciation':
        case 'feedback_deletion':
        case 'user_deletion':
            require_once('includes/feedback_email_service.php');
            $feedbackService = new FeedbackEmailService($conn);
            
            if ($email['email_type'] === 'feedback_deletion') {
                $retry_success = $feedbackService->sendDeletionEmail(
                    $email['recipient_email'],
                    $email['recipient_name'],
                    'Previous feedback content',
                    5,
                    'Retry of failed email',
                    $email['user_id']  // Pass the user_id from the email log
                );
            } elseif ($email['email_type'] === 'user_deletion') {
                $retry_success = $feedbackService->sendUserDeletionEmail(
                    $email['recipient_email'],
                    $email['recipient_name'],
                    'Retry of failed email',
                    $email['user_id']  // Pass the user_id from the email log
                );
            } else {
                // For appreciation emails, we need feedback ID which we don't have
                $error_message = 'Appreciation emails cannot be retried without original feedback data';
            }
            break;
            
        default:
            $error_message = 'Unknown email type: ' . $email['email_type'];
    }
    
    if (!empty($error_message)) {
        echo json_encode(['success' => false, 'error' => $error_message]);
        exit;
    }
    
    // Update the email log
    if ($retry_success) {
        $update_sql = "UPDATE email_logs SET status = 'SUCCESS', error_message = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $email_id);
        $update_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Email retry successful']);
    } else {
        $update_sql = "UPDATE email_logs SET error_message = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $retry_error = 'Retry failed - email service returned false';
        $update_stmt->bind_param("si", $retry_error, $email_id);
        $update_stmt->execute();
        
        echo json_encode(['success' => false, 'error' => 'Email retry failed']);
    }
    
} catch (Exception $e) {
    // Update with error message
    $update_sql = "UPDATE email_logs SET error_message = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $retry_error = 'Retry failed: ' . $e->getMessage();
    $update_stmt->bind_param("si", $retry_error, $email_id);
    $update_stmt->execute();
    
    echo json_encode(['success' => false, 'error' => 'Email retry failed: ' . $e->getMessage()]);
}
?>