<?php

namespace WalletTally\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/DatabaseInterface.php';
require_once __DIR__ . '/../../includes/MySQLDatabase.php';

/**
 * Property-Based Tests for Database Abstraction Layer
 * 
 * Tests universal properties that should hold across all database implementations.
 */
class DatabaseAbstractionPropertiesTest extends TestCase
{
    use TestTrait;
    
    private \DatabaseInterface $mysqlDb;
    private ?\DatabaseInterface $firebaseDb = null;
    private array $testCollections = ['test_users', 'test_transactions', 'test_categories'];
    
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
     * Feature: firebase-migration, Property 22: Database Implementation Switching
     * 
     * For any database operation, switching between MySQL and Firebase implementations
     * via configuration should produce equivalent results.
     * 
     * Validates: Requirements 1.5, 20.1, 20.2
     * 
     * @test
     */
    public function testDatabaseImplementationSwitchingProducesEquivalentResults()
    {
        if (!$this->firebaseDb) {
            $this->markTestSkipped('Firebase database not available for testing');
        }
        
        $this->forAll(
            Generator\associative([
                'username' => Generator\string()->withMaxSize(50),
                'email' => Generator\string()->withMaxSize(100),
                'password' => Generator\string()->withMinSize(60)->withMaxSize(60),
                'country' => Generator\elements(['US', 'UK', 'CA', 'AU', 'DE', 'FR']),
                'currency' => Generator\elements(['USD', 'GBP', 'CAD', 'AUD', 'EUR'])
            ])
        )
        ->then(function ($userData) {
            // Insert into MySQL
            $mysqlId = $this->mysqlDb->insert('test_users', $userData);
            $this->assertNotFalse($mysqlId, 'MySQL insert should succeed');
            
            // Insert same data into Firebase
            $firebaseId = $this->firebaseDb->insert('test_users', $userData);
            $this->assertNotFalse($firebaseId, 'Firebase insert should succeed');
            
            // Read from MySQL
            $mysqlRecord = $this->mysqlDb->findById('test_users', $mysqlId);
            $this->assertNotNull($mysqlRecord, 'MySQL record should be found');
            
            // Read from Firebase
            $firebaseRecord = $this->firebaseDb->findById('test_users', $firebaseId);
            $this->assertNotNull($firebaseRecord, 'Firebase record should be found');
            
            // Compare field values (excluding auto-generated IDs and timestamps)
            $this->assertEquals(
                $userData['username'],
                $mysqlRecord['username'],
                'MySQL should preserve username'
            );
            $this->assertEquals(
                $userData['username'],
                $firebaseRecord['username'],
                'Firebase should preserve username'
            );
            
            $this->assertEquals(
                $userData['email'],
                $mysqlRecord['email'],
                'MySQL should preserve email'
            );
            $this->assertEquals(
                $userData['email'],
                $firebaseRecord['email'],
                'Firebase should preserve email'
            );
            
            $this->assertEquals(
                $userData['password'],
                $mysqlRecord['password'],
                'MySQL should preserve password hash'
            );
            $this->assertEquals(
                $userData['password'],
                $firebaseRecord['password'],
                'Firebase should preserve password hash'
            );
            
            $this->assertEquals(
                $userData['country'],
                $mysqlRecord['country'],
                'MySQL should preserve country'
            );
            $this->assertEquals(
                $userData['country'],
                $firebaseRecord['country'],
                'Firebase should preserve country'
            );
            
            $this->assertEquals(
                $userData['currency'],
                $mysqlRecord['currency'],
                'MySQL should preserve currency'
            );
            $this->assertEquals(
                $userData['currency'],
                $firebaseRecord['currency'],
                'Firebase should preserve currency'
            );
            
            // Cleanup
            $this->mysqlDb->delete('test_users', $mysqlId);
            $this->firebaseDb->delete('test_users', $firebaseId);
        });
    }
    
    /**
     * Property: Query operations produce equivalent results across implementations
     * 
     * @test
     */
    public function testQueryOperationsProduceEquivalentResults()
    {
        if (!$this->firebaseDb) {
            $this->markTestSkipped('Firebase database not available for testing');
        }
        
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'username' => Generator\string()->withMaxSize(50),
                    'email' => Generator\string()->withMaxSize(100),
                    'country' => Generator\elements(['US', 'UK', 'CA'])
                ])
            )->withMaxSize(5)
        )
        ->then(function ($users) {
            $mysqlIds = [];
            $firebaseIds = [];
            
            // Insert test data into both databases
            foreach ($users as $user) {
                $mysqlIds[] = $this->mysqlDb->insert('test_users', $user);
                $firebaseIds[] = $this->firebaseDb->insert('test_users', $user);
            }
            
            // Query with condition
            $country = $users[0]['country'] ?? 'US';
            $mysqlResults = $this->mysqlDb->query('test_users', ['country' => $country]);
            $firebaseResults = $this->firebaseDb->query('test_users', ['country' => $country]);
            
            // Both should return same number of results
            $this->assertEquals(
                count($mysqlResults),
                count($firebaseResults),
                'Query should return same count across implementations'
            );
            
            // Count operation should match
            $mysqlCount = $this->mysqlDb->count('test_users', ['country' => $country]);
            $firebaseCount = $this->firebaseDb->count('test_users', ['country' => $country]);
            
            $this->assertEquals(
                $mysqlCount,
                $firebaseCount,
                'Count should return same value across implementations'
            );
            
            // Cleanup
            foreach ($mysqlIds as $id) {
                $this->mysqlDb->delete('test_users', $id);
            }
            foreach ($firebaseIds as $id) {
                $this->firebaseDb->delete('test_users', $id);
            }
        });
    }
    
    /**
     * Property: Update operations produce equivalent results across implementations
     * 
     * @test
     */
    public function testUpdateOperationsProduceEquivalentResults()
    {
        if (!$this->firebaseDb) {
            $this->markTestSkipped('Firebase database not available for testing');
        }
        
        $this->forAll(
            Generator\associative([
                'username' => Generator\string()->withMaxSize(50),
                'email' => Generator\string()->withMaxSize(100),
                'country' => Generator\elements(['US', 'UK', 'CA'])
            ]),
            Generator\associative([
                'country' => Generator\elements(['AU', 'DE', 'FR']),
                'currency' => Generator\elements(['AUD', 'EUR'])
            ])
        )
        ->then(function ($initialData, $updateData) {
            // Insert initial data
            $mysqlId = $this->mysqlDb->insert('test_users', $initialData);
            $firebaseId = $this->firebaseDb->insert('test_users', $initialData);
            
            // Update both records
            $mysqlSuccess = $this->mysqlDb->update('test_users', $mysqlId, $updateData);
            $firebaseSuccess = $this->firebaseDb->update('test_users', $firebaseId, $updateData);
            
            $this->assertTrue($mysqlSuccess, 'MySQL update should succeed');
            $this->assertTrue($firebaseSuccess, 'Firebase update should succeed');
            
            // Read updated records
            $mysqlRecord = $this->mysqlDb->findById('test_users', $mysqlId);
            $firebaseRecord = $this->firebaseDb->findById('test_users', $firebaseId);
            
            // Verify updates applied correctly in both
            $this->assertEquals(
                $updateData['country'],
                $mysqlRecord['country'],
                'MySQL should apply country update'
            );
            $this->assertEquals(
                $updateData['country'],
                $firebaseRecord['country'],
                'Firebase should apply country update'
            );
            
            // Cleanup
            $this->mysqlDb->delete('test_users', $mysqlId);
            $this->firebaseDb->delete('test_users', $firebaseId);
        });
    }
    
    /**
     * Property: Delete operations produce equivalent results across implementations
     * 
     * @test
     */
    public function testDeleteOperationsProduceEquivalentResults()
    {
        if (!$this->firebaseDb) {
            $this->markTestSkipped('Firebase database not available for testing');
        }
        
        $this->forAll(
            Generator\associative([
                'username' => Generator\string()->withMaxSize(50),
                'email' => Generator\string()->withMaxSize(100),
                'country' => Generator\elements(['US', 'UK', 'CA'])
            ])
        )
        ->then(function ($userData) {
            // Insert data
            $mysqlId = $this->mysqlDb->insert('test_users', $userData);
            $firebaseId = $this->firebaseDb->insert('test_users', $userData);
            
            // Delete from both
            $mysqlSuccess = $this->mysqlDb->delete('test_users', $mysqlId);
            $firebaseSuccess = $this->firebaseDb->delete('test_users', $firebaseId);
            
            $this->assertTrue($mysqlSuccess, 'MySQL delete should succeed');
            $this->assertTrue($firebaseSuccess, 'Firebase delete should succeed');
            
            // Verify records are gone
            $mysqlRecord = $this->mysqlDb->findById('test_users', $mysqlId);
            $firebaseRecord = $this->firebaseDb->findById('test_users', $firebaseId);
            
            $this->assertNull($mysqlRecord, 'MySQL record should be deleted');
            $this->assertNull($firebaseRecord, 'Firebase record should be deleted');
        });
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
    }
    
    /**
     * Helper: Clean up test tables
     */
    private function cleanupTestTables(): void
    {
        foreach ($this->testCollections as $collection) {
            $sql = "DROP TABLE IF EXISTS $collection";
            $stmt = $this->mysqlDb->prepare($sql);
            $stmt->execute();
        }
    }
}
