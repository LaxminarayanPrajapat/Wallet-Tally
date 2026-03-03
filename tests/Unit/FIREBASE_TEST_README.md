# FirebaseDatabase Unit Tests

## Overview

This document describes the unit tests for the `FirebaseDatabase` implementation, which validates CRUD operations, error handling, fallback logic, and batch operation limits.

## Test Coverage

### Requirements Validated
- **Requirement 1.1**: CRUD operations, query operations, aggregate operations, batch operations, transactions
- **Requirement 13.1**: Batch operations with Firebase 500 operation limit
- **Requirement 21.1**: Error handling, logging, fallback to MySQL

### Test Categories

1. **Connection Tests**
   - Connection establishment
   - Disconnection
   - Connection failure handling

2. **CRUD Operation Tests**
   - Insert with document ID generation
   - Insert with automatic server timestamp
   - FindById retrieval
   - Update existing documents
   - Delete documents

3. **Query Operation Tests**
   - Query with conditions
   - Query with operator conditions (>, <, >=, <=, !=)
   - Query with ORDER BY
   - Query with LIMIT
   - QueryOne (first match)
   - Count operation

4. **Aggregate Operation Tests**
   - Sum (client-side calculation)
   - Average (client-side calculation)
   - Sum with conditions

5. **Batch Operation Tests**
   - Batch insert multiple documents
   - Batch delete multiple documents
   - Batch operations with 500 limit (Firebase constraint)
   - Batch operations with 600+ documents (multiple batches)

6. **Transaction Tests**
   - Transaction commit
   - Transaction rollback
   - Multiple operations in single transaction

7. **Error Handling Tests**
   - Invalid data validation
   - Invalid field names
   - Nonexistent document operations
   - Empty data handling

8. **Fallback Logic Tests**
   - Fallback to MySQL on Firebase connection failure
   - Operations use fallback when Firebase unavailable
   - Graceful degradation

9. **Data Type Conversion Tests**
   - Null values
   - Boolean values
   - Decimal precision
   - Timestamp conversion

10. **Edge Case Tests**
    - Empty strings
    - Large decimal values
    - Negative amounts
    - Special characters
    - No results scenarios
    - Empty batch operations

11. **PreparedStatement Interface Tests**
    - Prepared statement insert
    - Prepared statement select

## Setup Requirements

### 1. Firebase Emulator

The tests require the Firebase emulator to be running. Install and start it:

```bash
# Install Firebase CLI (if not already installed)
npm install -g firebase-tools

# Login to Firebase (first time only)
firebase login

# Initialize Firebase in your project (first time only)
firebase init emulators

# Start the Firestore emulator
firebase emulators:start --only firestore
```

The emulator will run on `localhost:8080` by default.

### 2. Environment Configuration

Create or update `.env.test` file:

```env
# Firebase Emulator Configuration
FIREBASE_EMULATOR=true
FIREBASE_PROJECT_ID=demo-test-project
FIREBASE_CREDENTIALS_PATH=config/firebase-credentials.json

# MySQL Fallback Configuration (for fallback tests)
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=wallet_tally_test
```

### 3. Test Database

For fallback tests, ensure MySQL test database exists:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS wallet_tally_test;"
```

## Running the Tests

### Run All Firebase Tests

```bash
vendor/bin/phpunit tests/Unit/FirebaseDatabaseTest.php
```

### Run Specific Test

```bash
vendor/bin/phpunit tests/Unit/FirebaseDatabaseTest.php --filter testInsertCreatesNewDocument
```

### Run with Verbose Output

```bash
vendor/bin/phpunit tests/Unit/FirebaseDatabaseTest.php --verbose
```

### Run with Coverage Report

```bash
vendor/bin/phpunit tests/Unit/FirebaseDatabaseTest.php --coverage-html coverage/
```

## Skipping Firebase Tests

If you don't have the Firebase emulator running, set:

```env
FIREBASE_EMULATOR=false
```

Tests will be skipped with a message indicating the emulator is not available.

## Test Execution Flow

1. **setUp()**: 
   - Checks if Firebase emulator is enabled
   - Creates FirebaseDatabase instance
   - Sets emulator host environment variable
   - Connects to Firebase
   - Cleans up test collection

2. **Test Execution**:
   - Each test runs independently
   - Tests insert, query, update, or delete documents
   - Assertions verify expected behavior

3. **tearDown()**:
   - Cleans up test collection
   - Disconnects from Firebase
   - Clears emulator environment variable

## Key Test Scenarios

### Batch Operation Limit Test

Firebase has a 500 operation limit per batch. The test validates:

```php
testBatchOperationHandles500Limit()
```

- Inserts 600 records
- Verifies automatic batch splitting
- Confirms all 600 records are inserted

### Fallback Logic Test

When Firebase is unavailable, operations should fall back to MySQL:

```php
testFallbackToMySQLOnConnectionFailure()
testOperationsUseFallbackWhenFirebaseUnavailable()
```

- Creates Firebase with invalid credentials
- Provides MySQL fallback database
- Verifies operations use MySQL

### Error Handling Test

Validates proper error handling for invalid operations:

```php
testInsertWithInvalidDataFails()
testInsertWithInvalidFieldNameFails()
```

- Empty data validation
- Invalid field name validation
- Returns false on validation failure

## Troubleshooting

### Emulator Not Running

**Error**: "Firebase emulator is not available"

**Solution**: Start the emulator:
```bash
firebase emulators:start --only firestore
```

### Connection Timeout

**Error**: Connection timeout to localhost:8080

**Solution**: 
1. Check if emulator is running: `lsof -i :8080`
2. Restart emulator
3. Check firewall settings

### Permission Denied

**Error**: Permission denied writing to config directory

**Solution**: Ensure write permissions:
```bash
chmod 755 config/
```

### MySQL Not Available

**Error**: MySQL database is not available

**Solution**: 
1. Start MySQL server
2. Create test database: `CREATE DATABASE wallet_tally_test;`
3. Update `.env.test` with correct credentials

## Test Metrics

- **Total Tests**: 50+
- **Test Coverage**: CRUD operations, queries, aggregates, batches, transactions, error handling, fallback logic
- **Execution Time**: ~5-10 seconds (with emulator)
- **Requirements Validated**: 1.1, 13.1, 21.1

## Integration with CI/CD

To run these tests in CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Start Firebase Emulator
  run: |
    npm install -g firebase-tools
    firebase emulators:start --only firestore &
    sleep 5

- name: Run Firebase Tests
  run: vendor/bin/phpunit tests/Unit/FirebaseDatabaseTest.php
```

## Notes

- Tests use a dedicated test collection (`test_firebase_db`) that is cleaned up after each test
- The emulator provides a clean slate for each test run
- No production data is affected
- Tests are isolated and can run in parallel
- Fallback tests require MySQL to be available
