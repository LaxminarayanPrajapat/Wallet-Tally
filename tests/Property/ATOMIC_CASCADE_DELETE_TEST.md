# Atomic Cascade Delete Property Test

## Overview

This property test validates **Property 10: Atomic Cascade Delete** from the Firebase Migration design document.

**Property Statement**: For any delete operation with cascading relationships (delete category→transactions, delete user→categories/transactions/feedback/warnings), either all related records are deleted or none are deleted.

**Validates Requirements:**
- 13.1: Atomic category deletion with associated transactions using Firebase batch writes
- 13.2: Atomic user deletion with all related records (categories, transactions, feedback, warnings)
- 13.5: CASCADE DELETE behavior for foreign key relationships

## Test File

`tests/Property/AtomicCascadeDeletePropertiesTest.php`

## Properties Tested

### 1. Category Delete Cascades to Transactions Atomically

**Property**: When deleting a category, all associated transactions must be deleted atomically. Either all deletions succeed or none succeed.

**Test Strategy**:
- Generate random category data and 1-10 transactions
- Insert category and transactions into database
- Perform cascade delete using transaction
- Verify all records are deleted
- On failure, verify rollback leaves all records intact

**Validates**: Requirements 13.1, 13.2, 13.5

### 2. User Delete Cascades to All Related Records Atomically

**Property**: When deleting a user, all related categories, transactions, feedback, and warnings must be deleted atomically.

**Test Strategy**:
- Generate random user data
- Create 1-5 categories, 1-10 transactions, 0-3 feedback records, 0-2 warnings
- Perform cascade delete in correct order (transactions → categories → feedback → warnings → user)
- Verify all records are deleted
- On failure, verify rollback leaves all records intact

**Validates**: Requirements 13.2, 13.5

### 3. Cascade Delete Rolls Back on Failure

**Property**: If any step in a cascade delete fails, the entire operation should be rolled back and no records should be deleted.

**Test Strategy**:
- Insert category with multiple transactions
- Begin transaction
- Delete some transactions
- Intentionally cause failure (delete non-existent record)
- Rollback transaction
- Verify all original records still exist

**Validates**: Requirements 13.1, 13.2, 13.5

### 4. Batch Delete Operations Are Atomic

**Property**: When deleting multiple records in a batch, either all deletions succeed or none succeed.

**Test Strategy**:
- Generate 2-20 random transactions
- Insert all transactions
- Perform batch delete
- Verify all records are deleted
- Test with both MySQL and Firebase implementations

**Validates**: Requirements 13.1, 13.2

## Running the Tests

### Prerequisites

1. MySQL test database configured
2. Firebase emulator running (optional, for Firebase tests)
3. Environment variables set:

```bash
export DB_HOST=localhost
export DB_USER=root
export DB_PASS=your_password
export DB_NAME=wallet_tally_test
export FIREBASE_PROJECT_ID=your-project-id
export FIREBASE_CREDENTIALS_PATH=/path/to/credentials.json
export FIREBASE_EMULATOR=true
```

### Run All Property Tests

```bash
composer test:property
```

### Run Only Atomic Cascade Delete Tests

```bash
vendor/bin/phpunit tests/Property/AtomicCascadeDeletePropertiesTest.php
```

### Run with Verbose Output

```bash
vendor/bin/phpunit tests/Property/AtomicCascadeDeletePropertiesTest.php --verbose
```

### Run with Custom Iteration Count

```bash
ERIS_ITERATIONS=200 vendor/bin/phpunit tests/Property/AtomicCascadeDeletePropertiesTest.php
```

## Test Data Generation

The test uses Eris generators to create random test data:

- **Category Data**: Random user_id, category name, type (income/expense)
- **Transaction Data**: Random amounts (1-10000), descriptions, types
- **User Data**: Random username, email, password hash, country, currency
- **Counts**: Random number of related records (1-10 transactions, 1-5 categories, etc.)

Each property test runs 100 iterations by default with different random inputs.

## Expected Behavior

### MySQL Implementation

- Uses `BEGIN TRANSACTION`, `COMMIT`, `ROLLBACK` for atomicity
- Cascade deletes implemented in application code
- Foreign key constraints not enforced (application-level integrity)

### Firebase Implementation

- Uses Firebase batch writes (up to 500 operations per batch)
- Multiple batches executed sequentially for large deletes
- Rollback simulated by not committing batch on error

## Debugging Failed Tests

When a property test fails, Eris will:

1. Show the failing input that violated the property
2. Attempt to shrink the input to find minimal failing case
3. Display the shrunk input for easier debugging

Example failure output:

```
Failed asserting that null is not null.
Category should still exist after rollback

Failure after 42 iterations with seed 1234567890
Minimal failing input: [
  'categoryData' => ['user_id' => 1, 'category_name' => 'test', 'category_type' => 'income'],
  'transactions' => [['amount' => 100, 'description' => 'a']]
]
```

## Common Issues

### Issue: Test tables not created

**Solution**: Ensure MySQL connection is working and user has CREATE TABLE permissions.

### Issue: Firebase tests skipped

**Solution**: This is expected if Firebase is not configured. Tests will run with MySQL only.

### Issue: Transaction rollback not working

**Solution**: Verify database supports transactions (InnoDB engine for MySQL).

### Issue: Batch delete exceeds 500 operations

**Solution**: Firebase has a 500 operation limit per batch. Implementation should split into multiple batches.

## Integration with CI/CD

Add to your CI pipeline:

```yaml
- name: Run Property Tests
  run: |
    composer test:property
  env:
    DB_HOST: localhost
    DB_USER: root
    DB_PASS: password
    DB_NAME: wallet_tally_test
    FIREBASE_EMULATOR: true
```

## References

- Design Document: `.kiro/specs/firebase-migration/design.md` (Property 10)
- Requirements Document: `.kiro/specs/firebase-migration/requirements.md` (Requirements 13.1, 13.2, 13.5)
- Eris Documentation: https://github.com/giorgiosironi/eris
- Firebase Batch Writes: https://firebase.google.com/docs/firestore/manage-data/transactions#batched-writes
