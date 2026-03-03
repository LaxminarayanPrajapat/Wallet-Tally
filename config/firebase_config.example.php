<?php
/**
 * Firebase Configuration Example
 * 
 * Copy this file to firebase_config.php and update with your Firebase project details.
 * DO NOT commit firebase_config.php to version control.
 */

return [
    'project_id' => 'your-firebase-project-id',
    'credentials_path' => __DIR__ . '/firebase-credentials.json',
    'database_url' => 'https://your-project-id.firebaseio.com',
    'storage_bucket' => 'your-project-id.appspot.com',
    
    // Environment (development, staging, production)
    'environment' => 'development',
    
    // Fallback to MySQL if Firebase fails
    'fallback_enabled' => true,
    
    // Enable Firebase or use MySQL
    'use_firebase' => false, // Set to true after migration
];
