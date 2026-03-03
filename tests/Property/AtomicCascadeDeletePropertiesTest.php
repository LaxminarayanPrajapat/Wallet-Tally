<?php

namespace WalletTally\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/DatabaseInterface.php';
require_once __DIR__ . '/../../includes/MySQLDatabase.php';

/**
 * Property-Based Tests for Atomic Cascade Delete
 * 
 * Tests universal properties for cascade delete operations that should maintain
 * atomicity - either all related records are deleted or none are deleted.
 * 
 * Feature: firebase-migration
 * Property 10: Atomic Cascade Delete
 * Validates: Requirements 13.1, 13.2, 13.5
 */
class AtomicCascadeDeletePropertiesTest extends TestCase
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
     * Feature: firebase-migration, Property 10: Atomic Cascade Delete
     * 
     * For any delete operation with cascading relationships (delete category→transactions,
     * delete user→categories/transactions/feedback/warnings), either all related records
     * are deleted or none are deleted.
     * 
     * Validates: Requirements 13.1, 13.2, 13.5
     * 
     * @test
     */
    public function testCategoryDeleteCascadesToTransactionsAtomically()
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
            )->withMinSize(1)->withMaxSize(10)
        )
        ->then(function ($categoryData, $transactions) {
            // Test with MySQL
            $this->verifyCategoryCascadeDelete($this->mysqlDb, $categoryData, $transactions, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyCategoryCascadeDelete($this->firebaseDb, $categoryData, $transactions, 'Firebase');
            }
        });
    }
    
    /**
     * Property: User delete cascades to all related records atomically
     * 
     * When deleting a user, all related categories, transactions, feedback,
     * and warnings must be deleted atomically.
     * 
     * Validates: Requirements 13.2, 13.5
     * 
     * @test
     */
    public function testUserDeleteCascadesToAllRelatedRecordsAtomically()
    {
        $this->forAll(
            Generator\associative([
                'username' => Generator\string()->withMaxSize(50),
                'email' => Generator\string()->withMaxSize(100),
                'password' => Generator\string()->withMinSize(60)->withMaxSize(60),
                'country' => Generator\elements(['US', 'UK', 'CA']),
                'currency' => Generator\elements(['USD', 'GBP', 'CAD'])
            ]),
            Generator\int(1, 5), // Number of categories
            Generator\int(1, 10), // Number of transactions
            Generator\int(0, 3), // Number of feedback records
            Generator\int(0, 2)  // Number of warnings
        )
        ->then(function ($userData, $numCategories, $numTransactions, $numFeedback, $numWarnings) {
            // Test with MySQL
            $this->verifyUserCascadeDelete(
                $this->mysqlDb,
                $userData,
                $numCategories,
                $numTransactions,
                $numFeedback,
                $numWarnings,
                'MySQL'
            );
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyUserCascadeDelete(
                    $this->firebaseDb,
                    $userData,
                    $numCategories,
                    $numTransactions,
                    $numFeedback,
                    $numWarnings,
                    'Firebase'
                );
            }
        });
    }
    
    /**
     * Property: Cascade delete maintains atomicity on partial failure
     * 
     * If any step in a cascade delete fails, the entire operation should
     * be rolled back and no records should be deleted.
     * 
     * Validates: Requirements 13.1, 13.2, 13.5
     * 
     * @test
     */
    public function testCascadeDeleteRollsBackOnFailure()
    {
        $this->forAll(
            Generator\associative([
                'user_id' => Generator\int(1, 1000),
                'category_name' => Generator\string()->withMaxSize(50),
                'category_type' => Generator\elements(['income', 'expense'])
            ]),
            Generator\int(1, 5) // Number of transactions
        )
        ->then(function ($categoryData, $numTransactions) {
            // Test with MySQL
            $this->verifyRollbackOnFailure($this->mysqlDb, $categoryData, $numTransactions, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyRollbackOnFailure($this->firebaseDb, $categoryData, $numTransactions, 'Firebase');
            }
        });
    }
    
    /**
     * Property: Batch delete operations are atomic
     * 
     * When deleting multiple records in a batch, either all deletions
     * succeed or none succeed.
     * 
     * Validates: Requirements 13.1, 13.2
     * 
     * @test
     */
    public function testBatchDeleteIsAtomic()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'user_id' => Generator\int(1, 1000),
                    'amount' => Generator\choose(1, 10000),
                    'description' => Generator\string()->withMaxSize(100),
                    'type' => Generator\elements(['income', 'expense'])
                ])
            )->withMinSize(2)->withMaxSize(20)
        )
        ->then(function ($transactions) {
            // Test with MySQL
            $this->verifyBatchDeleteAtomicity($this->mysqlDb, $transactions, 'MySQL');
            
            // Test with Firebase if available
            if ($this->firebaseDb) {
                $this->verifyBatchDeleteAtomicity($this->firebaseDb, $transactions, 'Firebase');
            }
        });
    }
    
    /**
     * Helper: Verify category cascade delete atomicity
     */
    private function verifyCategoryCascadeDelete(
        \DatabaseInterface $db,
        array $categoryData,
        array $transactions,
        string $dbName
    ): void {
        // Insert category
        $categoryId = $db->insert('test_categories', [
            'user_id' => $categoryData['user_id'],
            'name' => $categoryData['category_name'],
            'type' => $categoryData['category_type']
        ]);
        
        $this->assertNotFalse($categoryId, "$dbName: Category insert should succeed");
        
        // Insert transactions for this category
        $transactionIds = [];
        foreach ($transactions as $txn) {
            $txnId = $db->insert('test_transactions', [
                'user_id' => $categoryData['user_id'],
                'category_id' => $categoryId,
                'type' => $categoryData['category_type'],
                'amount' => $txn['amount'],
                'description' => $txn['description'],
                'category' => $categoryData['category_name']
            ]);
            $this->assertNotFalse($txnId, "$dbName: Transaction insert should succeed");
            $transactionIds[] = $txnId;
        }
        
        // Verify records exist before delete
        $categoryBefore = $db->findById('test_categories', $categoryId);
        $this->assertNotNull($categoryBefore, "$dbName: Category should exist before delete");
        
        $transactionsBefore = $db->query('test_transactions', ['category_id' => $categoryId]);
        $this->assertCount(
            count($transactions),
            $transactionsBefore,
            "$dbName: All transactions should exist before delete"
        );
        
        // Perform cascade delete using transaction
        $db->beginTransaction();
        
        try {
            // Delete transactions first
            $db->batchDelete('test_transactions', $transactionIds);
            
            // Then delete category
            $db->delete('test_categories', $categoryId);
            
            // Commit transaction
            $db->commit();
            
            // Verify all records are deleted
            $categoryAfter = $db->findById('test_categories', $categoryId);
            $this->assertNull($categoryAfter, "$dbName: Category should be deleted");
            
            $transactionsAfter = $db->query('test_transactions', ['category_id' => $categoryId]);
            $this->assertEmpty(
                $transactionsAfter,
                "$dbName: All transactions should be deleted"
            );
            
        } catch (\Exception $e) {
            // Rollback on failure
            $db->rollback();
            
            // Verify nothing was deleted (atomicity)
            $categoryAfterRollback = $db->findById('test_categories', $categoryId);
            $this->assertNotNull(
                $categoryAfterRollback,
                "$dbName: Category should still exist after rollback"
            );
            
            $transactionsAfterRollback = $db->query('test_transactions', ['category_id' => $categoryId]);
            $this->assertCount(
                count($transactions),
                $transactionsAfterRollback,
                "$dbName: All transactions should still exist after rollback"
            );
            
            // Clean up for next iteration
            $db->beginTransaction();
            $db->batchDelete('test_transactions', $transactionIds);
            $db->delete('test_categories', $categoryId);
            $db->commit();
        }
    }
    
    /**
     * Helper: Verify user cascade delete atomicity
     */
    private function verifyUserCascadeDelete(
        \DatabaseInterface $db,
        array $userData,
        int $numCategories,
        int $numTransactions,
        int $numFeedback,
        int $numWarnings,
        string $dbName
    ): void {
        // Insert user
        $userId = $db->insert('test_users', $userData);
        $this->assertNotFalse($userId, "$dbName: User insert should succeed");
        
        // Insert categories
        $categoryIds = [];
        for ($i = 0; $i < $numCategories; $i++) {
            $catId = $db->insert('test_categories', [
                'user_id' => $userId,
                'name' => "Category_$i",
                'type' => $i % 2 === 0 ? 'income' : 'expense'
            ]);
            $categoryIds[] = $catId;
        }
        
        // Insert transactions
        $transactionIds = [];
        for ($i = 0; $i < $numTransactions; $i++) {
            $txnId = $db->insert('test_transactions', [
                'user_id' => $userId,
                'category_id' => $categoryIds[$i % count($categoryIds)],
                'type' => $i % 2 === 0 ? 'income' : 'expense',
                'amount' => 100 + $i,
                'description' => "Transaction_$i",
                'category' => "Category_" . ($i % count($categoryIds))
            ]);
            $transactionIds[] = $txnId;
        }
        
        // Insert feedback
        $feedbackIds = [];
        for ($i = 0; $i < $numFeedback; $i++) {
            $fbId = $db->insert('test_feedback', [
                'user_id' => $userId,
                'rating' => 3 + ($i % 3),
                'feedback' => "Feedback_$i",
                'display_approved' => $i % 2 === 0
            ]);
            $feedbackIds[] = $fbId;
        }
        
        // Insert warnings
        $warningIds = [];
        for ($i = 0; $i < $numWarnings; $i++) {
            $warnId = $db->insert('test_warnings', [
                'user_id' => $userId,
                'admin_name' => 'admin',
                'category' => 'test',
                'description' => "Warning_$i"
            ]);
            $warningIds[] = $warnId;
        }
        
        // Verify all records exist
        $this->assertNotNull($db->findById('test_users', $userId));
        $this->assertCount($numCategories, $db->query('test_categories', ['user_id' => $userId]));
        $this->assertCount($numTransactions, $db->query('test_transactions', ['user_id' => $userId]));
        $this->assertCount($numFeedback, $db->query('test_feedback', ['user_id' => $userId]));
        $this->assertCount($numWarnings, $db->query('test_warnings', ['user_id' => $userId]));
        
        // Perform cascade delete
        $db->beginTransaction();
        
        try {
            // Delete in correct order (transactions first, then categories, then feedback, warnings, user)
            if (!empty($transactionIds)) {
                $db->batchDelete('test_transactions', $transactionIds);
            }
            if (!empty($categoryIds)) {
                $db->batchDelete('test_categories', $categoryIds);
            }
            if (!empty($feedbackIds)) {
                $db->batchDelete('test_feedback', $feedbackIds);
            }
            if (!empty($warningIds)) {
                $db->batchDelete('test_warnings', $warningIds);
            }
            $db->delete('test_users', $userId);
            
            $db->commit();
            
            // Verify all records are deleted
            $this->assertNull($db->findById('test_users', $userId), "$dbName: User should be deleted");
            $this->assertEmpty($db->query('test_categories', ['user_id' => $userId]), "$dbName: Categories should be deleted");
            $this->assertEmpty($db->query('test_transactions', ['user_id' => $userId]), "$dbName: Transactions should be deleted");
            $this->assertEmpty($db->query('test_feedback', ['user_id' => $userId]), "$dbName: Feedback should be deleted");
            $this->assertEmpty($db->query('test_warnings', ['user_id' => $userId]), "$dbName: Warnings should be deleted");
            
        } catch (\Exception $e) {
            $db->rollback();
            
            // Verify nothing was deleted (atomicity)
            $this->assertNotNull($db->findById('test_users', $userId), "$dbName: User should exist after rollback");
            $this->assertCount($numCategories, $db->query('test_categories', ['user_id' => $userId]), "$dbName: Categories should exist after rollback");
            $this->assertCount($numTransactions, $db->query('test_transactions', ['user_id' => $userId]), "$dbName: Transactions should exist after rollback");
            $this->assertCount($numFeedback, $db->query('test_feedback', ['user_id' => $userId]), "$dbName: Feedback should exist after rollback");
            $this->assertCount($numWarnings, $db->query('test_warnings', ['user_id' => $userId]), "$dbName: Warnings should exist after rollback");
            
            // Clean up
            $db->beginTransaction();
            if (!empty($transactionIds)) $db->batchDelete('test_transactions', $transactionIds);
            if (!empty($categoryIds)) $db->batchDelete('test_categories', $categoryIds);
            if (!empty($feedbackIds)) $db->batchDelete('test_feedback', $feedbackIds);
            if (!empty($warningIds)) $db->batchDelete('test_warnings', $warningIds);
            $db->delete('test_users', $userId);
            $db->commit();
        }
    }
    
    /**
     * Helper: Verify rollback on failure
     */
    private function verifyRollbackOnFailure(
        \DatabaseInterface $db,
        array $categoryData,
        int $numTransactions,
        string $dbName
    ): void {
        // Insert category
        $categoryId = $db->insert('test_categories', [
            'user_id' => $categoryData['user_id'],
            'name' => $categoryData['category_name'],
            'type' => $categoryData['category_type']
        ]);
        
        // Insert transactions
        $transactionIds = [];
        for ($i = 0; $i < $numTransactions; $i++) {
            $txnId = $db->insert('test_transactions', [
                'user_id' => $categoryData['user_id'],
                'category_id' => $categoryId,
                'type' => $categoryData['category_type'],
                'amount' => 100 + $i,
                'description' => "Transaction_$i",
                'category' => $categoryData['category_name']
            ]);
            $transactionIds[] = $txnId;
        }
        
        // Count records before attempted delete
        $categoryBefore = $db->findById('test_categories', $categoryId);
        $transactionsBefore = $db->query('test_transactions', ['category_id' => $categoryId]);
        
        $this->assertNotNull($categoryBefore);
        $this->assertCount($numTransactions, $transactionsBefore);
        
        // Attempt cascade delete with intentional failure
        $db->beginTransaction();
        
        try {
            // Delete some transactions
            $db->delete('test_transactions', $transactionIds[0]);
            
            // Simulate failure by trying to delete non-existent record
            // This should cause the transaction to fail
            $db->delete('test_transactions', 'non_existent_id_12345');
            
            // This should not be reached
            $db->commit();
            
        } catch (\Exception $e) {
            // Rollback on failure
            $db->rollback();
        }
        
        // Verify all records still exist (rollback worked)
        $categoryAfter = $db->findById('test_categories', $categoryId);
        $transactionsAfter = $db->query('test_transactions', ['category_id' => $categoryId]);
        
        $this->assertNotNull($categoryAfter, "$dbName: Category should still exist after rollback");
        $this->assertCount(
            $numTransactions,
            $transactionsAfter,
            "$dbName: All transactions should still exist after rollback"
        );
        
        // Clean up
        $db->beginTransaction();
        $db->batchDelete('test_transactions', $transactionIds);
        $db->delete('test_categories', $categoryId);
        $db->commit();
    }
    
    /**
     * Helper: Verify batch delete atomicity
     */
    private function verifyBatchDeleteAtomicity(
        \DatabaseInterface $db,
        array $transactions,
        string $dbName
    ): void {
        // Insert all transactions
        $transactionIds = [];
        foreach ($transactions as $txn) {
            $txnId = $db->insert('test_transactions', [
                'user_id' => $txn['user_id'],
                'category_id' => 1,
                'type' => $txn['type'],
                'amount' => $txn['amount'],
                'description' => $txn['description'],
                'category' => 'test'
            ]);
            $transactionIds[] = $txnId;
        }
        
        // Verify all exist
        foreach ($transactionIds as $id) {
            $this->assertNotNull($db->findById('test_transactions', $id));
        }
        
        // Perform batch delete
        $db->beginTransaction();
        $success = $db->batchDelete('test_transactions', $transactionIds);
        $db->commit();
        
        $this->assertTrue($success, "$dbName: Batch delete should succeed");
        
        // Verify all are deleted
        foreach ($transactionIds as $id) {
            $this->assertNull(
                $db->findById('test_transactions', $id),
                "$dbName: Transaction $id should be deleted"
            );
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
            password VARCHAR(255) NOT NULL,
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
        
        // Create test_feedback table
        $sql = "CREATE TABLE IF NOT EXISTS test_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            rating INT NOT NULL,
            feedback TEXT,
            display_approved BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        // Create test_warnings table
        $sql = "CREATE TABLE IF NOT EXISTS test_warnings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            admin_name VARCHAR(100) NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT,
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
            'test_warnings',
            'test_feedback',
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
