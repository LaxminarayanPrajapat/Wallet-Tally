# Requirements Document

## Introduction

This document specifies the requirements for migrating the Wallet Tally application database from MySQL to Firebase Firestore while maintaining complete functional equivalence. The migration enables free deployment by replacing paid MySQL hosting with Firebase's free tier, without modifying frontend code, user experience, or application behavior.

## Glossary

- **Legacy_Database**: The existing MySQL database (wallet_tally) with 9 tables containing user, transaction, and administrative data
- **Firebase_Database**: Google Firebase Firestore NoSQL database service that will replace the Legacy_Database
- **Database_Abstraction_Layer**: PHP interface that provides uniform data access methods independent of underlying database implementation
- **Migration_Service**: Component responsible for transferring existing data from Legacy_Database to Firebase_Database
- **Query_Translator**: Component that converts SQL-style queries to Firebase Firestore queries
- **Transaction_Integrity**: Property ensuring multi-step database operations complete atomically (all succeed or all fail)
- **Session_Manager**: Component managing user authentication state and session persistence
- **File_Storage**: System for storing user-uploaded files (profile pictures)
- **Email_Service**: Component handling transactional emails and notification delivery
- **Admin_Panel**: Administrative interface for user management, transactions, feedback, and email history
- **OTP_Service**: One-Time Password generation and verification system for authentication
- **Cron_Service**: Scheduled task executor for cleanup operations
- **Response_Time**: Duration between request initiation and response delivery to client
- **Data_Consistency**: Property ensuring all data relationships and constraints are maintained across migration

## Requirements

### Requirement 1: Database Abstraction Layer

**User Story:** As a developer, I want a unified database interface, so that the application code remains unchanged regardless of the underlying database technology.

#### Acceptance Criteria

1. THE Database_Abstraction_Layer SHALL provide methods for create, read, update, and delete operations
2. THE Database_Abstraction_Layer SHALL accept parameters in the same format as current mysqli prepared statements
3. THE Database_Abstraction_Layer SHALL return data structures identical to current mysqli result sets
4. THE Database_Abstraction_Layer SHALL support both Legacy_Database and Firebase_Database implementations
5. WHEN switching between database implementations, THE Database_Abstraction_Layer SHALL require no changes to calling code
6. THE Database_Abstraction_Layer SHALL handle data type conversions between SQL and NoSQL formats transparently

### Requirement 2: Firebase Configuration and Initialization

**User Story:** As a system administrator, I want Firebase properly configured, so that the application can connect to Firebase services securely.

#### Acceptance Criteria

1. THE Firebase_Database SHALL be initialized with valid credentials from a configuration file
2. THE configuration file SHALL store Firebase project ID, API key, and service account credentials
3. THE Firebase_Database SHALL establish connection before processing any data operations
4. IF Firebase_Database connection fails, THEN THE system SHALL log the error and fall back to Legacy_Database
5. THE Firebase_Database SHALL use environment-specific configuration (development, production)
6. THE system SHALL validate Firebase credentials on application startup

### Requirement 3: User Authentication Data Migration

**User Story:** As a user, I want my account to work after migration, so that I can continue using the application without re-registering.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from users table to Firebase_Database users collection
2. FOR ALL user records, THE Migration_Service SHALL preserve id, username, password hash, email, country, currency, profile_picture, created_at, and remember_token fields
3. THE Migration_Service SHALL maintain unique constraints on username and email fields
4. THE Migration_Service SHALL preserve password hashes without modification
5. THE Migration_Service SHALL transfer remember_token and token_expiry for persistent sessions
6. WHEN a user record is migrated, THE Migration_Service SHALL verify data integrity by comparing source and destination records

### Requirement 4: Transaction Data Migration

**User Story:** As a user, I want all my financial transactions preserved, so that my financial history remains accurate and complete.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from transactions table to Firebase_Database transactions collection
2. FOR ALL transaction records, THE Migration_Service SHALL preserve id, user_id, category_id, type, amount, description, created_at, and category fields
3. THE Migration_Service SHALL maintain referential integrity between transactions and users
4. THE Migration_Service SHALL maintain referential integrity between transactions and categories
5. THE Migration_Service SHALL preserve decimal precision for amount fields (10 digits, 2 decimal places)
6. THE Migration_Service SHALL maintain transaction ordering by created_at timestamp

### Requirement 5: Category Data Migration

**User Story:** As a user, I want my custom categories preserved, so that I can continue organizing transactions as before.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from categories table to Firebase_Database categories collection
2. FOR ALL category records, THE Migration_Service SHALL preserve id, user_id, name, type, and created_at fields
3. THE Migration_Service SHALL maintain the relationship between categories and users
4. THE Migration_Service SHALL preserve category type enumeration (income, expense)
5. THE Migration_Service SHALL ensure category names remain unique per user and type combination

### Requirement 6: Admin Authentication Migration

**User Story:** As an administrator, I want admin accounts to work after migration, so that I can continue managing the system.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from admins table to Firebase_Database admins collection
2. FOR ALL admin records, THE Migration_Service SHALL preserve id, username, password hash, and created_at fields
3. THE Migration_Service SHALL maintain unique constraint on admin username field
4. THE Migration_Service SHALL preserve password hashes without modification

### Requirement 7: OTP Verification Data Migration

**User Story:** As a user in the registration process, I want my OTP verification to continue working, so that I can complete registration without interruption.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all active records from otp_verifications table to Firebase_Database otp_verifications collection
2. FOR ALL OTP records, THE Migration_Service SHALL preserve email, otp, created_at, expires_at, is_verified, attempts, resend_count, last_resend_at, and purpose fields
3. THE Migration_Service SHALL maintain indexes on email and expires_at for efficient querying
4. THE Migration_Service SHALL preserve OTP expiration logic based on expires_at timestamp
5. THE Migration_Service SHALL maintain attempt counting and resend limiting functionality

### Requirement 8: Pending User Registration Migration

**User Story:** As a user completing registration, I want my pending registration to persist, so that I can verify my email and activate my account.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all active records from pending_users table to Firebase_Database pending_users collection
2. FOR ALL pending user records, THE Migration_Service SHALL preserve username, password, email, country, currency, profile_picture, created_at, and expires_at fields
3. THE Migration_Service SHALL maintain unique constraint on email field
4. THE Migration_Service SHALL preserve expiration logic based on expires_at timestamp
5. THE Migration_Service SHALL maintain indexes on email and expires_at for cleanup operations

### Requirement 9: User Feedback Data Migration

**User Story:** As a user, I want my submitted feedback preserved, so that my testimonial and rating remain visible.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from user_feedback table to Firebase_Database user_feedback collection
2. FOR ALL feedback records, THE Migration_Service SHALL preserve user_id, rating, feedback text, created_at, updated_at, and display_approved fields
3. THE Migration_Service SHALL maintain referential integrity between feedback and users
4. THE Migration_Service SHALL preserve approval status for testimonial display
5. THE Migration_Service SHALL maintain automatic updated_at timestamp behavior

### Requirement 10: Email Logging Migration

**User Story:** As an administrator, I want email history preserved, so that I can track system communications and retry failed emails.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from email_logs table to Firebase_Database email_logs collection
2. FOR ALL email log records, THE Migration_Service SHALL preserve recipient_email, recipient_name, email_type, subject, status, error_message, admin_name, user_id, and created_at fields
3. THE Migration_Service SHALL maintain email_type enumeration (appreciation, warning, feedback_deletion, user_deletion)
4. THE Migration_Service SHALL maintain status enumeration (SUCCESS, FAILED, PENDING)
5. THE Migration_Service SHALL maintain indexes on email_type, status, created_at, and recipient_email for admin queries

### Requirement 11: User Warning System Migration

**User Story:** As an administrator, I want user warning history preserved, so that I can track disciplinary actions.

#### Acceptance Criteria

1. THE Migration_Service SHALL transfer all records from user_warnings table to Firebase_Database user_warnings collection
2. FOR ALL warning records, THE Migration_Service SHALL preserve user_id, admin_name, category, description, and created_at fields
3. THE Migration_Service SHALL maintain referential integrity between warnings and users
4. THE Migration_Service SHALL maintain indexes on user_id and created_at for efficient querying

### Requirement 12: Query Translation for User Operations

**User Story:** As a developer, I want SQL queries automatically converted to Firebase queries, so that existing code continues to work without modification.

#### Acceptance Criteria

1. THE Query_Translator SHALL convert SELECT statements with WHERE clauses to Firebase where() queries
2. THE Query_Translator SHALL convert ORDER BY clauses to Firebase orderBy() queries
3. THE Query_Translator SHALL convert LIMIT clauses to Firebase limit() queries
4. THE Query_Translator SHALL convert aggregate functions (SUM, COUNT, AVG) to client-side calculations
5. THE Query_Translator SHALL convert JOIN operations to multiple Firebase queries with client-side merging
6. THE Query_Translator SHALL convert prepared statement parameters to Firebase query parameters
7. THE Query_Translator SHALL handle comparison operators (=, !=, <, <=, >, >=, LIKE)
8. THE Query_Translator SHALL convert date/time functions (NOW(), DATE_SUB(), YEAR(), MONTH()) to PHP DateTime operations

### Requirement 13: Transaction Integrity for Multi-Step Operations

**User Story:** As a user, I want related database changes to succeed or fail together, so that my data remains consistent.

#### Acceptance Criteria

1. WHEN deleting a category, THE Database_Abstraction_Layer SHALL delete associated transactions atomically using Firebase batch writes
2. WHEN deleting a user, THE Database_Abstraction_Layer SHALL delete associated categories, transactions, feedback, and warnings atomically
3. IF any operation in a transaction fails, THEN THE Database_Abstraction_Layer SHALL roll back all changes
4. THE Database_Abstraction_Layer SHALL support Firebase transaction API for read-modify-write operations
5. THE Database_Abstraction_Layer SHALL maintain CASCADE DELETE behavior for foreign key relationships

### Requirement 14: Session Management Compatibility

**User Story:** As a user, I want to stay logged in across page loads, so that I don't have to re-authenticate repeatedly.

#### Acceptance Criteria

1. THE Session_Manager SHALL store and retrieve remember_token from Firebase_Database
2. THE Session_Manager SHALL validate token_expiry using Firebase timestamp queries
3. THE Session_Manager SHALL update last_activity timestamps in Firebase_Database
4. THE Session_Manager SHALL maintain session timeout behavior identical to Legacy_Database implementation
5. WHEN a user logs in with "remember me", THE Session_Manager SHALL store persistent token in Firebase_Database

### Requirement 15: File Upload Integration

**User Story:** As a user, I want to upload profile pictures, so that I can personalize my account.

#### Acceptance Criteria

1. THE File_Storage SHALL migrate from local filesystem to Firebase Storage
2. WHEN a user uploads a profile picture, THE system SHALL store the file in Firebase Storage and save the URL in Firebase_Database
3. THE system SHALL maintain existing profile picture URLs during migration
4. THE system SHALL preserve file access permissions (user-specific access control)
5. THE system SHALL support image formats currently accepted (JPEG, PNG, GIF)
6. THE system SHALL maintain maximum file size limits currently enforced

### Requirement 16: Scheduled Cleanup Operations

**User Story:** As a system administrator, I want expired data automatically removed, so that the database remains clean and performant.

#### Acceptance Criteria

1. THE Cron_Service SHALL delete expired OTP records from Firebase_Database where expires_at is less than current time
2. THE Cron_Service SHALL delete expired pending_users records from Firebase_Database where expires_at is less than current time
3. THE Cron_Service SHALL delete verified OTP records older than 24 hours from Firebase_Database
4. THE Cron_Service SHALL log cleanup operation results with record counts
5. THE Cron_Service SHALL execute cleanup operations on the same schedule as Legacy_Database implementation

### Requirement 17: Admin Panel Query Performance

**User Story:** As an administrator, I want admin panel pages to load quickly, so that I can efficiently manage users and transactions.

#### Acceptance Criteria

1. WHEN loading the admin dashboard, THE system SHALL retrieve summary statistics within 2 seconds
2. WHEN loading the users list, THE system SHALL retrieve paginated user data within 2 seconds
3. WHEN loading transaction history, THE system SHALL retrieve paginated transactions within 2 seconds
4. WHEN loading email history, THE system SHALL retrieve paginated email logs within 2 seconds
5. THE system SHALL implement Firebase composite indexes for multi-field queries
6. THE system SHALL cache frequently accessed data using Firebase local persistence

### Requirement 18: User Dashboard Performance

**User Story:** As a user, I want my dashboard to load quickly, so that I can view my financial summary without delay.

#### Acceptance Criteria

1. WHEN loading the user dashboard, THE system SHALL calculate total balance within 1 second
2. WHEN loading the user dashboard, THE system SHALL calculate monthly income and expenses within 1 second
3. WHEN loading transaction history, THE system SHALL retrieve transactions within 1 second
4. THE system SHALL maintain Response_Time equivalent to or better than Legacy_Database implementation
5. THE system SHALL implement Firebase query optimization strategies (indexed queries, pagination)

### Requirement 19: Data Consistency Validation

**User Story:** As a system administrator, I want to verify migration accuracy, so that I can confirm no data was lost or corrupted.

#### Acceptance Criteria

1. THE Migration_Service SHALL generate a validation report comparing Legacy_Database and Firebase_Database record counts
2. FOR ALL tables, THE Migration_Service SHALL verify record count matches between source and destination
3. THE Migration_Service SHALL perform checksum validation on critical fields (user passwords, transaction amounts)
4. THE Migration_Service SHALL identify and report any missing or mismatched records
5. THE Migration_Service SHALL verify referential integrity by checking foreign key relationships
6. IF validation fails, THEN THE Migration_Service SHALL provide detailed error report with affected record IDs

### Requirement 20: Rollback Capability

**User Story:** As a system administrator, I want to revert to MySQL if issues arise, so that I can maintain service availability.

#### Acceptance Criteria

1. THE Database_Abstraction_Layer SHALL support configuration flag to switch between Legacy_Database and Firebase_Database
2. WHEN rollback is initiated, THE system SHALL switch to Legacy_Database without code changes
3. THE system SHALL maintain Legacy_Database in read-only mode during Firebase_Database operation for emergency rollback
4. THE system SHALL provide synchronization utility to copy Firebase_Database changes back to Legacy_Database if needed
5. THE system SHALL log all database operations to enable rollback verification

### Requirement 21: Error Handling and Logging

**User Story:** As a developer, I want detailed error logs, so that I can diagnose and fix database issues quickly.

#### Acceptance Criteria

1. WHEN a Firebase_Database operation fails, THE Database_Abstraction_Layer SHALL log the error with operation details, parameters, and stack trace
2. THE Database_Abstraction_Layer SHALL log Firebase API errors with error codes and messages
3. THE Database_Abstraction_Layer SHALL maintain error log format compatible with existing log analysis tools
4. THE Database_Abstraction_Layer SHALL log slow queries exceeding 1 second execution time
5. IF Firebase_Database is unavailable, THEN THE system SHALL log connection failures and attempt reconnection

### Requirement 22: Security and Access Control

**User Story:** As a security-conscious user, I want my data protected, so that unauthorized users cannot access my information.

#### Acceptance Criteria

1. THE Firebase_Database SHALL enforce security rules preventing users from accessing other users' data
2. THE Firebase_Database SHALL require authentication for all read and write operations
3. THE Firebase_Database SHALL validate user_id matches authenticated user for all user-specific queries
4. THE Firebase_Database SHALL restrict admin collection access to authenticated admin users only
5. THE Firebase_Database SHALL implement rate limiting to prevent abuse
6. THE Firebase_Database SHALL encrypt data in transit using HTTPS
7. THE Firebase_Database SHALL encrypt data at rest using Firebase default encryption

### Requirement 23: Backup and Recovery

**User Story:** As a system administrator, I want automated backups, so that I can recover from data loss incidents.

#### Acceptance Criteria

1. THE system SHALL export Firebase_Database data to JSON format daily
2. THE system SHALL store backup files in Firebase Storage with timestamp-based naming
3. THE system SHALL retain backups for 30 days before automatic deletion
4. THE system SHALL provide restoration utility to import backup data into Firebase_Database
5. THE system SHALL verify backup integrity by validating JSON structure and record counts

### Requirement 24: Cost Monitoring

**User Story:** As a system administrator, I want to monitor Firebase usage, so that I can ensure the application remains within free tier limits.

#### Acceptance Criteria

1. THE system SHALL log daily Firebase read, write, and delete operation counts
2. THE system SHALL alert administrators when approaching 80% of free tier limits
3. THE system SHALL provide usage dashboard showing current month's Firebase consumption
4. THE system SHALL estimate monthly costs based on current usage patterns
5. THE system SHALL recommend optimization strategies when usage is high

### Requirement 25: Frontend Compatibility

**User Story:** As a user, I want the interface to work exactly as before, so that I don't need to learn new workflows.

#### Acceptance Criteria

1. THE Database_Abstraction_Layer SHALL return data in formats expected by existing JavaScript code
2. THE API endpoints SHALL maintain identical request and response formats
3. THE system SHALL preserve all AJAX response structures (success flags, data objects, error messages)
4. THE system SHALL maintain identical HTTP status codes for success and error conditions
5. THE system SHALL preserve JSON field names and data types in API responses
6. WHEN frontend JavaScript requests data, THE system SHALL respond within the same time constraints as Legacy_Database

### Requirement 26: Testing and Validation

**User Story:** As a quality assurance engineer, I want comprehensive tests, so that I can verify the migration works correctly.

#### Acceptance Criteria

1. THE test suite SHALL include unit tests for Database_Abstraction_Layer methods
2. THE test suite SHALL include integration tests for each API endpoint with Firebase_Database
3. THE test suite SHALL include end-to-end tests for critical user workflows (registration, login, transactions)
4. THE test suite SHALL include performance tests comparing Legacy_Database and Firebase_Database response times
5. THE test suite SHALL include data integrity tests verifying referential relationships
6. THE test suite SHALL achieve minimum 80% code coverage for database-related code

### Requirement 27: Documentation

**User Story:** As a developer, I want clear documentation, so that I can understand and maintain the Firebase integration.

#### Acceptance Criteria

1. THE documentation SHALL describe Database_Abstraction_Layer API with method signatures and examples
2. THE documentation SHALL provide Firebase_Database schema design with collection structures
3. THE documentation SHALL document security rules configuration
4. THE documentation SHALL provide migration runbook with step-by-step instructions
5. THE documentation SHALL document rollback procedures
6. THE documentation SHALL include troubleshooting guide for common Firebase errors
7. THE documentation SHALL document Firebase free tier limits and optimization strategies
