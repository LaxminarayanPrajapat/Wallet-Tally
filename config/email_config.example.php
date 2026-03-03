<?php
/**
 * Email Configuration Example
 * 
 * Copy this file to email_config.php and update with your SMTP credentials.
 * DO NOT commit email_config.php to version control.
 */

return [
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@example.com',
    'smtp_password' => 'your-password',
    'smtp_encryption' => 'tls', // tls or ssl
    'from_email' => 'noreply@example.com',
    'from_name' => 'Wallet Tally',
];
