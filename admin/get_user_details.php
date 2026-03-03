<?php
require_once('includes/auth_check.php');
require_once('../config/db.php');

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit();
}

$user_id = (int)$_GET['id'];

// Get user details
$stmt = $conn->prepare("SELECT id, username, email, country, currency, dob, created_at, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

// Process profile picture path - Correct path resolution for admin panel
$profile_picture_url = '../assets/images/default-avatar.svg'; // Default fallback

if (!empty($user['profile_picture'])) {
    // Profile pictures are stored as just filename, construct the full path
    $profile_filename = $user['profile_picture'];
    $profile_full_path = __DIR__ . '/../uploads/profile_pictures/' . $profile_filename;
    
    // Check if the file exists
    if (file_exists($profile_full_path)) {
        // Return the path relative to admin folder for web access
        $profile_picture_url = '../uploads/profile_pictures/' . $profile_filename;
    }
}

$user['profile_picture_url'] = $profile_picture_url;

// Get user's transaction count
$trans_stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
$trans_stmt->bind_param("i", $user_id);
$trans_stmt->execute();
$trans_result = $trans_stmt->get_result();
$transaction_count = $trans_result->fetch_assoc()['count'];

// Get user's category count
$cat_stmt = $conn->prepare("SELECT COUNT(*) as count FROM categories WHERE user_id = ?");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$category_count = $cat_result->fetch_assoc()['count'];

// Get user's feedback
$feedback_stmt = $conn->prepare("SELECT rating, feedback, created_at FROM user_feedback WHERE user_id = ?");
$feedback_stmt->bind_param("i", $user_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$feedback = $feedback_result->fetch_assoc();

$user['transaction_count'] = $transaction_count;
$user['category_count'] = $category_count;
$user['feedback'] = $feedback;

echo json_encode(['success' => true, 'user' => $user]);

$conn->close();
?>
