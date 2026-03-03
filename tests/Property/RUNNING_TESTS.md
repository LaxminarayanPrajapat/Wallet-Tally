# Running Property Test 2.3: Database Abstraction Layer

This guide explains how to run the property test for database implementation switching (Property 22).

## Prerequisites

1. **MySQL Test Database**: Create a test database
2. **Firebase Emulator** (recommended) or Firebase project
3. **PHP Dependencies**: Eris and PHPUnit installed via Composer

## Quick Start

### Step 1: Set Up Test Database

```bash
# Create MySQL test database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS wallet_tally_test;"
```

### Step 2: Configure Environment

```bash
# Copy example environment file
cp .env.test.example .env.test

# Edit .env.test with your credentials
nano .env.test
```

### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install
```

### Step 4: Run the Property Test

```bash
# Run only the database abstraction property test
composer test:property -- --filter DatabaseAbstractionPropertiesTest

# Or run directly with PHPUnit
vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php
```

## Running with Firebase Emulator (Recommended)

The Firebase emulator allows testing without consuming production quota:

```bash
# Install Firebase CLI (if not already installed)
npm install -g firebase-tools

# Initialize Firebase in your project
firebase init emulators

# Start Firestore emulator
firebase emulators:start --only firestore

# In another terminal, run the tests
FIREBASE_EMULATOR=true composer test:property -- --filter DatabaseAbstractionPropertiesTest
```

## Running Against Real Firebase (Not Recommended for Testing)

If you must test against a real Firebase project:

```bash
# Set environment variables
export FIREBASE_PROJECT_ID=your-project-id
export FIREBASE_CREDENTIALS_PATH=/path/to/credentials.json
export FIREBASE_EMULATOR=false

# Run tests
composer test:property -- --filter DatabaseAbstractionPropertiesTest
```

**Warning**: This will consume Firebase quota and may incur costs.

## Test Execution Options

### Run with More Iterations

```bash
# Run with 200 iterations instead of default 100
ERIS_ITERATIONS=200 vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php
```

### Run Specific Test Method

```bash
# Run only the main switching property test
vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php \
  --filter testDatabaseImplementationSwitchingProducesEquivalentResults
```

### Run with Verbose Output

```bash
# See detailed output for each iteration
vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php --verbose
```

## Understanding Test Output

### Successful Test Run

```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

Test environment initialized
Database: wallet_tally_test
Firebase Emulator: enabled
Eris Iterations: 100

....                                                                4 / 4 (100%)

Time: 00:15.234, Memory: 12.00 MB

OK (4 tests, 400 assertions)
```

### Failed Test Run

If a property is violated, Eris will show:

```
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'US'
+'UK'

Failure after 42 iterations with seed 1234567890
Minimal failing input: ['username' => 'test', 'country' => 'UK']
```

The "minimal failing input" is the smallest input that reproduces the failure.

## Troubleshooting

### "Firebase database not available for testing"

The test is skipped because FirebaseDatabase class is not found. This is expected if you haven't implemented the Firebase database yet. The test will run once FirebaseDatabase is implemented.

### "MySQL connection failed"

Check your database credentials in `.env.test`:
- Verify DB_HOST, DB_USER, DB_PASS are correct
- Ensure MySQL server is running
- Verify the test database exists

### "Table doesn't exist"

The test creates tables automatically. If this fails:
- Check MySQL user has CREATE TABLE permissions
- Verify database name is correct
- Check MySQL error logs

### "Firebase connection failed"

If using emulator:
- Verify emulator is running: `firebase emulators:start --only firestore`
- Check FIRESTORE_EMULATOR_HOST is set correctly

If using real Firebase:
- Verify credentials file exists and path is correct
- Check project ID is correct
- Ensure service account has Firestore permissions

## What This Test Validates

Property 22 validates that:

1. **Insert operations** produce equivalent results in MySQL and Firebase
2. **Query operations** return the same data from both implementations
3. **Update operations** apply changes equivalently
4. **Delete operations** remove records equivalently
5. **Count operations** return the same values

This ensures the database abstraction layer works correctly and switching between implementations requires no code changes.

## Next Steps

After this test passes:

1. Implement remaining property tests (Properties 1-21, 23-33)
2. Run full property test suite: `composer test:property`
3. Verify all 33 correctness properties pass
4. Proceed to integration testing

## References

- Design Document: `.kiro/specs/firebase-migration/design.md`
- Requirements: `.kiro/specs/firebase-migration/requirements.md`
- Property Test README: `tests/Property/README.md`
