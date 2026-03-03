<?php
/**
 * Email Configuration
 * 
 * IMPORTANT: Update these settings with your actual SMTP credentials
 * For Gmail: Use App Password (not your regular password)
 * Enable 2-factor authentication and generate an app password at:
 * https://myaccount.google.com/apppasswords
 */

// SMTP Server Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // Use 587 for TLS
define('SMTP_ENCRYPTION', 'tls'); // Use TLS instead of SSL

// SMTP Authentication
define('SMTP_USERNAME', 'laxminarayanprajapat2022@dkasc.ac.in');
define('SMTP_PASSWORD', 'xvmv xabu muex kaxm');

// Email Settings
define('FROM_EMAIL', 'noreply@wallettally.com');
define('FROM_NAME', 'Wallet Tally');
define('REPLY_TO', 'support@wallettally.com');

// Email Logging
define('ENABLE_EMAIL_LOGGING', true);
define('EMAIL_LOG_FILE', __DIR__ . '/../logs/email.log');

// Debug Mode (set to false in production)
define('EMAIL_DEBUG_MODE', false);
?>