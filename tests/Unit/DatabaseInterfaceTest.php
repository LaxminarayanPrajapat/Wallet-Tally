<?php

namespace WalletTally\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/DatabaseInterface.php';
require_once __DIR__ . '/../../includes/MySQLDatabase.php';
require_once __DIR__ . '/../../includes/PreparedStatement.php';
require_once __DIR__ . '/../../includes/MySQLPreparedStatement.php';

/**
 * Unit Tests for DatabaseInterface Implementations
 * 
 * Tests CRUD operations, edge cases, and PreparedStatement interface
 * for MySQLDatabase implementation.
 * 
 * Validates Requirements: 1.1, 1.2, 1.3
 */
class DatabaseInterfaceTest extends TestCase
{
    private \DatabaseInterface $db;
    private string $testTable = 'test_db_interface';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize MySQL database
        $this->db = new \MySQLDatabase([
            'host' => getenv('DB_HOST') ?: 'localhost',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'database' => getenv('DB_NAME') ?: 'wallet_tally_test',
            'charset' => 'utf8mb4'
        ]);
        
        $connected = $this->db->connect();
        
        if (!$connected) {
            $this->markTestSkipped('MySQL database is not available. Please start MySQL server and create the test database.');
        }
        
        $this->createTestTable();
    }
    
    protected function tearDown(): void
    {
        if ($this->db && $this->db->isConnected()) {
            $this->dropTestTable();
            $this->db->disconnect();
        }
        parent::tearDown();
    }
    
    // ========================================
    // Connection Tests
    // ========================================
    
    /**
     * @test
     */
    public function testConnectionEstablishment()
    {
        $this->assertTrue($this->db->isConnected(), 'Database should be connected');
    }
    
    /**
     * @test
     */
    public function testDisconnection()
    {
        $this->db->disconnect();
        $this->assertFalse($this->db->isConnected(), 'Database should be disconnected');
        
        // Reconnect for tearDown
        $this->db->connect();
    }
    
    // ========================================
    // CRUD Operation Tests - Specific Examples
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: CRUD operations
     */
    public function testInsertCreatesNewRecord()
    {
        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'amount' => 100.50
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        
        $this->assertNotFalse($id, 'Insert should return an ID');
        $this->assertIsString($id, 'Insert ID should be a string');
        $this->assertGreaterThan(0, (int)$id, 'Insert ID should be positive');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: CRUD operations
     */
    public function testFindByIdRetrievesCorrectRecord()
    {
        $data = [
            'username' => 'jane_smith',
            'email' => 'jane@example.com',
            'amount' => 250.75
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
        $this->assertNotNull($record, 'Record should be found');
        $this->assertEquals('jane_smith', $record['username']);
        $this->assertEquals('jane@example.com', $record['email']);
        $this->assertEquals(250.75, (float)$record['amount']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: CRUD operations
     */
    public function testUpdateModifiesExistingRecord()
    {
        $data = [
            'username' => 'bob_jones',
            'email' => 'bob@example.com',
            'amount' => 50.00
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        
        $updateData = [
            'email' => 'bob.jones@example.com',
            'amount' => 75.25
        ];
        
        $success = $this->db->update($this->testTable, $id, $updateData);
        $this->assertTrue($success, 'Update should succeed');
        
        $record = $this->db->findById($this->testTable, $id);
        $this->assertEquals('bob.jones@example.com', $record['email']);
        $this->assertEquals(75.25, (float)$record['amount']);
        $this->assertEquals('bob_jones', $record['username'], 'Unchanged field should remain');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: CRUD operations
     */
    public function testDeleteRemovesRecord()
    {
        $data = [
            'username' => 'alice_wonder',
            'email' => 'alice@example.com',
            'amount' => 300.00
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $success = $this->db->delete($this->testTable, $id);
        
        $this->assertTrue($success, 'Delete should succeed');
        
        $record = $this->db->findById($this->testTable, $id);
        $this->assertNull($record, 'Deleted record should not be found');
    }
    
    // ========================================
    // Query Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Query operations
     */
    public function testQueryWithConditionsReturnsMatchingRecords()
    {
        // Insert test data
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 100]);
        
        $results = $this->db->query($this->testTable, ['amount' => 100]);
        
        $this->assertCount(2, $results, 'Should find 2 records with amount 100');
        $this->assertEquals('user1', $results[0]['username']);
        $this->assertEquals('user3', $results[1]['username']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Query operations with operators
     */
    public function testQueryWithOperatorConditions()
    {
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 50]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 150]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 250]);
        
        $results = $this->db->query($this->testTable, ['amount' => ['>', 100]]);
        
        $this->assertCount(2, $results, 'Should find 2 records with amount > 100');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Query with ordering
     */
    public function testQueryWithOrderBy()
    {
        $this->db->insert($this->testTable, ['username' => 'charlie', 'email' => 'c@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'alice', 'email' => 'a@test.com', 'amount' => 200]);
        $this->db->insert($this->testTable, ['username' => 'bob', 'email' => 'b@test.com', 'amount' => 150]);
        
        $results = $this->db->query($this->testTable, [], ['username' => 'ASC']);
        
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
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 300]);
        
        $results = $this->db->query($this->testTable, [], [], 2);
        
        $this->assertCount(2, $results, 'Should return only 2 records');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: QueryOne operation
     */
    public function testQueryOneReturnsFirstMatch()
    {
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 100]);
        
        $record = $this->db->queryOne($this->testTable, ['amount' => 100]);
        
        $this->assertNotNull($record, 'Should find a record');
        $this->assertEquals('user1', $record['username']);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Count operation
     */
    public function testCountReturnsCorrectNumber()
    {
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 100]);
        
        $count = $this->db->count($this->testTable, ['amount' => 100]);
        
        $this->assertEquals(2, $count);
    }
    
    // ========================================
    // Aggregate Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Sum aggregate operation
     */
    public function testSumCalculatesTotal()
    {
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100.50]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200.25]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 150.75]);
        
        $sum = $this->db->sum($this->testTable, 'amount');
        
        $this->assertEquals(451.50, $sum, '', 0.01);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Average aggregate operation
     */
    public function testAvgCalculatesAverage()
    {
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 300]);
        
        $avg = $this->db->avg($this->testTable, 'amount');
        
        $this->assertEquals(200.0, $avg, '', 0.01);
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Sum with conditions
     */
    public function testSumWithConditions()
    {
        $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 300]);
        
        $sum = $this->db->sum($this->testTable, 'amount', ['amount' => ['>', 150]]);
        
        $this->assertEquals(500.0, $sum, '', 0.01);
    }
    
    // ========================================
    // Batch Operation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Batch insert operation
     */
    public function testBatchInsertCreatesMultipleRecords()
    {
        $records = [
            ['username' => 'batch1', 'email' => 'batch1@test.com', 'amount' => 100],
            ['username' => 'batch2', 'email' => 'batch2@test.com', 'amount' => 200],
            ['username' => 'batch3', 'email' => 'batch3@test.com', 'amount' => 300]
        ];
        
        $success = $this->db->batchInsert($this->testTable, $records);
        
        $this->assertTrue($success, 'Batch insert should succeed');
        
        $count = $this->db->count($this->testTable);
        $this->assertEquals(3, $count, 'Should have 3 records');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Batch delete operation
     */
    public function testBatchDeleteRemovesMultipleRecords()
    {
        $id1 = $this->db->insert($this->testTable, ['username' => 'user1', 'email' => 'user1@test.com', 'amount' => 100]);
        $id2 = $this->db->insert($this->testTable, ['username' => 'user2', 'email' => 'user2@test.com', 'amount' => 200]);
        $id3 = $this->db->insert($this->testTable, ['username' => 'user3', 'email' => 'user3@test.com', 'amount' => 300]);
        
        $success = $this->db->batchDelete($this->testTable, [$id1, $id3]);
        
        $this->assertTrue($success, 'Batch delete should succeed');
        
        $this->assertNull($this->db->findById($this->testTable, $id1));
        $this->assertNotNull($this->db->findById($this->testTable, $id2));
        $this->assertNull($this->db->findById($this->testTable, $id3));
    }
    
    // ========================================
    // Transaction Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.1: Transaction commit
     */
    public function testTransactionCommit()
    {
        $this->db->beginTransaction();
        
        $id = $this->db->insert($this->testTable, ['username' => 'trans_user', 'email' => 'trans@test.com', 'amount' => 100]);
        
        $this->db->commit();
        
        $record = $this->db->findById($this->testTable, $id);
        $this->assertNotNull($record, 'Committed record should exist');
    }
    
    /**
     * @test
     * Validates Requirement 1.1: Transaction rollback
     */
    public function testTransactionRollback()
    {
        $this->db->beginTransaction();
        
        $id = $this->db->insert($this->testTable, ['username' => 'rollback_user', 'email' => 'rollback@test.com', 'amount' => 100]);
        
        $this->db->rollback();
        
        $record = $this->db->findById($this->testTable, $id);
        $this->assertNull($record, 'Rolled back record should not exist');
    }
    
    // ========================================
    // PreparedStatement Interface Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 1.2: Prepared statement interface
     */
    public function testPreparedStatementInsert()
    {
        $sql = "INSERT INTO {$this->testTable} (username, email, amount) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind(['prep_user', 'prep@test.com', 150.50]);
        $success = $stmt->execute();
        
        $this->assertTrue($success, 'Prepared statement should execute');
        
        $insertId = $stmt->getInsertId();
        $this->assertGreaterThan(0, (int)$insertId, 'Should return insert ID');
        
        $record = $this->db->findById($this->testTable, $insertId);
        $this->assertEquals('prep_user', $record['username']);
    }
    
    /**
     * @test
     * Validates Requirement 1.2: Prepared statement select
     */
    public function testPreparedStatementSelect()
    {
        $this->db->insert($this->testTable, ['username' => 'select_user', 'email' => 'select@test.com', 'amount' => 200]);
        
        $sql = "SELECT * FROM {$this->testTable} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind(['select_user']);
        $stmt->execute();
        
        $results = $stmt->getResult();
        
        $this->assertCount(1, $results);
        $this->assertEquals('select_user', $results[0]['username']);
    }
    
    /**
     * @test
     * Validates Requirement 1.2: Prepared statement update
     */
    public function testPreparedStatementUpdate()
    {
        $id = $this->db->insert($this->testTable, ['username' => 'update_user', 'email' => 'update@test.com', 'amount' => 100]);
        
        $sql = "UPDATE {$this->testTable} SET amount = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind([250.75, $id]);
        $success = $stmt->execute();
        
        $this->assertTrue($success);
        $this->assertEquals(1, $stmt->getAffectedRows());
        
        $record = $this->db->findById($this->testTable, $id);
        $this->assertEquals(250.75, (float)$record['amount']);
    }
    
    /**
     * @test
     * Validates Requirement 1.2: Prepared statement delete
     */
    public function testPreparedStatementDelete()
    {
        $id = $this->db->insert($this->testTable, ['username' => 'delete_user', 'email' => 'delete@test.com', 'amount' => 100]);
        
        $sql = "DELETE FROM {$this->testTable} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind([$id]);
        $success = $stmt->execute();
        
        $this->assertTrue($success);
        $this->assertEquals(1, $stmt->getAffectedRows());
        
        $record = $this->db->findById($this->testTable, $id);
        $this->assertNull($record);
    }
    
    // ========================================
    // Edge Case Tests
    // ========================================
    
    /**
     * @test
     * Edge case: Null values
     */
    public function testInsertWithNullValues()
    {
        $data = [
            'username' => 'null_user',
            'email' => null,
            'amount' => 100
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals('null_user', $record['username']);
        $this->assertNull($record['email']);
    }
    
    /**
     * @test
     * Edge case: Empty strings
     */
    public function testInsertWithEmptyStrings()
    {
        $data = [
            'username' => '',
            'email' => 'empty@test.com',
            'amount' => 0
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals('', $record['username']);
        $this->assertEquals(0.0, (float)$record['amount']);
    }
    
    /**
     * @test
     * Edge case: Boundary values for decimal amounts
     */
    public function testInsertWithBoundaryDecimalValues()
    {
        // Maximum value for DECIMAL(10,2)
        $data = [
            'username' => 'max_amount',
            'email' => 'max@test.com',
            'amount' => 99999999.99
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals(99999999.99, (float)$record['amount']);
    }
    
    /**
     * @test
     * Edge case: Minimum decimal value
     */
    public function testInsertWithMinimumDecimalValue()
    {
        $data = [
            'username' => 'min_amount',
            'email' => 'min@test.com',
            'amount' => 0.01
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals(0.01, (float)$record['amount']);
    }
    
    /**
     * @test
     * Edge case: Negative amounts
     */
    public function testInsertWithNegativeAmount()
    {
        $data = [
            'username' => 'negative_user',
            'email' => 'negative@test.com',
            'amount' => -50.25
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
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
            'email' => 'special@test.com',
            'amount' => 100
        ];
        
        $id = $this->db->insert($this->testTable, $data);
        $record = $this->db->findById($this->testTable, $id);
        
        $this->assertNotFalse($id);
        $this->assertEquals("user's \"name\" with <special> & chars", $record['username']);
    }
    
    /**
     * @test
     * Edge case: Query with no results
     */
    public function testQueryWithNoResults()
    {
        $results = $this->db->query($this->testTable, ['username' => 'nonexistent']);
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
    
    /**
     * @test
     * Edge case: QueryOne with no results
     */
    public function testQueryOneWithNoResults()
    {
        $record = $this->db->queryOne($this->testTable, ['username' => 'nonexistent']);
        
        $this->assertNull($record);
    }
    
    /**
     * @test
     * Edge case: FindById with nonexistent ID
     */
    public function testFindByIdWithNonexistentId()
    {
        $record = $this->db->findById($this->testTable, '999999');
        
        $this->assertNull($record);
    }
    
    /**
     * @test
     * Edge case: Update nonexistent record
     */
    public function testUpdateNonexistentRecord()
    {
        $success = $this->db->update($this->testTable, '999999', ['username' => 'updated']);
        
        // Update should succeed but affect 0 rows
        $this->assertTrue($success);
    }
    
    /**
     * @test
     * Edge case: Delete nonexistent record
     */
    public function testDeleteNonexistentRecord()
    {
        $success = $this->db->delete($this->testTable, '999999');
        
        // Delete should succeed but affect 0 rows
        $this->assertTrue($success);
    }
    
    /**
     * @test
     * Edge case: Count with no matching records
     */
    public function testCountWithNoMatches()
    {
        $count = $this->db->count($this->testTable, ['username' => 'nonexistent']);
        
        $this->assertEquals(0, $count);
    }
    
    /**
     * @test
     * Edge case: Sum with no matching records
     */
    public function testSumWithNoMatches()
    {
        $sum = $this->db->sum($this->testTable, 'amount', ['username' => 'nonexistent']);
        
        $this->assertEquals(0.0, $sum);
    }
    
    /**
     * @test
     * Edge case: Batch insert with empty array
     */
    public function testBatchInsertWithEmptyArray()
    {
        $success = $this->db->batchInsert($this->testTable, []);
        
        $this->assertFalse($success, 'Batch insert with empty array should fail');
    }
    
    /**
     * @test
     * Edge case: Batch delete with empty array
     */
    public function testBatchDeleteWithEmptyArray()
    {
        $success = $this->db->batchDelete($this->testTable, []);
        
        $this->assertFalse($success, 'Batch delete with empty array should fail');
    }
    
    // ========================================
    // Helper Methods
    // ========================================
    
    /**
     * Create test table for unit tests
     */
    private function createTestTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->testTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            amount DECIMAL(10, 2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
    
    /**
     * Drop test table
     */
    private function dropTestTable(): void
    {
        $sql = "DROP TABLE IF EXISTS {$this->testTable}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
}
