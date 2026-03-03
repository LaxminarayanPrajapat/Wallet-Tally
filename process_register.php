<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'registration_errors.log');

session_start();

// Log PHP version and extensions
error_log("PHP Version: " . phpversion());
error_log("Loaded Extensions: " . implode(", ", get_loaded_extensions()));

try {
    require_once('config/db.php');
    require_once('includes/otp_service.php');
    require_once('includes/email_service.php');
    error_log("Database connection file loaded successfully");
} catch (Exception $e) {
    error_log("Error loading database file: " . $e->getMessage());
    die("Database configuration error. Please check the error logs.");
}

// Log the incoming request
error_log("Registration attempt received - POST data: " . print_r($_POST, true));
error_log("Files data: " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify database connection
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        error_log("Database connection verified");

        // Get form data
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $country = $_POST['country'] ?? '';
        $currency = $_POST['currency'] ?? '';

        // Log the processed form data
        error_log("Processed form data: " . print_r([
            'username' => $username,
            'email' => $email,
            'country' => $country,
            'currency' => $currency
        ], true));

        // Validate required fields
        $missing_fields = [];
        if (empty($username)) $missing_fields[] = 'username';
        if (empty($email)) $missing_fields[] = 'email';
        if (empty($password)) $missing_fields[] = 'password';
        if (empty($country)) $missing_fields[] = 'country';
        if (empty($currency)) $missing_fields[] = 'currency';

        if (!empty($missing_fields)) {
            error_log("Missing fields: " . implode(', ', $missing_fields));
            header('Location: register.php?error=required_fields');
            exit();
        }
        error_log("All required fields present");

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format: " . $email);
            header('Location: register.php?error=invalid_email');
            exit();
        }
        error_log("Email format valid");



        // Validate password match
        if ($password !== $confirm_password) {
            error_log("Password mismatch");
            header('Location: register.php?error=password_mismatch');
            exit();
        }
        error_log("Passwords match");

        // Check if username is already taken
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for username check: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            error_log("Username already taken: " . $username);
            header('Location: register.php?error=username_taken');
            exit();
        }
        error_log("Username available");

        // Check if email is already registered in users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for email check: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            error_log("Email already registered: " . $email);
            header('Location: register.php?error=email_taken');
            exit();
        }
        error_log("Email available in users table");
        
        // Check if email is in pending_users table (from previous registration attempt)
        $stmt = $conn->prepare("SELECT id, expires_at FROM pending_users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for pending users check: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $pendingUser = $result->fetch_assoc();
            // Check if expired
            if (strtotime($pendingUser['expires_at']) < time()) {
                // Expired, delete it
                error_log("Found expired pending user for email: " . $email . ", deleting...");
                $conn->query("DELETE FROM pending_users WHERE id = " . $pendingUser['id']);
                $conn->query("DELETE FROM otp_verifications WHERE email = '$email'");
            } else {
                // Still active, user should complete verification
                error_log("Email has pending registration: " . $email);
                $_SESSION['pending_registration'] = [
                    'email' => $email,
                    'username' => $username
                ];
                header('Location: verify_otp.php?resend=1');
                exit();
            }
        }
        error_log("Email available in pending_users table");

        // Handle profile picture upload
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!in_array($file['type'], $allowed_types)) {
                error_log("Invalid file type: " . $file['type']);
                header('Location: register.php?error=invalid_file_type');
                exit();
            }
            
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $max_size) {
                error_log("File too large: " . $file['size']);
                header('Location: register.php?error=file_too_large');
                exit();
            }
            
            $upload_dir = 'uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
                error_log("Profile picture uploaded successfully: " . $new_filename);
            } else {
                error_log("Failed to move uploaded file");
                header('Location: register.php?error=upload_failed');
                exit();
            }
        } else {
            error_log("No profile picture uploaded, will be null");
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        error_log("Password hashed successfully");

        // Store user data in pending_users table
        error_log("Preparing to insert user into pending_users table");
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
        $stmt = $conn->prepare(
            "INSERT INTO pending_users (username, email, password, country, currency, profile_picture, expires_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception("Prepare failed for pending user insert: " . $conn->error);
        }
        
        $stmt->bind_param("sssssss", $username, $email, $hashed_password, $country, $currency, $profile_picture, $expiresAt);
        error_log("Parameters bound successfully");
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        error_log("User data stored in pending_users table");
        
        // Generate and send OTP
        $otpService = new OTPService($conn);
        $otpResult = $otpService->createOTP($email);
        
        if (!$otpResult['success']) {
            error_log("Failed to generate OTP: " . $otpResult['message']);
            header('Location: register.php?error=otp_generation_failed');
            exit();
        }
        
        error_log("OTP generated: " . $otpResult['otp']);
        
        // Send OTP email
        try {
            $emailService = new EmailService();
            $emailSent = $emailService->sendOTP($email, $username, $otpResult['otp']);
            
            if ($emailSent) {
                error_log("OTP email sent successfully to: " . $email);
            } else {
                error_log("Failed to send OTP email to: " . $email);
            }
        } catch (Exception $e) {
            error_log("Email service error: " . $e->getMessage());
        }
        
        // Store registration data in session for OTP verification page
        $_SESSION['pending_registration'] = [
            'email' => $email,
            'username' => $username
        ];
        
        // Redirect to OTP verification page
        header('Location: verify_otp.php');
        exit();
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $lastError = error_get_last();
        if ($lastError) {
            error_log("Last error: " . $lastError['message']);
        }
        header('Location: register.php?error=db_error');
        exit();
    }
} else {
    // If someone tries to access this file directly without POST data
    header('Location: register.php');
}
exit();
?> 