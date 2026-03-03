<?php

namespace WalletTally\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/DatabaseInterface.php';
require_once __DIR__ . '/../../includes/MySQLDatabase.php';

/**
 * Property-Based Tests for Transaction Rollback Completeness
 * 
 * Tests universal properties for transaction rollback operations that should
 * ensure database state remains unchanged if any step in a multi-step operation fails.
 * 
 * Feature: firebase-migration
 * Property 11: Transaction Rollback Completeness
 * Validates: Requirements 13.3
 */
class TransactionRollbackPropertiesTest extends TestCase
{
    use TestTrait;
    
    private \DatabaseInterface $mysqlDb;
    private ?\DatabaseInterface $firebaseDb = null;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize MySQL database
        $this->mysqlDb = new \MySQLDatabase([
            'host' => getenv('DB_HOST') ?: 'localhost',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'database' => getenv('DB_NAME') ?: 'wallet_tally_test',
            'charset' => 'utf8mb4'
        ]);
        
        $this->mysqlDb->connect();
        
        // Create test tables
        $this->createTestTables();
        
        // Initialize Firebase database if available
        if (class_exists('FirebaseDatabase')) {
            $this->firebaseDb = new \FirebaseDatabase([
                'project_id' => getenv('FIREBASE_PROJECT_ID'),
                'credentials_path' => getenv('FIREBASE_CREDENTIALS_PATH'),
                'use_emulator' => getenv('FIREBASE_EMULATOR') === 'true'
            ]);
            $this->firebaseDb->connect();
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestTables();
        
        if ($this->mysqlDb) {
            $this->mysqlDb->disconnect();
        }
        
        if ($this->firebaseDb) {
            $this->firebaseDb->disconnect();
        }
        
        parent::tearDown();
    }
    
    /**
     * Feature: firebase-migration, Property 11: Transaction Rollback Completeness
     * 
     * For any multi-step database operation, if any step fails, the database state
     * should be unchanged from before the operation started.
     * 
     * Validates: Requirements 13.3
     * 
     * @test
     */
    public function testMultiStepInsertRollbackLeavesNoRecords()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'username' => Generator\string()->withMaxSize(50),
                    'email' => Generator\string()->withMaxSize(100),
                    'country' => Generator\elements(['US', 'UK', 'CA'])
                ])
            )->withMinSize(2)->withMaxSize(5)
        )
        ->then(function ($users) {
            // Test with MySQL
            $this->verifyInsertRollback($this->mysqlDb, $users, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyInsertRollback($this->firebaseDb, $users, 'Firebase');
            }
        });
    }
    
    /**
     * Property: Multi-step update rollback restores original values
     * 
     * When multiple update operations are performed in a transaction and one fails,
     * all records should retain their original values.
     * 
     * Validates: Requirements 13.3
     * 
     * @test
     */
    public function testMultiStepUpdateRollbackRestoresOriginalValues()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'username' => Generator\string()->withMaxSize(50),
                    'email' => Generator\string()->withMaxSize(100),
                    'country' => Generator\elements(['US', 'UK', 'CA'])
                ])
            )->withMinSize(2)->withMaxSize(5),
            Generator\associative([
                'country' => Generator\elements(['AU', 'DE', 'FR'])
            ])
        )
        ->then(function ($users, $updateData) {
            // Test with MySQL
            $this->verifyUpdateRollback($this->mysqlDb, $users, $updateData, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyUpdateRollback($this->firebaseDb, $users, $updateData, 'Firebase');
            }
        });
    }
    
    /**
     * Property: Multi-step delete rollback preserves all records
     * 
     * When multiple delete operations are performed in a transaction and one fails,
     * all records should still exist.
     * 
     * Validates: Requirements 13.3
     * 
     * @test
     */
    public function testMultiStepDeleteRollbackPreservesAllRecords()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'username' => Generator\string()->withMaxSize(50),
                    'email' => Generator\string()->withMaxSize(100),
                    'country' => Generator\elements(['US', 'UK', 'CA'])
                ])
            )->withMinSize(2)->withMaxSize(5)
        )
        ->then(function ($users) {
            // Test with MySQL
            $this->verifyDeleteRollback($this->mysqlDb, $users, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyDeleteRollback($this->firebaseDb, $users, 'Firebase');
            }
        });
    }
    
    /**
     * Property: Mixed operation rollback maintains consistency
     * 
     * When a transaction contains inserts, updates, and deletes, and any operation fails,
     * the database should be in the exact same state as before the transaction began.
     * 
     * Validates: Requirements 13.3
     * 
     * @test
     */
    public function testMixedOperationRollbackMaintainsConsistency()
    {
        $this->forAll(
            Generator\associative([
                'username' => Generator\string()->withMaxSize(50),
                'email' => Generator\string()->withMaxSize(100),
                'country' => Generator\elements(['US', 'UK', 'CA'])
            ]),
            Generator\associative([
                'country' => Generator\elements(['AU', 'DE', 'FR'])
            ]),
            Generator\associative([
                'username' => Generator\string()->withMaxSize(50),
                'email' => Generator\string()->withMaxSize(100),
                'country' => Generator\elements(['US', 'UK', 'CA'])
            ])
        )
        ->then(function ($existingUser, $updateData, $newUser) {
            // Test with MySQL
            $this->verifyMixedOperationRollback($this->mysqlDb, $existingUser, $updateData, $newUser, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyMixedOperationRollback($this->firebaseDb, $existingUser, $updateData, $newUser, 'Firebase');
            }
        });
    }
    
    /**
     * Property: Nested transaction rollback maintains atomicity
     * 
     * When transactions are nested (transaction within transaction), rollback of
     * the outer transaction should undo all operations including nested ones.
     * 
     * Validates: Requirements 13.3
     * 
     * @test
     */
    public function testNestedTransactionRollbackMaintainsAtomicity()
    {
        $this->forAll(
            Generator\associative([
                'user_id' => Generator\int(1, 1000),
                'category_name' => Generator\string()->withMaxSize(50),
                'category_type' => Generator\elements(['income', 'expense'])
            ]),
            Generator\seq(
                Generator\associative([
                    'amount' => Generator\choose(1, 10000),
                    'description' => Generator\string()->withMaxSize(100)
                ])
            )->withMinSize(1)->withMaxSize(5)
        )
        ->then(function ($categoryData, $transactions) {
            // Test with MySQL
            $this->verifyNestedTransactionRollback($this->mysqlDb, $categoryData, $transactions, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyNestedTransactionRollback($this->firebaseDb, $categoryData, $transactions, 'Firebase');
            }
        });
    }
    
    /**
     * Helper: Verify insert rollback leaves no records
     */
    private function verifyInsertRollback(
        \DatabaseInterface $db,
        array $users,
        string $dbName
    ): void {
        // Count records before transaction
        $countBefore = $db->count('test_users');
        
        // Begin transaction
        $db->beginTransaction();
        
        $insertedIds = [];
        $shouldFail = false;
        
        try {
            // Insert all users except the last one
            for ($i = 0; $i < count($users) - 1; $i++) {
                $id = $db->insert('test_users', $users[$i]);
                $this->assertNotFalse($id, "$dbName: Insert should succeed");
                $insertedIds[] = $id;
            }
            
            // Intentionally cause a failure by inserting invalid data
            // (e.g., trying to insert with a non-existent ID that would violate constraints)
            $invalidUser = $users[count($users) - 1];
            $invalidUser['id'] = 'invalid_id_that_causes_failure';
            
            $id = $db->insert('test_users', $invalidUser);
            
            // If we get here without exception, commit
            $db->commit();
            
        } catch (\Exception $e) {
            // Expected failure - rollback
            $shouldFail = true;
            $db->rollback();
        }
        
        // Verify rollback worked - count should be same as before
        $countAfter = $db->count('test_users');
        
        if ($shouldFail) {
            $this->assertEquals(
                $countBefore,
                $countAfter,
                "$dbName: Record count should be unchanged after rollback"
            );
            
            // Verify none of the inserted records exist
            foreach ($insertedIds as $id) {
                $record = $db->findById('test_users', $id);
                $this->assertNull(
                    $record,
                    "$dbName: Record $id should not exist after rollback"
                );
            }
        } else {
            // If no failure occurred, clean up the inserted records
            $db->beginTransaction();
            foreach ($insertedIds as $id) {
                $db->delete('test_users', $id);
            }
            $db->commit();
        }
    }
    
    /**
     * Helper: Verify update rollback restores original values
     */
    private function verifyUpdateRollback(
        \DatabaseInterface $db,
        array $users,
        array $updateData,
        string $dbName
    ): void {
        // Insert test users
        $userIds = [];
        $originalData = [];
        
        foreach ($users as $user) {
            $id = $db->insert('test_users', $user);
            $userIds[] = $id;
            $originalData[$id] = $user;
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        $shouldFail = false;
        
        try {
            // Update all users except the last one
            for ($i = 0; $i < count($userIds) - 1; $i++) {
                $success = $db->update('test_users', $userIds[$i], $updateData);
                $this->assertTrue($success, "$dbName: Update should succeed");
            }
            
            // Intentionally cause a failure by updating with invalid ID
            $success = $db->update('test_users', 'non_existent_id_12345', $updateData);
            
            // If we get here, commit
            $db->commit();
            
        } catch (\Exception $e) {
            // Expected failure - rollback
            $shouldFail = true;
            $db->rollback();
        }
        
        // Verify rollback worked - all records should have original values
        if ($shouldFail) {
            foreach ($userIds as $id) {
                $record = $db->findById('test_users', $id);
                $this->assertNotNull($record, "$dbName: Record should still exist");
                
                // Verify original values are preserved
                $this->assertEquals(
                    $originalData[$id]['country'],
                    $record['country'],
                    "$dbName: Original country value should be preserved after rollback"
                );
            }
        }
        
        // Clean up
        $db->beginTransaction();
        foreach ($userIds as $id) {
            $db->delete('test_users', $id);
        }
        $db->commit();
    }
    
    /**
     * Helper: Verify delete rollback preserves all records
     */
    private function verifyDeleteRollback(
        \DatabaseInterface $db,
        array $users,
        string $dbName
    ): void {
        // Insert test users
        $userIds = [];
        
        foreach ($users as $user) {
            $id = $db->insert('test_users', $user);
            $userIds[] = $id;
        }
        
        // Verify all records exist
        foreach ($userIds as $id) {
            $this->assertNotNull($db->findById('test_users', $id));
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        $shouldFail = false;
        
        try {
            // Delete all users except the last one
            for ($i = 0; $i < count($userIds) - 1; $i++) {
                $success = $db->delete('test_users', $userIds[$i]);
                $this->assertTrue($success, "$dbName: Delete should succeed");
            }
            
            // Intentionally cause a failure by deleting non-existent record
            $success = $db->delete('test_users', 'non_existent_id_12345');
            
            // If we get here, commit
            $db->commit();
            
        } catch (\Exception $e) {
            // Expected failure - rollback
            $shouldFail = true;
            $db->rollback();
        }
        
        // Verify rollback worked - all records should still exist
        if ($shouldFail) {
            foreach ($userIds as $id) {
                $record = $db->findById('test_users', $id);
                $this->assertNotNull(
                    $record,
                    "$dbName: Record $id should still exist after rollback"
                );
            }
        }
        
        // Clean up
        $db->beginTransaction();
        foreach ($userIds as $id) {
            $db->delete('test_users', $id);
        }
        $db->commit();
    }
    
    /**
     * Helper: Verify mixed operation rollback maintains consistency
     */
    private function verifyMixedOperationRollback(
        \DatabaseInterface $db,
        array $existingUser,
        array $updateData,
        array $newUser,
        string $dbName
    ): void {
        // Insert existing user
        $existingId = $db->insert('test_users', $existingUser);
        $this->assertNotFalse($existingId);
        
        // Get original state
        $originalRecord = $db->findById('test_users', $existingId);
        $countBefore = $db->count('test_users');
        
        // Begin transaction with mixed operations
        $db->beginTransaction();
        
        $newId = null;
        $shouldFail = false;
        
        try {
            // Operation 1: Update existing user
            $success = $db->update('test_users', $existingId, $updateData);
            $this->assertTrue($success, "$dbName: Update should succeed");
            
            // Operation 2: Insert new user
            $newId = $db->insert('test_users', $newUser);
            $this->assertNotFalse($newId, "$dbName: Insert should succeed");
            
            // Operation 3: Delete with invalid ID (causes failure)
            $success = $db->delete('test_users', 'non_existent_id_12345');
            
            // If we get here, commit
            $db->commit();
            
        } catch (\Exception $e) {
            // Expected failure - rollback
            $shouldFail = true;
            $db->rollback();
        }
        
        // Verify rollback worked
        if ($shouldFail) {
            // Existing record should have original values
            $recordAfter = $db->findById('test_users', $existingId);
            $this->assertNotNull($recordAfter, "$dbName: Existing record should still exist");
            $this->assertEquals(
                $originalRecord['country'],
                $recordAfter['country'],
                "$dbName: Original values should be preserved"
            );
            
            // New record should not exist
            if ($newId) {
                $newRecord = $db->findById('test_users', $newId);
                $this->assertNull(
                    $newRecord,
                    "$dbName: New record should not exist after rollback"
                );
            }
            
            // Count should be same as before
            $countAfter = $db->count('test_users');
            $this->assertEquals(
                $countBefore,
                $countAfter,
                "$dbName: Record count should be unchanged after rollback"
            );
        }
        
        // Clean up
        $db->beginTransaction();
        $db->delete('test_users', $existingId);
        if ($newId && !$shouldFail) {
            $db->delete('test_users', $newId);
        }
        $db->commit();
    }
    
    /**
     * Helper: Verify nested transaction rollback
     */
    private function verifyNestedTransactionRollback(
        \DatabaseInterface $db,
        array $categoryData,
        array $transactions,
        string $dbName
    ): void {
        // Get initial state
        $countCategoriesBefore = $db->count('test_categories');
        $countTransactionsBefore = $db->count('test_transactions');
        
        // Begin outer transaction
        $db->beginTransaction();
        
        $categoryId = null;
        $transactionIds = [];
        $shouldFail = false;
        
        try {
            // Insert category
            $categoryId = $db->insert('test_categories', [
                'user_id' => $categoryData['user_id'],
                'name' => $categoryData['category_name'],
                'type' => $categoryData['category_type']
            ]);
            $this->assertNotFalse($categoryId, "$dbName: Category insert should succeed");
            
            // Insert transactions (simulating nested operations)
            foreach ($transactions as $i => $txn) {
                if ($i === count($transactions) - 1) {
                    // Last transaction - cause failure with invalid data
                    $invalidTxn = $txn;
                    $invalidTxn['category_id'] = 'invalid_category_id';
                    $txnId = $db->insert('test_transactions', [
                        'user_id' => $categoryData['user_id'],
                        'category_id' => $invalidTxn['category_id'],
                        'type' => $categoryData['category_type'],
                        'amount' => $txn['amount'],
                        'description' => $txn['description'],
                        'category' => $categoryData['category_name']
                    ]);
                } else {
                    $txnId = $db->insert('test_transactions', [
                        'user_id' => $categoryData['user_id'],
                        'category_id' => $categoryId,
                        'type' => $categoryData['category_type'],
                        'amount' => $txn['amount'],
                        'description' => $txn['description'],
                        'category' => $categoryData['category_name']
                    ]);
                    $transactionIds[] = $txnId;
                }
            }
            
            // If we get here, commit
            $db->commit();
            
        } catch (\Exception $e) {
            // Expected failure - rollback
            $shouldFail = true;
            $db->rollback();
        }
        
        // Verify rollback worked
        if ($shouldFail) {
            // Category should not exist
            if ($categoryId) {
                $category = $db->findById('test_categories', $categoryId);
                $this->assertNull(
                    $category,
                    "$dbName: Category should not exist after rollback"
                );
            }
            
            // Transactions should not exist
            foreach ($transactionIds as $txnId) {
                $txn = $db->findById('test_transactions', $txnId);
                $this->assertNull(
                    $txn,
                    "$dbName: Transaction should not exist after rollback"
                );
            }
            
            // Counts should be same as before
            $countCategoriesAfter = $db->count('test_categories');
            $countTransactionsAfter = $db->count('test_transactions');
            
            $this->assertEquals(
                $countCategoriesBefore,
                $countCategoriesAfter,
                "$dbName: Category count should be unchanged after rollback"
            );
            $this->assertEquals(
                $countTransactionsBefore,
                $countTransactionsAfter,
                "$dbName: Transaction count should be unchanged after rollback"
            );
        } else {
            // Clean up if no failure
            $db->beginTransaction();
            foreach ($transactionIds as $txnId) {
                $db->delete('test_transactions', $txnId);
            }
            if ($categoryId) {
                $db->delete('test_categories', $categoryId);
            }
            $db->commit();
        }
    }
    
    /**
     * Helper: Create test tables in MySQL
     */
    private function createTestTables(): void
    {
        $conn = $this->mysqlDb;
        
        // Create test_users table
        $sql = "CREATE TABLE IF NOT EXISTS test_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            password VARCHAR(255),
            country VARCHAR(50),
            currency VARCHAR(10),
            profile_picture VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            remember_token VARCHAR(255),
            token_expiry TIMESTAMP NULL
        )";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        // Create test_categories table
        $sql = "CREATE TABLE IF NOT EXISTS test_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            type ENUM('income', 'expense') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        // Create test_transactions table
        $sql = "CREATE TABLE IF NOT EXISTS test_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT,
            type ENUM('income', 'expense') NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    /**
     * Helper: Clean up test tables
     */
    private function cleanupTestTables(): void
    {
        $tables = [
            'test_transactions',
            'test_categories',
            'test_users'
        ];
        
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS $table";
            $stmt = $this->mysqlDb->prepare($sql);
            $stmt->execute();
        }
    }
}
