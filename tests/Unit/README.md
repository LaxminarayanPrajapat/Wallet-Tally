# Unit Tests for DatabaseInterface

This directory contains unit tests for the DatabaseInterface implementations, specifically testing the MySQLDatabase class.

## Prerequisites

Before running these tests, you need:

1. **MySQL Server Running**: Ensure MySQL is installed and running on your system
2. **Test Database**: Create a test database named `wallet_tally_test` (or configure via environment variables)
3. **Database Permissions**: The test user needs CREATE, DROP, INSERT, UPDATE, DELETE, and SELECT permissions

## Setup

### 1. Start MySQL Server

**Windows:**
```bash
# Start MySQL service
net start MySQL80

# Or use XAMPP/WAMP control panel
```

**Linux/Mac:**
```bash
# Start MySQL service
sudo systemctl start mysql
# or
sudo service mysql start
```

### 2. Create Test Database

```sql
CREATE DATABASE IF NOT EXISTS wallet_tally_test;
GRANT ALL PRIVILEGES ON wallet_tally_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configure Environment Variables (Optional)

Create a `.env.test` file in the project root:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=wallet_tally_test
```

## Running the Tests

### Run all unit tests:
```bash
vendor/bin/phpunit tests/Unit
```

### Run DatabaseInterface tests specifically:
```bash
vendor/bin/phpunit tests/Unit/DatabaseInterfaceTest.php
```

### Run with detailed output:
```bash
vendor/bin/phpunit tests/Unit/DatabaseInterfaceTest.php --testdox
```

### Run with verbose output:
```bash
vendor/bin/phpunit tests/Unit/DatabaseInterfaceTest.php --verbose
```

## Test Coverage

The DatabaseInterfaceTest covers:

### CRUD Operations
- Insert: Creating new records
- Read: Finding records by ID
- Update: Modifying existing records
- Delete: Removing records

### Query Operations
- Query with conditions (equality, operators)
- Query with ORDER BY
- Query with LIMIT
- QueryOne (single record)
- Count records

### Aggregate Operations
- SUM with and without conditions
- AVG (average)

### Batch Operations
- Batch insert (multiple records)
- Batch delete (multiple records)

### Transaction Management
- Begin transaction
- Commit transaction
- Rollback transaction

### PreparedStatement Interface
- Prepared statement insert
- Prepared statement select
- Prepared statement update
- Prepared statement delete
- getInsertId()
- getAffectedRows()
- getResult()

### Edge Cases
- Null values
- Empty strings
- Boundary decimal values (min/max)
- Negative amounts
- Special characters in strings
- Queries with no results
- Operations on nonexistent records
- Empty arrays for batch operations

## Validates Requirements

These tests validate the following requirements from the Firebase migration spec:

- **Requirement 1.1**: Database abstraction layer provides CRUD operations
- **Requirement 1.2**: Accepts parameters in same format as mysqli prepared statements
- **Requirement 1.3**: Returns data structures identical to mysqli result sets

## Troubleshooting

### "MySQL database is not available"
- Ensure MySQL server is running
- Check database credentials in `.env.test` or environment variables
- Verify the test database exists

### "Access denied for user"
- Check MySQL username and password
- Verify user has necessary permissions on the test database

### "Unknown database 'wallet_tally_test'"
- Create the test database using the SQL command above

### Connection timeout
- Check if MySQL is listening on the correct port (default: 3306)
- Verify firewall settings allow local connections

## Notes

- Tests automatically create and drop a test table (`test_db_interface`)
- Each test is isolated and cleans up after itself
- Tests will be skipped if MySQL is not available (not marked as failures)
- The test database should be separate from your production database
