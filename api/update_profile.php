<?php
session_start();
require_once('../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $profile_picture = null;
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = '../uploads/profile_pictures/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
                
                // Delete old profile picture if it's not the default
                if ($_SESSION['profile_picture'] !== 'default-profile.png') {
                    $old_file = '../uploads/profile_pictures/' . $_SESSION['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            }
        }
    }
    
    // Update database
    if ($profile_picture) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $profile_picture, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $user_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        if ($profile_picture) {
            $_SESSION['profile_picture'] = $profile_picture;
        }
        
        $response = [
            'success' => true,
            'profile_picture' => $profile_picture,
            'message' => 'Profile updated successfully'
        ];
    } else {
        throw new Exception("Failed to update profile");
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response); 