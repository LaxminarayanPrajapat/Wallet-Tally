# Firebase Setup Checklist

Quick reference for setting up Firebase for Wallet Tally migration.

## ☐ Step 1: Create Firebase Project
- [ ] Go to https://console.firebase.google.com/
- [ ] Create new project named "wallet-tally"
- [ ] Note your Project ID: `___________________`

## ☐ Step 2: Enable Services
- [ ] Enable Firestore Database (production mode)
- [ ] Enable Firebase Storage
- [ ] Choose location: `___________________`

## ☐ Step 3: Download Credentials
- [ ] Go to Project Settings → Service Accounts
- [ ] Click "Generate new private key"
- [ ] Download JSON file
- [ ] Rename to `firebase-credentials.json`
- [ ] Move to `config/` directory

## ☐ Step 4: Configure Application
- [ ] Copy: `cp config/firebase_config.example.php config/firebase_config.php`
- [ ] Edit `config/firebase_config.php`:
  - [ ] Set `project_id` to your Firebase Project ID
  - [ ] Verify `credentials_path` points to `firebase-credentials.json`
  - [ ] Set `environment` to `development`
  - [ ] Keep `use_firebase` as `false` for now

## ☐ Step 5: Install Dependencies
```bash
composer install
```

## ☐ Step 6: Test Connection
```bash
php test_firebase_connection.php
```

Expected output: "All Tests Passed!"

## ☐ Step 7: Security Verification
- [ ] Verify `firebase-credentials.json` is NOT in git
- [ ] Verify `config/firebase_config.php` is NOT in git
- [ ] Check file permissions: `chmod 600 config/firebase-credentials.json`

## Configuration Values

Fill in your actual values:

```php
'project_id' => '___________________',  // From Firebase Console
'storage_bucket' => '___________________.appspot.com',
'database_url' => 'https://___________________.firebaseio.com',
```

## Environment Setup

### Development
```php
'environment' => 'development',
'fallback_enabled' => true,
'use_firebase' => false,  // Test first!
```

### Production (after testing)
```php
'environment' => 'production',
'fallback_enabled' => true,  // Keep for safety
'use_firebase' => true,      // Enable after validation
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Could not load credentials" | Check `credentials_path` is correct |
| "Permission denied" | Verify Firestore is enabled in console |
| "Project not found" | Check `project_id` matches exactly |
| Test script fails | Run `composer install` first |

## Next Steps

After all checkboxes are complete:
1. ✓ Mark Task 1 as complete in tasks.md
2. → Proceed to Task 2: Implement database abstraction layer
3. Keep MySQL as primary database until migration is complete

## Support Resources

- Firebase Console: https://console.firebase.google.com/
- Firestore Docs: https://firebase.google.com/docs/firestore
- PHP SDK: https://cloud.google.com/firestore/docs/reference/libraries#client-libraries-install-php
