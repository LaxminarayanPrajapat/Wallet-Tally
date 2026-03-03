<?php
session_start();
require_once('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name']);
    $site_description = trim($_POST['site_description']);
    $contact_email = trim($_POST['contact_email']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    
    $errors = [];
    
    // Validate inputs
    if (empty($site_name)) {
        $errors[] = "Site name is required";
    }
    
    if (empty($site_description)) {
        $errors[] = "Site description is required";
    }
    
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($errors)) {
        // Update settings
        $settings = [
            'site_name' => $site_name,
            'site_description' => $site_description,
            'contact_email' => $contact_email,
            'maintenance_mode' => $maintenance_mode
        ];
        
        $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        
        foreach ($settings as $name => $value) {
            $stmt->bind_param("sss", $name, $value, $value);
            if (!$stmt->execute()) {
                $errors[] = "Failed to update setting: " . $name;
            }
        }
        
        if (empty($errors)) {
            $_SESSION['success_message'] = "Settings updated successfully";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
}

header('Location: settings.php');
exit();
?> 