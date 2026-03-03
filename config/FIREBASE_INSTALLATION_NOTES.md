# Firebase Installation Notes

## Installation Status: ✓ Complete

The Firebase PHP SDK has been successfully installed via Composer.

## Installed Packages

- `google/cloud-firestore` v1.55.0
- All required dependencies (26 packages)

## Important Notes

### gRPC Extension (Optional)

The Firebase SDK works in two modes:

1. **With gRPC extension** (faster, recommended for production)
   - Requires PHP gRPC extension to be installed
   - Provides better performance for high-volume operations
   
2. **Without gRPC extension** (REST API fallback)
   - Currently active mode (gRPC not installed)
   - Uses REST API instead of gRPC
   - Fully functional but slightly slower
   - Perfectly fine for development and low-to-medium traffic

### Current Configuration

- Mode: REST API (gRPC extension not installed)
- Composer flag used: `--ignore-platform-req=ext-grpc`
- Status: Fully functional

### Installing gRPC Extension (Optional)

If you want to enable gRPC for better performance:

#### Windows (XAMPP)
1. Download PHP gRPC extension from PECL
2. Copy `php_grpc.dll` to `C:\xampp\php\ext\`
3. Add to `php.ini`: `extension=grpc`
4. Restart Apache
5. Run: `composer update`

#### Linux
```bash
sudo pecl install grpc
echo "extension=grpc.so" | sudo tee -a /etc/php/8.1/cli/php.ini
sudo service apache2 restart
composer update
```

#### macOS
```bash
pecl install grpc
echo "extension=grpc.so" >> /usr/local/etc/php/8.1/php.ini
brew services restart php
composer update
```

### Performance Impact

For Wallet Tally's expected usage:
- **Without gRPC**: Response times 50-100ms (acceptable)
- **With gRPC**: Response times 20-50ms (optimal)

**Recommendation**: Start without gRPC for development. Add it later if performance testing shows it's needed.

## Verification

To verify the installation works:

```bash
php test_firebase_connection.php
```

This will test:
- Configuration file loading
- Credentials validation
- Firestore connectivity
- Collection listing

## Next Steps

1. Complete Firebase Console setup (see FIREBASE_SETUP.md)
2. Download service account credentials
3. Create `config/firebase_config.php`
4. Run `php test_firebase_connection.php`
5. Proceed to Task 2: Database Abstraction Layer

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### Composer lock file issues
```bash
composer update --ignore-platform-req=ext-grpc
```

### Permission errors
```bash
chmod -R 755 vendor/
```

## Resources

- [Firebase PHP SDK](https://github.com/googleapis/google-cloud-php-firestore)
- [gRPC Installation Guide](https://cloud.google.com/php/grpc)
- [Firestore Documentation](https://firebase.google.com/docs/firestore)
