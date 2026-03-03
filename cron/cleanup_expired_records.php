<?php
/**
 * Cleanup Script for Expired OTP and Pending User Records
 * This script should be run periodically via cron job
 * 
 * Recommended cron schedule: Every hour
 * Cron command: 0 * * * * php /path/to/cleanup_expired_records.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

require_once(__DIR__ . '/../config/db.php');

$logFile = __DIR__ . '/cleanup.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

logMessage("=== Starting cleanup process ===");

try {
    // Clean up expired OTP records
    $stmt = $conn->prepare("DELETE FROM otp_verifications WHERE expires_at < NOW()");
    if ($stmt->execute()) {
        $deletedOTPs = $stmt->affected_rows;
        logMessage("Deleted $deletedOTPs expired OTP records");
    } else {
        logMessage("Error deleting expired OTPs: " . $conn->error);
    }
    
    // Clean up expired pending users
    $stmt = $conn->prepare("DELETE FROM pending_users WHERE expires_at < NOW()");
    if ($stmt->execute()) {
        $deletedUsers = $stmt->affected_rows;
        logMessage("Deleted $deletedUsers expired pending user records");
    } else {
        logMessage("Error deleting expired pending users: " . $conn->error);
    }
    
    // Clean up verified OTP records older than 24 hours
    $stmt = $conn->prepare("DELETE FROM otp_verifications WHERE is_verified = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($stmt->execute()) {
        $deletedVerified = $stmt->affected_rows;
        logMessage("Deleted $deletedVerified old verified OTP records");
    } else {
        logMessage("Error deleting old verified OTPs: " . $conn->error);
    }
    
    logMessage("=== Cleanup process completed successfully ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

$conn->close();
exit(0);
?>
