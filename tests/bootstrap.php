<?php

/**
 * PHPUnit Bootstrap File
 * 
 * Loads environment configuration and sets up test environment.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env.test if it exists
$envFile = __DIR__ . '/../.env.test';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!getenv($name)) {
            putenv("$name=$value");
        }
    }
}

// Set default test environment variables if not already set
$defaults = [
    'APP_ENV' => 'testing',
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'wallet_tally_test',
    'FIREBASE_EMULATOR' => 'true',
    'ERIS_ITERATIONS' => '100'
];

foreach ($defaults as $key => $value) {
    if (!getenv($key)) {
        putenv("$key=$value");
    }
}

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

echo "Test environment initialized\n";
echo "Database: " . getenv('DB_NAME') . "\n";
echo "Firebase Emulator: " . (getenv('FIREBASE_EMULATOR') === 'true' ? 'enabled' : 'disabled') . "\n";
echo "Eris Iterations: " . getenv('ERIS_ITERATIONS') . "\n";
