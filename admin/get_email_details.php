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
$sql = "SELECT * FROM email_logs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $email_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Email log not found']);
    exit;
}

$email = $result->fetch_assoc();

// Generate HTML content
$type_labels = [
    'appreciation' => '<span class="badge bg-success"><i class="fas fa-star me-1"></i>Appreciation Email</span>',
    'warning' => '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Warning Email</span>',
    'feedback_deletion' => '<span class="badge bg-info"><i class="fas fa-comment-slash me-1"></i>Feedback Deletion</span>',
    'user_deletion' => '<span class="badge bg-danger"><i class="fas fa-user-times me-1"></i>User Deletion</span>'
];

$status_labels = [
    'SUCCESS' => '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Success</span>',
    'FAILED' => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>',
    'PENDING' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>'
];

$html = '
<div class="row g-3">
    <div class="col-md-6">
        <div class="border rounded p-3">
            <h6 class="gradient-text mb-3"><i class="fas fa-info-circle me-2"></i>Email Information</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td><strong>ID:</strong></td>
                    <td>' . $email['id'] . '</td>
                </tr>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>' . ($type_labels[$email['email_type']] ?? 'Unknown') . '</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>' . ($status_labels[$email['status']] ?? 'Unknown') . '</td>
                </tr>
                <tr>
                    <td><strong>Admin:</strong></td>
                    <td>' . htmlspecialchars($email['admin_name']) . '</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>' . date('M d, Y H:i:s', strtotime($email['created_at'])) . '</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="border rounded p-3">
            <h6 class="gradient-text mb-3"><i class="fas fa-user me-2"></i>Recipient Information</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>' . htmlspecialchars($email['recipient_name'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>' . htmlspecialchars($email['recipient_email']) . '</td>
                </tr>
                <tr>
                    <td><strong>User ID:</strong></td>
                    <td>' . ($email['user_id'] ? $email['user_id'] : 'N/A') . '</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-12">
        <div class="border rounded p-3">
            <h6 class="gradient-text mb-3"><i class="fas fa-envelope me-2"></i>Email Subject</h6>
            <p class="mb-0">' . htmlspecialchars($email['subject']) . '</p>
        </div>
    </div>';

if ($email['status'] === 'FAILED' && !empty($email['error_message'])) {
    $html .= '
    <div class="col-12">
        <div class="border rounded p-3 bg-light-danger">
            <h6 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Error Details</h6>
            <div class="alert alert-danger mb-0">
                <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;">' . htmlspecialchars($email['error_message']) . '</pre>
            </div>
        </div>
    </div>';
}

$html .= '</div>';

echo json_encode(['success' => true, 'html' => $html]);
?>