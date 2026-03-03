<?php
require_once('../config/db.php');

header('Content-Type: application/json');

// Get only approved 5-star feedback with user information for testimonials display
$sql = "SELECT 
            uf.id,
            uf.rating,
            uf.feedback,
            uf.created_at,
            u.username,
            u.profile_picture
        FROM user_feedback uf
        INNER JOIN users u ON uf.user_id = u.id
        WHERE uf.rating = 5 
        AND uf.feedback IS NOT NULL 
        AND uf.feedback != ''
        AND uf.display_approved = TRUE
        ORDER BY uf.created_at DESC";

$result = $conn->query($sql);

$testimonials = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Set profile picture path
        $profile_pic = 'default';
        
        if (!empty($row['profile_picture'])) {
            $pic_value = $row['profile_picture'];
            
            // The database stores just the filename (e.g., "profile_23_1762825572.jpg" or "67ee54283f1f3.jpg")
            // The actual file is in uploads/profile_pictures/
            $profile_path = __DIR__ . '/../uploads/profile_pictures/' . $pic_value;
            
            if (file_exists($profile_path)) {
                // Return the path relative to index.php
                $profile_pic = 'uploads/profile_pictures/' . $pic_value;
            }
        }
        
        $testimonials[] = array(
            'id' => $row['id'],
            'username' => $row['username'],
            'rating' => $row['rating'],
            'feedback' => $row['feedback'],
            'created_at' => $row['created_at'],
            'profile_picture' => $profile_pic
        );
    }
}

echo json_encode($testimonials);

$conn->close();
?>
