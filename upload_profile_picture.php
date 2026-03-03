<?php
session_start();
require_once('config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Basic validation
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error_message'] = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
    } elseif ($file['size'] > $max_size) {
        $_SESSION['error_message'] = "File is too large. Maximum size is 2MB.";
    } else {
        // Additional validation with GD (if available)
        if (extension_loaded('gd')) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $_SESSION['error_message'] = "Invalid image file. Please upload a valid image.";
                header('Location: profile.php');
                exit();
            }
            
            // Check image dimensions (optional - prevent extremely large images)
            $max_width = 2000;
            $max_height = 2000;
            if ($image_info[0] > $max_width || $image_info[1] > $max_height) {
                $_SESSION['error_message'] = "Image dimensions too large. Maximum size is {$max_width}x{$max_height} pixels.";
                header('Location: profile.php');
                exit();
            }
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/profile_pictures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // If GD is available, create a resized version for better performance
            if (extension_loaded('gd')) {
                $resized_file = resizeImage($target_file, 300, 300);
                if ($resized_file) {
                    // Remove original large file and use resized version
                    unlink($target_file);
                    rename($resized_file, $target_file);
                }
            }
            
            // Update database
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Profile picture updated successfully";
                $_SESSION['profile_picture'] = $new_filename;
            } else {
                $_SESSION['error_message'] = "Failed to update profile picture in database";
                // Clean up uploaded file if database update fails
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload file";
        }
    }
} else {
    $_SESSION['error_message'] = "No file was uploaded or there was an error with the upload.";
}

/**
 * Resize image using GD extension
 * @param string $source_file Path to source image
 * @param int $max_width Maximum width
 * @param int $max_height Maximum height
 * @return string|false Path to resized image or false on failure
 */
function resizeImage($source_file, $max_width, $max_height) {
    if (!extension_loaded('gd')) {
        return false;
    }
    
    $image_info = getimagesize($source_file);
    if ($image_info === false) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    if ($ratio >= 1) {
        return false; // Image is already smaller than target size
    }
    
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create source image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_file);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_file);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_file);
            break;
        default:
            return false;
    }
    
    if (!$source_image) {
        return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    $resized_file = $source_file . '_resized';
    $success = false;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($new_image, $resized_file, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($new_image, $resized_file, 8);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($new_image, $resized_file);
            break;
    }
    
    // Clean up memory
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $success ? $resized_file : false;
}

header('Location: profile.php');
exit();
?> 