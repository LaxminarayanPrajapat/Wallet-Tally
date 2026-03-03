<?php
/**
 * Firebase Connection Test Script
 * 
 * This script verifies that Firebase is properly configured and accessible.
 * Run this after completing the Firebase setup steps.
 * 
 * Usage: php test_firebase_connection.php
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "=== Firebase Connection Test ===\n\n";

// Check if config file exists
$configPath = __DIR__ . '/config/firebase_config.php';
if (!file_exists($configPath)) {
    echo "✗ Error: config/firebase_config.php not found\n";
    echo "  Please copy config/firebase_config.example.php to config/firebase_config.php\n";
    exit(1);
}

echo "✓ Configuration file found\n";

// Load configuration
$config = require $configPath;

// Validate configuration
$requiredKeys = ['project_id', 'credentials_path', 'environment'];
foreach ($requiredKeys as $key) {
    if (!isset($config[$key]) || empty($config[$key])) {
        echo "✗ Error: Missing or empty configuration key: {$key}\n";
        exit(1);
    }
}

echo "✓ Configuration loaded successfully\n";
echo "  - Project ID: {$config['project_id']}\n";
echo "  - Environment: {$config['environment']}\n";

// Check if credentials file exists
if (!file_exists($config['credentials_path'])) {
    echo "✗ Error: Credentials file not found at: {$config['credentials_path']}\n";
    echo "  Please download service account JSON from Firebase Console\n";
    exit(1);
}

echo "✓ Credentials file found\n";

// Validate credentials file is valid JSON
$credentialsContent = file_get_contents($config['credentials_path']);
$credentials = json_decode($credentialsContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "✗ Error: Credentials file is not valid JSON\n";
    echo "  JSON Error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✓ Credentials file is valid JSON\n";

// Verify required fields in credentials
$requiredCredentialKeys = ['type', 'project_id', 'private_key', 'client_email'];
foreach ($requiredCredentialKeys as $key) {
    if (!isset($credentials[$key])) {
        echo "✗ Error: Missing required field in credentials: {$key}\n";
        exit(1);
    }
}

echo "✓ Credentials contain required fields\n";
echo "  - Service Account: {$credentials['client_email']}\n";

// Attempt to initialize Firestore client
try {
    echo "\nAttempting to connect to Firestore...\n";
    
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => $config['project_id'],
        'keyFilePath' => $config['credentials_path']
    ]);
    
    echo "✓ Firestore client initialized successfully\n";
    
    // Try to list collections (this will verify actual connectivity)
    echo "\nTesting Firestore connectivity...\n";
    $collections = $firestore->collections();
    
    echo "✓ Successfully connected to Firestore!\n";
    
    // List existing collections (if any)
    $collectionNames = [];
    foreach ($collections as $collection) {
        $collectionNames[] = $collection->id();
    }
    
    if (empty($collectionNames)) {
        echo "  - No collections found (this is normal for a new project)\n";
    } else {
        echo "  - Found collections: " . implode(', ', $collectionNames) . "\n";
    }
    
    echo "\n=== All Tests Passed! ===\n";
    echo "Firebase is properly configured and accessible.\n";
    echo "You can proceed to the next task.\n";
    
} catch (Google\Cloud\Core\Exception\ServiceException $e) {
    echo "✗ Firestore API Error: " . $e->getMessage() . "\n";
    echo "  This might indicate:\n";
    echo "  - Firestore is not enabled in Firebase Console\n";
    echo "  - Service account lacks necessary permissions\n";
    echo "  - Network connectivity issues\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    echo "  Error type: " . get_class($e) . "\n";
    exit(1);
}
