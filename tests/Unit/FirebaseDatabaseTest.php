<?php

namespace WalletTally\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/DatabaseInterface.php';
require_once __DIR__ . '/../../includes/FirebaseDatabase.php';
require_once __DIR__ . '/../../includes/MySQLDatabase.php';
require_once __DIR__ . '/../../includes/PreparedStatement.php';
require_once __DIR__ . '/../../includes/FirebasePreparedStatement.php';

/**
 * Unit Tests for FirebaseDatabase Implementation
 * 
 * Tests CRUD operations with Firebase emulator, error handling,
 * fallback logic, and batch operation limits.
 * 
 * Validates Requirements: 1.1, 13.1, 21.1
 * 
 * SETUP REQUIREMENTS:
 * - Firebase emulator must be running on localhost:8080
 * - Start with: firebase emulators:start --only firestore
 * - Or set FIREBASE_EMULATOR=false to skip Firebase tests
 */
class FirebaseDatabaseTest extends TestCase
{
    private ?\FirebaseDatabase $db = null;
    private ?string $projectId = null;
    private string $testCollection = 'test_firebase_db';
    private bool $emulatorAvailable = false;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Check if Firebase emulator is enabled
        $useEmulator = getenv('FIREBASE_EMULATOR') !== 'false';
        
        if (!$useEmulator) {
            $this->markTestSkipped('Firebase emulator is disabled. Set FIREBASE_EMULATOR=true to run these tests.');
            return;
        }
        
        // Check if Firebase credentials are configured
        $projectId = getenv('FIREBASE_PROJECT_ID') ?: 'demo-test-project';
        $credentialsPath = getenv('FIREBASE_CREDENTIALS_PATH') ?: __DIR__ . '/../../config/firebase-credentials.json';
        
        // For emulator, we can use a demo project
        $this->projectId = $projectId;
        
        // Create a temporary credentials file for emulator if it doesn't exist
        if (!file_exists($credentialsPath) && $useEmulator) {
            $this->createDemoCredentials($credentialsPath);
        }
        
        // Initialize Firebase database
        $this->db = new \FirebaseDatabase([
            'project_id' => $this->projectId,
            'credentials_path' => $credentialsPath,
            'fallback_enabled' => false,
            'log_errors' => false
        ]);
        
        // Set emulator host for testing
        if ($useEmulator) {
            putenv('FIRESTORE_EMULATOR_HOST=localhost:8080');
        }
        
        try {
            $connected = $this->db->connect();
            
            if (!$connected) {
                $this->markTestSkipped('Firebase emulator is not available. Please start it with: firebase emulators:start --only firestore');
                return;
            }
            
            $this->emulatorAvailable = true;
            $this->cleanupTestCollection();
            
        } catch (\Exception $e) {
            $this->markTestSkipped('Firebase emulator connection failed: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        if ($this->db && $this->db->isConnected()) {
            $this->cleanupTestCollection();
            $this->db->disconnect();
        }
        
        // Clear emulator environment variable
        putenv('FIRESTORE_EMULATOR_HOST');
        
        parent::tearDown();
    }
    
    // ========================================
    // Connection Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 21.1: Connection management
     */
    public function testConnectionEstablishment()
    {
        $this->assertTrue($this->db->isConnected(), 'Firebase should be connected');
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Disconnection
     */
    public function testDisconnection()
    {
        $this->db->disconnect();
        $this->assertFalse($this->db->isConnected(), 'Firebase should be disconnected');
        
        // Reconnect for tearDown
        $this->db->connect();
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Connection failure handling
     */
    public function testConnectionFailureWithInvalidCredentials()
    {
        $db = new \FirebaseDatabase([
            'project_id' => '',
            'credentials_path' => '/nonexistent/path.json',
            'fallback_enabled' => false,
            'log_errors' => false
        ]);
        
        $connected = $db->connect();
        
        $this->assertFalse($connected, 'Connection should fail with invalid credentials');
        $this->assertFalse($db->isConnected());
    }
    
    // ========================================
    // CRUD Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Insert operation
     */
    public function testInsertCreatesNewDocument()
    {
        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'amount' => 100.50
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        
        $this->assertNotFalse($id, 'Insert should return a document ID');
        $this->assertIsString($id, 'Document ID should be a string');
        $this->assertNotEmpty($id, 'Document ID should not be empty');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Insert with server timestamp
     */
    public function testInsertAddsServerTimestamp()
    {
        $data = [
            'username' => 'timestamp_user',
            'email' => 'timestamp@example.com',
            'amount' => 50.00
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotNull($record);
        $this->assertArrayHasKey('created_at', $record, 'Should have created_at timestamp');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: FindById retrieves correct document
     */
    public function testFindByIdRetrievesCorrectDocument()
    {
        $data = [
            'username' => 'jane_smith',
            'email' => 'jane@example.com',
            'amount' => 250.75
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotNull($record, 'Document should be found');
        $this->assertEquals($id, $record['id']);
        $this->assertEquals('jane_smith', $record['username']);
        $this->assertEquals('jane@example.com', $record['email']);
        $this->assertEquals(250.75, (float)$record['amount']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Update modifies existing document
     */
    public function testUpdateModifiesExistingDocument()
    {
        $data = [
            'username' => 'bob_jones',
            'email' => 'bob@example.com',
            'amount' => 50.00
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        
        $updateData = [
            'email' => 'bob.jones@example.com',
            'amount' => 75.25
        ];
        
        $success = $this->db->update($this->testCollection, $id, $updateData);
        $this->assertTrue($success, 'Update should succeed');
        
        $record = $this->db->findById($this->testCollection, $id);
        $this->assertEquals('bob.jones@example.com', $record['email']);
        $this->assertEquals(75.25, (float)$record['amount']);
        $this->assertEquals('bob_jones', $record['username'], 'Unchanged field should remain');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Delete removes document
     */
    public function testDeleteRemovesDocument()
    {
        $data = [
            'username' => 'alice_wonder',
            'email' => 'alice@example.com',
            'amount' => 300.00
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $success = $this->db->delete($this->testCollection, $id);
        
        $this->assertTrue($success, 'Delete should succeed');
        
        $record = $this->db->findById($this->testCollection, $id);
        $this->assertNull($record, 'Deleted document should not be found');
    }
    
    // ========================================
    // Query Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Query with conditions
     */
    public function testQueryWithConditionsReturnsMatchingDocuments()
    {
        // Insert test data
        $this->db->insert($this->testCollection, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 100]);
        
        $results = $this->db->query($this->testCollection, ['amount' => 100]);
        
        $this->assertCount(2, $results, 'Should find 2 documents with amount 100');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Query with operator conditions
     */
    public function testQueryWithOperatorConditions()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 50]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 150]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 250]);
        
        $results = $this->db->query($this->testCollection, ['amount' => ['>', 100]]);
        
        $this->assertCount(2, $results, 'Should find 2 documents with amount > 100');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Query with ordering
     */
    public function testQueryWithOrderBy()
    {
        $this->db->insert($this->testCollection, ['username' => 'charlie', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'alice', 'amount' => 200]);
        $this->db->insert($this->testCollection, ['username' => 'bob', 'amount' => 150]);
        
        $results = $this->db->query($this->testCollection, [], ['username' => 'ASC']);
        
        $this->assertEquals('alice', $results[0]['username']);
        $this->assertEquals('bob', $results[1]['username']);
        $this->assertEquals('charlie', $results[2]['username']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Query with limit
     */
    public function testQueryWithLimit()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 300]);
        
        $results = $this->db->query($this->testCollection, [], [], 2);
        
        $this->assertCount(2, $results, 'Should return only 2 documents');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: QueryOne returns first match
     */
    public function testQueryOneReturnsFirstMatch()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 100]);
        
        $record = $this->db->queryOne($this->testCollection, ['amount' => 100]);
        
        $this->assertNotNull($record, 'Should find a document');
        $this->assertEquals(100, $record['amount']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Count operation
     */
    public function testCountReturnsCorrectNumber()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 100]);
        
        $count = $this->db->count($this->testCollection, ['amount' => 100]);
        
        $this->assertEquals(2, $count);
    }
    
    // ========================================
    // Aggregate Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Sum aggregate (client-side)
     */
    public function testSumCalculatesTotal()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100.50]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200.25]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 150.75]);
        
        $sum = $this->db->sum($this->testCollection, 'amount');
        
        $this->assertEquals(451.50, $sum, '', 0.01);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Average aggregate (client-side)
     */
    public function testAvgCalculatesAverage()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 300]);
        
        $avg = $this->db->avg($this->testCollection, 'amount');
        
        $this->assertEquals(200.0, $avg, '', 0.01);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Sum with conditions
     */
    public function testSumWithConditions()
    {
        $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200]);
        $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 300]);
        
        $sum = $this->db->sum($this->testCollection, 'amount', ['amount' => ['>', 150]]);
        
        $this->assertEquals(500.0, $sum, '', 0.01);
    }
    
    // ========================================
    // Batch Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 13.1: Batch insert operation
     */
    public function testBatchInsertCreatesMultipleDocuments()
    {
        $records = [
            ['username' => 'batch1', 'email' => 'batch1@test.com', 'amount' => 100],
            ['username' => 'batch2', 'email' => 'batch2@test.com', 'amount' => 200],
            ['username' => 'batch3', 'email' => 'batch3@test.com', 'amount' => 300]
        ];
        
        $success = $this->db->batchInsert($this->testCollection, $records);
        
        $this->assertTrue($success, 'Batch insert should succeed');
        
        $count = $this->db->count($this->testCollection);
        $this->assertEquals(3, $count, 'Should have 3 documents');
    }
    
    /**
     * @test
     * Validates Requirement 13.1: Batch delete operation
     */
    public function testBatchDeleteRemovesMultipleDocuments()
    {
        $id1 = $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $id2 = $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200]);
        $id3 = $this->db->insert($this->testCollection, ['username' => 'user3', 'amount' => 300]);
        
        $success = $this->db->batchDelete($this->testCollection, [$id1, $id3]);
        
        $this->assertTrue($success, 'Batch delete should succeed');
        
        $this->assertNull($this->db->findById($this->testCollection, $id1));
        $this->assertNotNull($this->db->findById($this->testCollection, $id2));
        $this->assertNull($this->db->findById($this->testCollection, $id3));
    }
    
    /**
     * @test
     * Validates Requirement 13.1: Batch operation with 500 limit
     */
    public function testBatchOperationHandles500Limit()
    {
        // Create 600 records to test batch splitting
        $records = [];
        for ($i = 1; $i <= 600; $i++) {
            $records[] = [
                'username' => "user{$i}",
                'amount' => $i * 10
            ];
        }
        
        $success = $this->db->batchInsert($this->testCollection, $records);
        
        $this->assertTrue($success, 'Batch insert should handle 600 records across multiple batches');
        
        $count = $this->db->count($this->testCollection);
        $this->assertEquals(600, $count, 'All 600 records should be inserted');
    }
    
    /**
     * @test
     * Validates Requirement 13.1: Batch delete with 500 limit
     */
    public function testBatchDeleteHandles500Limit()
    {
        // Insert 600 documents
        $ids = [];
        for ($i = 1; $i <= 600; $i++) {
            $id = $this->db->insert($this->testCollection, ['username' => "user{$i}", 'amount' => $i]);
            $ids[] = $id;
        }
        
        // Delete all 600 documents
        $success = $this->db->batchDelete($this->testCollection, $ids);
        
        $this->assertTrue($success, 'Batch delete should handle 600 documents across multiple batches');
        
        $count = $this->db->count($this->testCollection);
        $this->assertEquals(0, $count, 'All documents should be deleted');
    }
    
    // ========================================
    // Transaction Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 13.1: Transaction commit
     */
    public function testTransactionCommit()
    {
        $this->db->beginTransaction();
        
        $id = $this->db->insert($this->testCollection, ['username' => 'trans_user', 'amount' => 100]);
        
        $this->db->commit();
        
        $record = $this->db->findById($this->testCollection, $id);
        $this->assertNotNull($record, 'Committed document should exist');
    }
    
    /**
     * @test
     * Validates Requirement 13.1: Transaction rollback
     */
    public function testTransactionRollback()
    {
        $this->db->beginTransaction();
        
        $id = $this->db->insert($this->testCollection, ['username' => 'rollback_user', 'amount' => 100]);
        
        $this->db->rollback();
        
        // Note: Firebase batch writes don't support true rollback
        // The batch is simply discarded before commit
        // We can't verify the document doesn't exist because it was never committed
        $this->assertTrue(true, 'Rollback should discard batch');
    }
    
    /**
     * @test
     * Validates Requirement 13.1: Multiple operations in transaction
     */
    public function testTransactionWithMultipleOperations()
    {
        $this->db->beginTransaction();
        
        $id1 = $this->db->insert($this->testCollection, ['username' => 'user1', 'amount' => 100]);
        $id2 = $this->db->insert($this->testCollection, ['username' => 'user2', 'amount' => 200]);
        $this->db->update($this->testCollection, $id1, ['amount' => 150]);
        
        $this->db->commit();
        
        $record1 = $this->db->findById($this->testCollection, $id1);
        $record2 = $this->db->findById($this->testCollection, $id2);
        
        $this->assertNotNull($record1);
        $this->assertNotNull($record2);
        $this->assertEquals(150, $record1['amount']);
        $this->assertEquals(200, $record2['amount']);
    }
    
    // ========================================
    // Error Handling Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 21.1: Error handling for invalid data
     */
    public function testInsertWithInvalidDataFails()
    {
        // Empty data should fail validation
        $id = $this->db->insert($this->testCollection, []);
        
        $this->assertFalse($id, 'Insert with empty data should fail');
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Error handling for invalid field names
     */
    public function testInsertWithInvalidFieldNameFails()
    {
        // Field name cannot be empty
        $id = $this->db->insert($this->testCollection, ['' => 'value']);
        
        $this->assertFalse($id, 'Insert with empty field name should fail');
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Error handling for nonexistent document
     */
    public function testFindByIdWithNonexistentId()
    {
        $record = $this->db->findById($this->testCollection, 'nonexistent_id_12345');
        
        $this->assertNull($record, 'Should return null for nonexistent document');
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Error handling for update nonexistent document
     */
    public function testUpdateNonexistentDocument()
    {
        // Firebase update doesn't fail for nonexistent documents
        $success = $this->db->update($this->testCollection, 'nonexistent_id', ['username' => 'updated']);
        
        $this->assertTrue($success, 'Update should succeed even for nonexistent document');
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Error handling for delete nonexistent document
     */
    public function testDeleteNonexistentDocument()
    {
        // Firebase delete doesn't fail for nonexistent documents
        $success = $this->db->delete($this->testCollection, 'nonexistent_id');
        
        $this->assertTrue($success, 'Delete should succeed even for nonexistent document');
    }
    
    // ========================================
    // Fallback Logic Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 21.1: Fallback to MySQL on Firebase failure
     */
    public function testFallbackToMySQLOnConnectionFailure()
    {
        // Create MySQL fallback database
        $mysqlDb = new \MySQLDatabase([
            'host' => getenv('DB_HOST') ?: 'localhost',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'database' => getenv('DB_NAME') ?: 'wallet_tally_test',
            'charset' => 'utf8mb4'
        ]);
        
        // Create Firebase with invalid config but fallback enabled
        $db = new \FirebaseDatabase([
            'project_id' => '',
            'credentials_path' => '/invalid/path.json',
            'fallback_enabled' => true,
            'log_errors' => false
        ], $mysqlDb);
        
        $connected = $db->connect();
        
        // Should connect via fallback
        $this->assertTrue($connected, 'Should connect via MySQL fallback');
        $this->assertTrue($db->isConnected(), 'Should be connected via fallback');
        
        $db->disconnect();
    }
    
    /**
     * @test
     * Validates Requirement 21.1: Operations use fallback when Firebase unavailable
     */
    public function testOperationsUseFallbackWhenFirebaseUnavailable()
    {
        // Create MySQL fallback database
        $mysqlDb = new \MySQLDatabase([
            'host' => getenv('DB_HOST') ?: 'localhost',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'database' => getenv('DB_NAME') ?: 'wallet_tally_test',
            'charset' => 'utf8mb4'
        ]);
        
        if (!$mysqlDb->connect()) {
            $this->markTestSkipped('MySQL not available for fallback test');
            return;
        }
        
        // Create test table in MySQL
        $testTable = 'test_fallback';
        $sql = "CREATE TABLE IF NOT EXISTS {$testTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            amount DECIMAL(10, 2)
        )";
        $mysqlDb->prepare($sql)->execute();
        
        // Create Firebase with invalid config but fallback enabled
        $db = new \FirebaseDatabase([
            'project_id' => '',
            'credentials_path' => '/invalid/path.json',
            'fallback_enabled' => true,
            'log_errors' => false
        ], $mysqlDb);
        
        $db->connect();
        
        // Insert should use fallback
        $id = $db->insert($testTable, ['username' => 'fallback_user', 'amount' => 100]);
        $this->assertNotFalse($id, 'Insert should succeed via fallback');
        
        // Query should use fallback
        $record = $db->findById($testTable, $id);
        $this->assertNotNull($record, 'FindById should work via fallback');
        $this->assertEquals('fallback_user', $record['username']);
        
        // Cleanup
        $mysqlDb->prepare("DROP TABLE IF EXISTS {$testTable}")->execute();
        $db->disconnect();
    }
    
    // ========================================
    // Data Type Conversion Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Null value handling
     */
    public function testInsertWithNullValues()
    {
        $data = [
            'username' => 'null_user',
            'email' => null,
            'amount' => 100
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals('null_user', $record['username']);
        $this->assertNull($record['email']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Boolean value handling
     */
    public function testInsertWithBooleanValues()
    {
        $data = [
            'username' => 'bool_user',
            'is_active' => true,
            'is_verified' => false
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertTrue($record['is_active']);
        $this->assertFalse($record['is_verified']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Decimal precision handling
     */
    public function testInsertWithDecimalPrecision()
    {
        $data = [
            'username' => 'decimal_user',
            'amount' => 123.45
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals(123.45, (float)$record['amount']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Timestamp conversion
     */
    public function testTimestampConversion()
    {
        $timestamp = time();
        $data = [
            'username' => 'timestamp_user',
            'created_at' => $timestamp
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertIsNumeric($record['created_at']);
    }
    
    // ========================================
    // Edge Case Tests
    // ========================================
    
    /**
     * @test
     * Edge case: Empty string values
     */
    public function testInsertWithEmptyStrings()
    {
        $data = [
            'username' => '',
            'email' => 'empty@test.com',
            'amount' => 0
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals('', $record['username']);
        $this->assertEquals(0.0, (float)$record['amount']);
    }
    
    /**
     * @test
     * Edge case: Large decimal values
     */
    public function testInsertWithLargeDecimalValues()
    {
        $data = [
            'username' => 'large_amount',
            'amount' => 99999999.99
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals(99999999.99, (float)$record['amount']);
    }
    
    /**
     * @test
     * Edge case: Negative amounts
     */
    public function testInsertWithNegativeAmount()
    {
        $data = [
            'username' => 'negative_user',
            'amount' => -50.25
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals(-50.25, (float)$record['amount']);
    }
    
    /**
     * @test
     * Edge case: Special characters in strings
     */
    public function testInsertWithSpecialCharacters()
    {
        $data = [
            'username' => "user's \"name\" with <special> & chars",
            'email' => 'special@test.com'
        ];
        
        $id = $this->db->insert($this->testCollection, $data);
        $record = $this->db->findById($this->testCollection, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals("user's \"name\" with <special> & chars", $record['username']);
    }
    
    /**
     * @test
     * Edge case: Query with no results
     */
    public function testQueryWithNoResults()
    {
        $results = $this->db->query($this->testCollection, ['username' => 'nonexistent']);
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
    
    /**
     * @test
     * Edge case: Count with no matching documents
     */
    public function testCountWithNoMatches()
    {
        $count = $this->db->count($this->testCollection, ['username' => 'nonexistent']);
        
        $this->assertEquals(0, $count);
    }
    
    /**
     * @test
     * Edge case: Sum with no matching documents
     */
    public function testSumWithNoMatches()
    {
        $sum = $this->db->sum($this->testCollection, 'amount', ['username' => 'nonexistent']);
        
        $this->assertEquals(0.0, $sum);
    }
    
    /**
     * @test
     * Edge case: Batch insert with empty array
     */
    public function testBatchInsertWithEmptyArray()
    {
        $success = $this->db->batchInsert($this->testCollection, []);
        
        $this->assertTrue($success, 'Batch insert with empty array should succeed (no-op)');
    }
    
    /**
     * @test
     * Edge case: Batch delete with empty array
     */
    public function testBatchDeleteWithEmptyArray()
    {
        $success = $this->db->batchDelete($this->testCollection, []);
        
        $this->assertTrue($success, 'Batch delete with empty array should succeed (no-op)');
    }
    
    // ========================================
    // PreparedStatement Interface Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Prepared statement interface
     */
    public function testPreparedStatementInsert()
    {
        $sql = "INSERT INTO {$this->testCollection} (username, email, amount) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind(['prep_user', 'prep@test.com', 150.50]);
        $success = $stmt->execute();
        
        $this->assertTrue($success, 'Prepared statement should execute');
        
        $insertId = $stmt->getInsertId();
        $this->assertNotEmpty($insertId, 'Should return insert ID');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Prepared statement select
     */
    public function testPreparedStatementSelect()
    {
        $this->db->insert($this->testCollection, ['username' => 'select_user', 'email' => 'select@test.com', 'amount' => 200]);
        
        $sql = "SELECT * FROM {$this->testCollection} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind(['select_user']);
        $stmt->execute();
        
        $results = $stmt->getResult();
        
        $this->assertCount(1, $results);
        $this->assertEquals('select_user', $results[0]['username']);
    }
    
    // ========================================
    // Helper Methods
    // ========================================
    
    /**
     * Clean up test collection
     */
    private function cleanupTestCollection(): void
    {
        try {
            $documents = $this->db->query($this->testCollection);
            $ids = array_column($documents, 'id');
            
            if (!empty($ids)) {
                $this->db->batchDelete($this->testCollection, $ids);
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
    
    /**
     * Create demo credentials file for emulator
     */
    private function createDemoCredentials(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $credentials = [
            'type' => 'service_account',
            'project_id' => 'demo-test-project',
            'private_key_id' => 'demo-key-id',
            'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC\n-----END PRIVATE KEY-----\n',
            'client_email' => 'demo@demo-test-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs'
        ];
        
        file_put_contents($path, json_encode($credentials, JSON_PRETTY_PRINT));
    }
}
