# Implementation Plan: Firebase Migration

## Overview

This plan implements the migration of Wallet Tally from MySQL to Firebase Firestore while maintaining complete functional equivalence. The implementation follows a layered approach: first establishing the database abstraction layer, then implementing Firebase-specific components, followed by migration tooling, and finally testing and validation.

## Tasks

- [x] 1. Set up Firebase project and configuration
  - Create Firebase project in Google Cloud Console
  - Generate service account credentials JSON file
  - Create PHP configuration file for Firebase credentials (project_id, credentials_path)
  - Install Firebase PHP SDK via Composer (`google/cloud-firestore`)
  - Set up environment-specific configuration (development, production)
  - _Requirements: 2.1, 2.2, 2.5_

- [x] 2. Implement database abstraction layer
  - [x] 2.1 Create DatabaseInterface with CRUD, query, transaction, and batch operation methods
    - Define interface with connect(), insert(), update(), delete(), findById(), query(), queryOne(), count(), sum(), avg(), beginTransaction(), commit(), rollback(), batchDelete(), batchInsert(), prepare() methods
    - Define PreparedStatement interface with bind(), execute(), getResult(), getInsertId(), getAffectedRows() methods
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  
  - [x] 2.2 Implement MySQLDatabase class wrapping existing mysqli operations
    - Wrap existing MySQL operations in DatabaseInterface contract
    - Maintain backward compatibility with current mysqli result sets
    - _Requirements: 1.4, 1.5_
  
  - [x] 2.3 Write property test for database abstraction layer
    - **Property 22: Database Implementation Switching**
    - **Validates: Requirements 1.5, 20.1, 20.2**
  
  - [ ]* 2.4 Write unit tests for DatabaseInterface implementations
    - Test CRUD operations with specific examples
    - Test edge cases (null values, empty strings, boundary conditions)
    - _Requirements: 1.1, 1.2, 1.3_

- [ ] 3. Implement Query Translator component
  - [ ] 3.1 Create QueryTranslator class with SQL pattern translation methods
    - Implement translateWhere() for WHERE clause conversion to Firestore conditions
    - Implement translateJoin() for JOIN clause conversion to multiple queries
    - Implement translateAggregate() for SUM, COUNT, AVG conversion to client-side calculations
    - Create operator mapping (=, !=, <, <=, >, >=, LIKE)
    - Handle date/time functions (NOW(), DATE_SUB(), YEAR(), MONTH())
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8_
  
  - [ ]* 3.2 Write property test for query translation equivalence
    - **Property 9: Query Translation Equivalence**
    - **Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8**
  
  - [ ]* 3.3 Write unit tests for QueryTranslator
    - Test specific SQL patterns used in application
    - Test comparison operators and LIKE patterns
    - Test ORDER BY and LIMIT clauses
    - _Requirements: 12.1, 12.2, 12.3_

- [ ] 4. Implement FirebaseDatabase class
  - [ ] 4.1 Create FirebaseDatabase implementing DatabaseInterface
    - Initialize Firestore client with credentials
    - Implement connection management (connect(), disconnect(), isConnected())
    - Add error handling with logging and fallback to MySQL
    - _Requirements: 1.4, 2.1, 2.3, 2.4, 21.1, 21.2, 21.5_
  
  - [ ] 4.2 Implement CRUD operations (insert, update, delete, findById)
    - Add server timestamp for created_at if not present
    - Handle data type conversions between SQL and NoSQL formats
    - Validate data before write operations
    - _Requirements: 1.1, 1.6_
  
  - [ ] 4.3 Implement query operations (query, queryOne, count)
    - Apply WHERE conditions using QueryTranslator
    - Apply ORDER BY and LIMIT clauses
    - Format results to match mysqli result structure
    - _Requirements: 1.2, 1.3, 12.1, 12.2, 12.3_
  
  - [ ] 4.4 Implement aggregate operations (sum, avg) with client-side calculation
    - Query all matching documents
    - Calculate aggregates in PHP
    - _Requirements: 12.4_
  
  - [ ] 4.5 Implement transaction management using Firebase batch writes
    - Implement beginTransaction() to create batch
    - Implement commit() to execute batch atomically
    - Implement rollback() to discard batch
    - Handle Firebase 500 operation limit per batch
    - _Requirements: 13.1, 13.2, 13.3, 13.4_
  
  - [ ] 4.6 Implement batch operations (batchDelete, batchInsert)
    - Group operations into batches of 500
    - Execute batches sequentially
    - _Requirements: 13.1, 13.2_
  
  - [ ]* 4.7 Write property test for atomic cascade delete
    - **Property 10: Atomic Cascade Delete**
    - **Validates: Requirements 13.1, 13.2, 13.5**
  
  - [ ]* 4.8 Write property test for transaction rollback completeness
    - **Property 11: Transaction Rollback Completeness**
    - **Validates: Requirements 13.3**
  
  - [ ]* 4.9 Write unit tests for FirebaseDatabase operations
    - Test CRUD operations with Firebase emulator
    - Test error handling and fallback logic
    - Test batch operation limits
    - _Requirements: 1.1, 13.1, 21.1_

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Design and implement Firestore schema
  - [ ] 6.1 Create Firestore collections with proper indexes
    - Create users collection with indexes on username, email, remember_token
    - Create transactions collection with composite indexes (user_id + created_at, user_id + type + created_at, user_id + category_id)
    - Create categories collection with composite index (user_id + type + name)
    - Create admins collection with index on username
    - Create otp_verifications collection with composite index (email + expires_at) and single index on expires_at
    - Create pending_users collection with indexes on email and expires_at
    - Create user_feedback collection with indexes on user_id and composite index (display_approved + created_at)
    - Create email_logs collection with composite indexes (email_type + created_at, status + created_at, recipient_email + created_at)
    - Create user_warnings collection with composite index (user_id + created_at)
    - _Requirements: 3.1, 4.1, 5.1, 6.1, 7.1, 7.3, 8.1, 8.5, 9.1, 10.1, 10.5, 11.1_
  
  - [ ] 6.2 Implement Firestore security rules
    - Write security rules for users collection (user can only access own data)
    - Write security rules for transactions collection (user can only access own transactions)
    - Write security rules for categories collection (user can only access own categories)
    - Write security rules for admins collection (only admins can access)
    - Write security rules for otp_verifications collection (public for verification flow)
    - Write security rules for pending_users collection (public for registration flow)
    - Write security rules for user_feedback collection (public read if approved, authenticated write)
    - Write security rules for email_logs collection (admin-only access)
    - Write security rules for user_warnings collection (user can read own, admin can write)
    - _Requirements: 22.1, 22.2, 22.3, 22.4_
  
  - [ ]* 6.3 Write property test for security rule enforcement
    - **Property 24: Security Rule Enforcement - User Data Isolation**
    - **Property 25: Authentication Requirement**
    - **Property 26: Admin Access Restriction**
    - **Validates: Requirements 22.1, 22.2, 22.3, 22.4**
  
  - [ ]* 6.4 Write unit tests for security rules
    - Test user data isolation with Firebase emulator
    - Test authentication requirements
    - Test admin access restrictions
    - _Requirements: 22.1, 22.3, 22.4_

- [ ] 7. Implement Migration Service
  - [ ] 7.1 Create MigrationService class with collection migration methods
    - Initialize with MySQL and Firebase database instances
    - Create migrate() method orchestrating migration in dependency order
    - Implement migrateUsers() transferring all user records with field preservation
    - Implement migrateAdmins() transferring all admin records
    - Implement migrateCategories() transferring all category records
    - Implement migrateTransactions() transferring all transaction records with decimal precision
    - Implement migrateOtpVerifications() transferring active OTP records
    - Implement migratePendingUsers() transferring active pending user records
    - Implement migrateUserFeedback() transferring all feedback records
    - Implement migrateEmailLogs() transferring all email log records
    - Implement migrateUserWarnings() transferring all warning records
    - _Requirements: 3.1, 3.2, 4.1, 4.2, 4.5, 5.1, 5.2, 6.1, 6.2, 7.1, 7.2, 8.1, 8.2, 9.1, 9.2, 10.1, 10.2, 11.1, 11.2_
  
  - [ ] 7.2 Implement data validation during migration
    - Verify record integrity by comparing source and destination
    - Maintain unique constraints (username, email, category name per user/type)
    - Preserve referential integrity (transaction→user, transaction→category, etc.)
    - Validate enumeration values (type, status, email_type)
    - Preserve timestamp ordering
    - _Requirements: 3.3, 3.6, 4.3, 4.4, 4.6, 5.3, 5.4, 5.5, 7.4, 8.3, 8.4, 9.3, 9.4, 10.3, 10.4, 11.3_
  
  - [ ]* 7.3 Write property test for migration data completeness
    - **Property 1: Migration Data Completeness**
    - **Validates: Requirements 3.1, 4.1, 5.1, 6.1, 7.1, 8.1, 9.1, 10.1, 11.1**
  
  - [ ]* 7.4 Write property test for migration field preservation
    - **Property 2: Migration Field Preservation (Round Trip)**
    - **Validates: Requirements 3.2, 3.4, 3.5, 3.6, 4.2, 5.2, 6.2, 6.4, 7.2, 8.2, 9.2, 10.2, 11.2**
  
  - [ ]* 7.5 Write property test for decimal precision preservation
    - **Property 3: Decimal Precision Preservation**
    - **Validates: Requirements 4.5**
  
  - [ ]* 7.6 Write property test for timestamp ordering preservation
    - **Property 4: Timestamp Ordering Preservation**
    - **Validates: Requirements 4.6**
  
  - [ ]* 7.7 Write property test for referential integrity maintenance
    - **Property 5: Referential Integrity Maintenance**
    - **Validates: Requirements 4.3, 4.4, 5.3, 9.3, 11.3, 19.5**
  
  - [ ]* 7.8 Write property test for unique constraint enforcement
    - **Property 6: Unique Constraint Enforcement**
    - **Validates: Requirements 3.3, 5.5, 6.3, 8.3**
  
  - [ ]* 7.9 Write property test for enumeration value preservation
    - **Property 7: Enumeration Value Preservation**
    - **Validates: Requirements 5.4, 10.3, 10.4**
  
  - [ ]* 7.10 Write property test for expiration logic preservation
    - **Property 8: Expiration Logic Preservation**
    - **Validates: Requirements 7.4, 8.4**
  
  - [ ]* 7.11 Write unit tests for MigrationService
    - Test migration of each collection with sample data
    - Test error handling for migration failures
    - _Requirements: 3.1, 4.1, 5.1_

- [ ] 8. Implement Data Validator component
  - [ ] 8.1 Create DataValidator class for post-migration validation
    - Implement validate() method generating validation report
    - Compare record counts between MySQL and Firebase for all collections
    - Perform checksum validation on critical fields (passwords, transaction amounts)
    - Identify and report missing or mismatched records
    - Verify referential integrity by checking foreign key relationships
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5_
  
  - [ ]* 8.2 Write property test for migration record count equality
    - **Property 20: Migration Record Count Equality**
    - **Validates: Requirements 19.2**
  
  - [ ]* 8.3 Write property test for critical field checksum validation
    - **Property 21: Critical Field Checksum Validation**
    - **Validates: Requirements 19.3**
  
  - [ ]* 8.4 Write unit tests for DataValidator
    - Test validation report generation
    - Test detection of missing records
    - Test checksum calculation
    - _Requirements: 19.1, 19.2, 19.3_

- [ ] 9. Update Session Manager for Firebase
  - [ ] 9.1 Modify SessionManager to use DatabaseInterface
    - Update createSession() to store remember_token in Firebase via DatabaseInterface
    - Update validateSession() to query remember_token from Firebase
    - Update token expiry validation using Firebase timestamp queries
    - Maintain session timeout behavior identical to MySQL implementation
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_
  
  - [ ]* 9.2 Write property test for session token round trip
    - **Property 12: Session Token Round Trip**
    - **Validates: Requirements 14.1**
  
  - [ ]* 9.3 Write property test for token expiry validation
    - **Property 13: Token Expiry Validation**
    - **Validates: Requirements 14.2**
  
  - [ ]* 9.4 Write property test for session behavior equivalence
    - **Property 14: Session Behavior Equivalence**
    - **Validates: Requirements 14.4, 14.5**
  
  - [ ]* 9.5 Write unit tests for SessionManager
    - Test session creation with remember me
    - Test session validation with expired tokens
    - Test session timeout behavior
    - _Requirements: 14.1, 14.2, 14.4_

- [ ] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Implement file storage migration to Firebase Storage
  - [ ] 11.1 Create FileStorageService for Firebase Storage operations
    - Initialize Firebase Storage client
    - Implement uploadFile() method storing files in Firebase Storage
    - Implement getFileUrl() method returning public URLs
    - Implement deleteFile() method removing files from storage
    - Set up file access permissions (user-specific access control)
    - _Requirements: 15.1, 15.2, 15.4_
  
  - [ ] 11.2 Update file upload handler to use Firebase Storage
    - Modify profile picture upload to store in Firebase Storage
    - Save Firebase Storage URL in Firestore user document
    - Validate image formats (JPEG, PNG, GIF)
    - Enforce maximum file size limits
    - _Requirements: 15.2, 15.3, 15.5, 15.6_
  
  - [ ] 11.3 Migrate existing profile pictures to Firebase Storage
    - Read existing profile pictures from local filesystem
    - Upload to Firebase Storage
    - Update user records with new Firebase Storage URLs
    - _Requirements: 15.3_
  
  - [ ]* 11.4 Write property test for file storage round trip
    - **Property 15: File Storage Round Trip**
    - **Validates: Requirements 15.2, 15.3**
  
  - [ ]* 11.5 Write property test for file access control
    - **Property 16: File Access Control**
    - **Validates: Requirements 15.4**
  
  - [ ]* 11.6 Write property test for file format support
    - **Property 17: File Format Support**
    - **Validates: Requirements 15.5**
  
  - [ ]* 11.7 Write property test for file size limit enforcement
    - **Property 18: File Size Limit Enforcement**
    - **Validates: Requirements 15.6**
  
  - [ ]* 11.8 Write unit tests for FileStorageService
    - Test file upload with various formats
    - Test file size limit enforcement
    - Test access control rules
    - _Requirements: 15.2, 15.5, 15.6_

- [ ] 12. Update cron jobs for Firebase cleanup operations
  - [ ] 12.1 Modify cleanup cron job to use DatabaseInterface
    - Update expired OTP deletion to query Firebase where expires_at < current_time
    - Update expired pending_users deletion to query Firebase where expires_at < current_time
    - Update verified OTP deletion (older than 24 hours) to query Firebase
    - Log cleanup operation results with record counts
    - Maintain same schedule as MySQL implementation
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_
  
  - [ ]* 12.2 Write property test for expired record cleanup
    - **Property 19: Expired Record Cleanup**
    - **Validates: Requirements 16.1, 16.2, 16.3**
  
  - [ ]* 12.3 Write unit tests for cleanup cron job
    - Test deletion of expired OTP records
    - Test deletion of expired pending users
    - Test logging of cleanup results
    - _Requirements: 16.1, 16.2, 16.4_

- [ ] 13. Implement error handling and logging
  - [ ] 13.1 Create structured logging system with JSON format
    - Define log format with timestamp, level, component, operation, collection, user_id, error details, execution_time_ms, stack_trace
    - Implement log levels (ERROR, WARNING, INFO, DEBUG)
    - Log Firebase operation failures with error codes and messages
    - Log slow queries exceeding 1 second
    - Log connection failures and reconnection attempts
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5_
  
  - [ ] 13.2 Implement error handling patterns
    - Add graceful degradation with fallback to MySQL
    - Implement retry with exponential backoff for transient errors
    - Add validation before write operations
    - Handle connection errors, data validation errors, permission errors, quota errors, transaction errors
    - _Requirements: 2.4, 21.1, 21.5_
  
  - [ ]* 13.3 Write unit tests for error handling
    - Test fallback to MySQL on Firebase failure
    - Test retry logic with exponential backoff
    - Test validation error handling
    - _Requirements: 2.4, 21.1_

- [ ] 14. Implement monitoring and cost tracking
  - [ ] 14.1 Create UsageMonitor for Firebase operation tracking
    - Log daily Firebase read, write, delete operation counts
    - Implement alert system for approaching 80% of free tier limits
    - Create usage dashboard showing current month's consumption
    - Estimate monthly costs based on usage patterns
    - Recommend optimization strategies when usage is high
    - _Requirements: 24.1, 24.2, 24.3, 24.4, 24.5_
  
  - [ ]* 14.2 Write unit tests for UsageMonitor
    - Test operation counting
    - Test alert thresholds
    - Test cost estimation
    - _Requirements: 24.1, 24.2, 24.4_

- [ ] 15. Implement backup and recovery system
  - [ ] 15.1 Create BackupService for automated backups
    - Implement daily export of Firestore data to JSON format
    - Store backup files in Firebase Storage with timestamp-based naming (YYYY-MM-DD-HH-MM-SS)
    - Implement 30-day retention policy with automatic deletion
    - Verify backup integrity by validating JSON structure and record counts
    - _Requirements: 23.1, 23.2, 23.3, 23.5_
  
  - [ ] 15.2 Create restoration utility for backup imports
    - Implement import of backup JSON into Firestore
    - Validate restored data matches backup
    - _Requirements: 23.4_
  
  - [ ]* 15.3 Write property test for backup file naming convention
    - **Property 27: Backup File Naming Convention**
    - **Validates: Requirements 23.2**
  
  - [ ]* 15.4 Write property test for backup retention policy
    - **Property 28: Backup Retention Policy**
    - **Validates: Requirements 23.3**
  
  - [ ]* 15.5 Write property test for backup restoration round trip
    - **Property 29: Backup Restoration Round Trip**
    - **Validates: Requirements 23.4**
  
  - [ ]* 15.6 Write property test for backup integrity validation
    - **Property 30: Backup Integrity Validation**
    - **Validates: Requirements 23.5**
  
  - [ ]* 15.7 Write unit tests for BackupService
    - Test backup file creation
    - Test backup file naming
    - Test restoration from backup
    - _Requirements: 23.1, 23.2, 23.4_

- [ ] 16. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 17. Implement rollback capability
  - [ ] 17.1 Add configuration flag for database implementation switching
    - Create config option to switch between MySQL and Firebase
    - Implement runtime switching without code changes
    - _Requirements: 20.1, 20.2_
  
  - [ ] 17.2 Create synchronization utility for Firebase to MySQL
    - Implement utility to copy Firebase changes back to MySQL
    - Maintain field mapping and data type conversion
    - _Requirements: 20.4_
  
  - [ ] 17.3 Implement operation logging for rollback verification
    - Log all database operations with timestamps and parameters
    - _Requirements: 20.5_
  
  - [ ]* 17.4 Write property test for rollback synchronization
    - **Property 23: Rollback Synchronization**
    - **Validates: Requirements 20.4**
  
  - [ ]* 17.5 Write unit tests for rollback capability
    - Test switching between MySQL and Firebase
    - Test synchronization utility
    - _Requirements: 20.1, 20.2, 20.4_

- [ ] 18. Update API endpoints for frontend compatibility
  - [ ] 18.1 Verify all API endpoints use DatabaseInterface
    - Audit all PHP endpoints to ensure they use DatabaseInterface instead of direct mysqli calls
    - Update any remaining direct database calls
    - _Requirements: 1.5, 25.1_
  
  - [ ] 18.2 Validate API response format preservation
    - Ensure response structures match MySQL implementation (success flags, data objects, error messages)
    - Preserve JSON field names and data types
    - Maintain identical HTTP status codes
    - _Requirements: 25.2, 25.3, 25.4, 25.5_
  
  - [ ]* 18.3 Write property test for API response format preservation
    - **Property 31: API Response Format Preservation**
    - **Validates: Requirements 25.1, 25.2, 25.3, 25.5**
  
  - [ ]* 18.4 Write property test for HTTP status code preservation
    - **Property 32: HTTP Status Code Preservation**
    - **Validates: Requirements 25.4**
  
  - [ ]* 18.5 Write property test for data type conversion transparency
    - **Property 33: Data Type Conversion Transparency**
    - **Validates: Requirements 1.6**
  
  - [ ]* 18.6 Write integration tests for all API endpoints
    - Test user registration flow with OTP verification
    - Test login and session management
    - Test transaction CRUD operations
    - Test category management
    - Test admin panel operations
    - Test feedback submission
    - _Requirements: 25.1, 25.2, 25.6, 26.2, 26.3_

- [ ] 19. Performance testing and optimization
  - [ ]* 19.1 Write performance tests for user dashboard
    - Test total balance calculation within 1 second
    - Test monthly income/expense calculation within 1 second
    - Test transaction history retrieval within 1 second
    - _Requirements: 18.1, 18.2, 18.3, 18.4_
  
  - [ ]* 19.2 Write performance tests for admin panel
    - Test admin dashboard summary statistics within 2 seconds
    - Test paginated user list retrieval within 2 seconds
    - Test paginated transaction history within 2 seconds
    - Test paginated email history within 2 seconds
    - _Requirements: 17.1, 17.2, 17.3, 17.4_
  
  - [ ]* 19.3 Write performance comparison tests
    - Compare MySQL vs Firebase response times for all critical queries
    - Ensure Firebase meets or exceeds MySQL performance
    - _Requirements: 17.5, 18.4, 26.4_
  
  - [ ] 19.4 Implement query optimization strategies
    - Implement Firebase local persistence for caching
    - Optimize composite indexes based on performance test results
    - Implement pagination for large result sets
    - _Requirements: 17.6, 18.5_

- [ ] 20. Create comprehensive documentation
  - [ ] 20.1 Document DatabaseInterface API
    - Document all interface methods with signatures, parameters, return types, and examples
    - _Requirements: 27.1_
  
  - [ ] 20.2 Document Firestore schema design
    - Document all collections with field definitions, data types, indexes, and security rules
    - _Requirements: 27.2, 27.3_
  
  - [ ] 20.3 Create migration runbook
    - Provide step-by-step migration instructions
    - Document pre-migration checklist
    - Document validation procedures
    - _Requirements: 27.4_
  
  - [ ] 20.4 Document rollback procedures
    - Provide step-by-step rollback instructions
    - Document synchronization utility usage
    - _Requirements: 27.5_
  
  - [ ] 20.5 Create troubleshooting guide
    - Document common Firebase errors and solutions
    - Document debugging procedures
    - _Requirements: 27.6_
  
  - [ ] 20.6 Document Firebase free tier limits and optimization
    - Document free tier quotas
    - Provide optimization strategies
    - _Requirements: 27.7_

- [ ] 21. Final checkpoint - Run full test suite and validation
  - Run all unit tests, property tests, integration tests, and performance tests
  - Execute migration on test dataset and validate with DataValidator
  - Verify all 27 requirements are met
  - Verify all 33 correctness properties pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end workflows
- Performance tests ensure response time requirements are met
- All database operations go through DatabaseInterface for abstraction
- Firebase emulator should be used for testing to avoid consuming production quota
- Migration should be tested on a copy of production data before live migration
- Rollback capability should be tested before decommissioning MySQL
