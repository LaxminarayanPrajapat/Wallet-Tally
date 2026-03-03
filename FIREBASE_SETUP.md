# Firebase Setup Guide

This guide walks you through setting up Firebase for the Wallet Tally application migration.

## Prerequisites

- Google account
- PHP 7.4 or higher
- Composer installed
- Access to create Firebase projects

## Step 1: Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project" or "Create a project"
3. Enter project name: `wallet-tally` (or your preferred name)
4. (Optional) Enable Google Analytics if desired
5. Click "Create project" and wait for setup to complete

## Step 2: Enable Firestore Database

1. In your Firebase project, click "Firestore Database" in the left sidebar
2. Click "Create database"
3. Choose "Start in production mode" (we'll add security rules later)
4. Select a Cloud Firestore location (choose closest to your users)
5. Click "Enable"

## Step 3: Enable Firebase Storage

1. In your Firebase project, click "Storage" in the left sidebar
2. Click "Get started"
3. Review security rules and click "Next"
4. Select a storage location (same as Firestore for consistency)
5. Click "Done"

## Step 4: Generate Service Account Credentials

1. In Firebase Console, click the gear icon ⚙️ next to "Project Overview"
2. Select "Project settings"
3. Go to the "Service accounts" tab
4. Click "Generate new private key"
5. Click "Generate key" in the confirmation dialog
6. A JSON file will download (e.g., `wallet-tally-firebase-adminsdk-xxxxx.json`)
7. **IMPORTANT**: Keep this file secure - it grants full access to your Firebase project

## Step 5: Install the Service Account Credentials

1. Rename the downloaded JSON file to `firebase-credentials.json`
2. Move it to the `config/` directory of your project:
   ```bash
   mv ~/Downloads/wallet-tally-firebase-adminsdk-*.json config/firebase-credentials.json
   ```
3. Verify the file is in `.gitignore` (it should be already)

## Step 6: Create Firebase Configuration File

1. Copy the example configuration:
   ```bash
   cp config/firebase_config.example.php config/firebase_config.php
   ```

2. Edit `config/firebase_config.php` with your project details:
   ```php
   <?php
   return [
       'project_id' => 'your-actual-project-id',  // From Firebase Console
       'credentials_path' => __DIR__ . '/firebase-credentials.json',
       'database_url' => 'https://your-project-id.firebaseio.com',
       'storage_bucket' => 'your-project-id.appspot.com',
       
       'environment' => 'development',  // Change to 'production' when ready
       'fallback_enabled' => true,      // Keep MySQL fallback enabled initially
       'use_firebase' => false,         // Set to true after testing
   ];
   ```

3. Find your project ID:
   - In Firebase Console, go to Project Settings
   - Copy the "Project ID" value
   - Update `project_id` in the config file

## Step 7: Install Firebase PHP SDK

Run Composer to install dependencies:

```bash
composer install
```

The Firebase SDK (`google/cloud-firestore`) is already in `composer.json`, so this will install it.

## Step 8: Verify Installation

Create a test script to verify Firebase connection:

```php
<?php
// test_firebase.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/firebase_config.php';

try {
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => $config['project_id'],
        'keyFilePath' => $config['credentials_path']
    ]);
    
    echo "✓ Firebase connection successful!\n";
    echo "Project ID: " . $config['project_id'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Firebase connection failed: " . $e->getMessage() . "\n";
}
```

Run the test:
```bash
php test_firebase.php
```

## Step 9: Environment-Specific Configuration

For different environments (development, staging, production), you can:

### Option A: Multiple Config Files
```bash
config/firebase_config.development.php
config/firebase_config.production.php
```

Then load based on environment variable:
```php
$env = getenv('APP_ENV') ?: 'development';
$config = require __DIR__ . "/config/firebase_config.{$env}.php";
```

### Option B: Environment Variables
Use a `.env` file (already in `.gitignore`):
```
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS_PATH=/path/to/credentials.json
APP_ENV=development
```

## Security Checklist

- [ ] `firebase-credentials.json` is in `.gitignore`
- [ ] `config/firebase_config.php` is in `.gitignore`
- [ ] Service account JSON file has restricted file permissions (600)
- [ ] Never commit credentials to version control
- [ ] Use different Firebase projects for dev/staging/production

## Troubleshooting

### "Could not load the default credentials"
- Verify `credentials_path` points to the correct JSON file
- Check file permissions (should be readable by PHP process)
- Ensure the JSON file is valid (not corrupted)

### "Permission denied" errors
- Verify Firestore is enabled in Firebase Console
- Check that security rules allow access
- Ensure service account has necessary permissions

### "Project not found"
- Verify `project_id` matches exactly (case-sensitive)
- Ensure the Firebase project exists and is active

## Next Steps

After completing this setup:
1. Proceed to Task 2: Implement database abstraction layer
2. Keep `use_firebase` set to `false` until migration is complete
3. Test thoroughly in development before enabling in production

## Resources

- [Firebase Console](https://console.firebase.google.com/)
- [Firestore PHP Documentation](https://cloud.google.com/firestore/docs/reference/libraries#client-libraries-install-php)
- [Firebase Admin SDK Setup](https://firebase.google.com/docs/admin/setup)
