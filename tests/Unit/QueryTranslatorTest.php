<?php

namespace WalletTally\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/QueryTranslator.php';

/**
 * Unit Tests for QueryTranslator
 * 
 * Tests SQL pattern translation to Firestore queries including:
 * - WHERE clause translation with comparison operators
 * - LIKE pattern matching
 * - ORDER BY clause parsing
 * - LIMIT clause parsing
 * - Aggregate function translation
 * - Date/time function handling
 * 
 * Validates Requirements: 12.1, 12.2, 12.3
 */
class QueryTranslatorTest extends TestCase
{
    private \QueryTranslator $translator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = new \QueryTranslator();
    }
    
    // ========================================
    // WHERE Clause Translation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.1: WHERE clause conversion
     */
    public function testTranslateWhereWithEqualityOperator()
    {
        $conditions = $this->translator->translateWhere("user_id = '123'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        $this->assertEquals('==', $conditions[0]['operator']);
        $this->assertEquals('123', $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.1: WHERE clause with multiple conditions
     */
    public function testTranslateWhereWithMultipleConditions()
    {
        $conditions = $this->translator->translateWhere("user_id = '123' AND type = 'income'");
        
        $this->assertCount(2, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        $this->assertEquals('==', $conditions[0]['operator']);
        $this->assertEquals('123', $conditions[0]['value']);
        
        $this->assertEquals('type', $conditions[1]['field']);
        $this->assertEquals('==', $conditions[1]['operator']);
        $this->assertEquals('income', $conditions[1]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: Comparison operators
     */
    public function testTranslateWhereWithLessThanOperator()
    {
        $conditions = $this->translator->translateWhere("amount < 100");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('amount', $conditions[0]['field']);
        $this->assertEquals('<', $conditions[0]['operator']);
        $this->assertEquals(100, $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: Comparison operators
     */
    public function testTranslateWhereWithGreaterThanOperator()
    {
        $conditions = $this->translator->translateWhere("amount > 50.25");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('amount', $conditions[0]['field']);
        $this->assertEquals('>', $conditions[0]['operator']);
        $this->assertEquals(50.25, $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: Comparison operators
     */
    public function testTranslateWhereWithLessThanOrEqualOperator()
    {
        $conditions = $this->translator->translateWhere("amount <= 200");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('amount', $conditions[0]['field']);
        $this->assertEquals('<=', $conditions[0]['operator']);
        $this->assertEquals(200, $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: Comparison operators
     */
    public function testTranslateWhereWithGreaterThanOrEqualOperator()
    {
        $conditions = $this->translator->translateWhere("amount >= 100.50");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('amount', $conditions[0]['field']);
        $this->assertEquals('>=', $conditions[0]['operator']);
        $this->assertEquals(100.50, $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: Comparison operators
     */
    public function testTranslateWhereWithNotEqualOperator()
    {
        $conditions = $this->translator->translateWhere("status != 'deleted'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('status', $conditions[0]['field']);
        $this->assertEquals('!=', $conditions[0]['operator']);
        $this->assertEquals('deleted', $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: Comparison operators (<> to !=)
     */
    public function testTranslateWhereWithNotEqualAlternativeOperator()
    {
        $conditions = $this->translator->translateWhere("status <> 'deleted'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('status', $conditions[0]['field']);
        $this->assertEquals('!=', $conditions[0]['operator']);
        $this->assertEquals('deleted', $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.7: LIKE operator
     */
    public function testTranslateWhereWithLikeOperator()
    {
        $conditions = $this->translator->translateWhere("username LIKE 'john%'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('username', $conditions[0]['field']);
        $this->assertEquals('LIKE', $conditions[0]['operator']);
        $this->assertEquals('john%', $conditions[0]['value']);
    }
    
    /**
     * @test
     * Test WHERE clause with backticks
     */
    public function testTranslateWhereWithBackticks()
    {
        $conditions = $this->translator->translateWhere("`user_id` = '123'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
    }
    
    /**
     * @test
     * Test WHERE clause with table prefix
     */
    public function testTranslateWhereWithTablePrefix()
    {
        $conditions = $this->translator->translateWhere("transactions.user_id = '123'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        $this->assertEquals('==', $conditions[0]['operator']);
        $this->assertEquals('123', $conditions[0]['value']);
    }
    
    /**
     * @test
     * Test empty WHERE clause
     */
    public function testTranslateWhereWithEmptyClause()
    {
        $conditions = $this->translator->translateWhere("");
        
        $this->assertEmpty($conditions);
    }
    
    /**
     * @test
     * Test WHERE clause with numeric values
     */
    public function testTranslateWhereWithNumericValues()
    {
        $conditions = $this->translator->translateWhere("id = 42 AND amount = 99.99");
        
        $this->assertCount(2, $conditions);
        $this->assertEquals(42, $conditions[0]['value']);
        $this->assertIsInt($conditions[0]['value']);
        
        $this->assertEquals(99.99, $conditions[1]['value']);
        $this->assertIsFloat($conditions[1]['value']);
    }
    
    /**
     * @test
     * Test WHERE clause with boolean values
     */
    public function testTranslateWhereWithBooleanValues()
    {
        $conditions = $this->translator->translateWhere("is_verified = TRUE AND is_deleted = FALSE");
        
        $this->assertCount(2, $conditions);
        $this->assertTrue($conditions[0]['value']);
        $this->assertFalse($conditions[1]['value']);
    }
    
    /**
     * @test
     * Test WHERE clause with NULL value
     */
    public function testTranslateWhereWithNullValue()
    {
        $conditions = $this->translator->translateWhere("deleted_at = NULL");
        
        $this->assertCount(1, $conditions);
        $this->assertNull($conditions[0]['value']);
    }
    
    // ========================================
    // LIKE Pattern Matching Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.7: LIKE pattern matching
     */
    public function testMatchesLikePatternWithWildcardAtEnd()
    {
        $this->assertTrue($this->translator->matchesLikePattern('john_doe', 'john%'));
        $this->assertTrue($this->translator->matchesLikePattern('john', 'john%'));
        $this->assertFalse($this->translator->matchesLikePattern('jane_doe', 'john%'));
    }
    
    /**
     * @test
     * Validates Requirement 12.7: LIKE pattern matching
     */
    public function testMatchesLikePatternWithWildcardAtStart()
    {
        $this->assertTrue($this->translator->matchesLikePattern('john_doe', '%doe'));
        $this->assertTrue($this->translator->matchesLikePattern('doe', '%doe'));
        $this->assertFalse($this->translator->matchesLikePattern('john_smith', '%doe'));
    }
    
    /**
     * @test
     * Validates Requirement 12.7: LIKE pattern matching
     */
    public function testMatchesLikePatternWithWildcardInMiddle()
    {
        $this->assertTrue($this->translator->matchesLikePattern('john_doe', 'john%doe'));
        $this->assertTrue($this->translator->matchesLikePattern('john_middle_doe', 'john%doe'));
        $this->assertFalse($this->translator->matchesLikePattern('jane_doe', 'john%doe'));
    }
    
    /**
     * @test
     * Validates Requirement 12.7: LIKE pattern with underscore
     */
    public function testMatchesLikePatternWithUnderscore()
    {
        $this->assertTrue($this->translator->matchesLikePattern('john', 'joh_'));
        $this->assertTrue($this->translator->matchesLikePattern('jane', 'jan_'));
        $this->assertFalse($this->translator->matchesLikePattern('johnny', 'joh_'));
    }
    
    /**
     * @test
     * Test LIKE pattern case insensitivity
     */
    public function testMatchesLikePatternCaseInsensitive()
    {
        $this->assertTrue($this->translator->matchesLikePattern('JOHN', 'john%'));
        $this->assertTrue($this->translator->matchesLikePattern('john', 'JOHN%'));
    }
    
    // ========================================
    // ORDER BY Clause Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.2: ORDER BY clause conversion
     */
    public function testParseOrderByWithSingleFieldAscending()
    {
        $orderBy = $this->translator->parseOrderBy("created_at ASC");
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('created_at', $orderBy[0]['field']);
        $this->assertEquals('ASC', $orderBy[0]['direction']);
    }
    
    /**
     * @test
     * Validates Requirement 12.2: ORDER BY clause conversion
     */
    public function testParseOrderByWithSingleFieldDescending()
    {
        $orderBy = $this->translator->parseOrderBy("created_at DESC");
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('created_at', $orderBy[0]['field']);
        $this->assertEquals('DESC', $orderBy[0]['direction']);
    }
    
    /**
     * @test
     * Validates Requirement 12.2: ORDER BY with default direction
     */
    public function testParseOrderByWithDefaultDirection()
    {
        $orderBy = $this->translator->parseOrderBy("username");
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('username', $orderBy[0]['field']);
        $this->assertEquals('ASC', $orderBy[0]['direction']);
    }
    
    /**
     * @test
     * Validates Requirement 12.2: ORDER BY with multiple fields
     */
    public function testParseOrderByWithMultipleFields()
    {
        $orderBy = $this->translator->parseOrderBy("user_id ASC, created_at DESC");
        
        $this->assertCount(2, $orderBy);
        $this->assertEquals('user_id', $orderBy[0]['field']);
        $this->assertEquals('ASC', $orderBy[0]['direction']);
        $this->assertEquals('created_at', $orderBy[1]['field']);
        $this->assertEquals('DESC', $orderBy[1]['direction']);
    }
    
    /**
     * @test
     * Test ORDER BY with ORDER BY keyword prefix
     */
    public function testParseOrderByWithKeywordPrefix()
    {
        $orderBy = $this->translator->parseOrderBy("ORDER BY created_at DESC");
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('created_at', $orderBy[0]['field']);
        $this->assertEquals('DESC', $orderBy[0]['direction']);
    }
    
    /**
     * @test
     * Test ORDER BY with backticks
     */
    public function testParseOrderByWithBackticks()
    {
        $orderBy = $this->translator->parseOrderBy("`created_at` DESC");
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('created_at', $orderBy[0]['field']);
    }
    
    /**
     * @test
     * Test ORDER BY with table prefix
     */
    public function testParseOrderByWithTablePrefix()
    {
        $orderBy = $this->translator->parseOrderBy("transactions.created_at DESC");
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('created_at', $orderBy[0]['field']);
    }
    
    // ========================================
    // LIMIT Clause Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.3: LIMIT clause conversion
     */
    public function testParseLimitWithSimpleValue()
    {
        $limit = $this->translator->parseLimit("10");
        
        $this->assertEquals(10, $limit);
    }
    
    /**
     * @test
     * Validates Requirement 12.3: LIMIT with LIMIT keyword
     */
    public function testParseLimitWithKeywordPrefix()
    {
        $limit = $this->translator->parseLimit("LIMIT 25");
        
        $this->assertEquals(25, $limit);
    }
    
    /**
     * @test
     * Test LIMIT with OFFSET (MySQL format)
     */
    public function testParseLimitWithOffsetMySQLFormat()
    {
        $limit = $this->translator->parseLimit("20, 10");
        
        $this->assertEquals(10, $limit);
    }
    
    /**
     * @test
     * Test LIMIT with OFFSET (standard SQL format)
     */
    public function testParseLimitWithOffsetStandardFormat()
    {
        $limit = $this->translator->parseLimit("10 OFFSET 20");
        
        $this->assertEquals(10, $limit);
    }
    
    // ========================================
    // Aggregate Function Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.4: SUM aggregate function
     */
    public function testTranslateAggregateSumFunction()
    {
        $aggregateFn = $this->translator->translateAggregate("SUM(amount)");
        
        $documents = [
            ['amount' => 100.0],
            ['amount' => 200.0],
            ['amount' => 150.50]
        ];
        
        $result = $aggregateFn($documents);
        
        $this->assertEquals(450.50, $result, '', 0.01);
    }
    
    /**
     * @test
     * Validates Requirement 12.4: COUNT aggregate function
     */
    public function testTranslateAggregateCountFunction()
    {
        $aggregateFn = $this->translator->translateAggregate("COUNT(*)");
        
        $documents = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];
        
        $result = $aggregateFn($documents);
        
        $this->assertEquals(3, $result);
    }
    
    /**
     * @test
     * Validates Requirement 12.4: AVG aggregate function
     */
    public function testTranslateAggregateAvgFunction()
    {
        $aggregateFn = $this->translator->translateAggregate("AVG(amount)");
        
        $documents = [
            ['amount' => 100.0],
            ['amount' => 200.0],
            ['amount' => 300.0]
        ];
        
        $result = $aggregateFn($documents);
        
        $this->assertEquals(200.0, $result, '', 0.01);
    }
    
    /**
     * @test
     * Test MAX aggregate function
     */
    public function testTranslateAggregateMaxFunction()
    {
        $aggregateFn = $this->translator->translateAggregate("MAX(amount)");
        
        $documents = [
            ['amount' => 100.0],
            ['amount' => 300.0],
            ['amount' => 200.0]
        ];
        
        $result = $aggregateFn($documents);
        
        $this->assertEquals(300.0, $result);
    }
    
    /**
     * @test
     * Test MIN aggregate function
     */
    public function testTranslateAggregateMinFunction()
    {
        $aggregateFn = $this->translator->translateAggregate("MIN(amount)");
        
        $documents = [
            ['amount' => 100.0],
            ['amount' => 50.0],
            ['amount' => 200.0]
        ];
        
        $result = $aggregateFn($documents);
        
        $this->assertEquals(50.0, $result);
    }
    
    /**
     * @test
     * Test aggregate with empty documents
     */
    public function testTranslateAggregateSumWithEmptyDocuments()
    {
        $aggregateFn = $this->translator->translateAggregate("SUM(amount)");
        
        $result = $aggregateFn([]);
        
        $this->assertEquals(0.0, $result);
    }
    
    /**
     * @test
     * Test aggregate AVG with empty documents
     */
    public function testTranslateAggregateAvgWithEmptyDocuments()
    {
        $aggregateFn = $this->translator->translateAggregate("AVG(amount)");
        
        $result = $aggregateFn([]);
        
        $this->assertEquals(0.0, $result);
    }
    
    /**
     * @test
     * Test aggregate with missing field
     */
    public function testTranslateAggregateSumWithMissingField()
    {
        $aggregateFn = $this->translator->translateAggregate("SUM(amount)");
        
        $documents = [
            ['amount' => 100.0],
            ['other_field' => 200.0],  // Missing 'amount'
            ['amount' => 150.0]
        ];
        
        $result = $aggregateFn($documents);
        
        $this->assertEquals(250.0, $result, '', 0.01);
    }
    
    // ========================================
    // Date/Time Function Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.8: Date/time functions
     */
    public function testExtractDateComponentYear()
    {
        $timestamp = strtotime('2024-03-15 14:30:00');
        
        $year = $this->translator->extractDateComponent('YEAR', $timestamp);
        
        $this->assertEquals(2024, $year);
    }
    
    /**
     * @test
     * Validates Requirement 12.8: Date/time functions
     */
    public function testExtractDateComponentMonth()
    {
        $timestamp = strtotime('2024-03-15 14:30:00');
        
        $month = $this->translator->extractDateComponent('MONTH', $timestamp);
        
        $this->assertEquals(3, $month);
    }
    
    /**
     * @test
     * Validates Requirement 12.8: Date/time functions
     */
    public function testExtractDateComponentDay()
    {
        $timestamp = strtotime('2024-03-15 14:30:00');
        
        $day = $this->translator->extractDateComponent('DAY', $timestamp);
        
        $this->assertEquals(15, $day);
    }
    
    /**
     * @test
     * Test date component extraction with string date
     */
    public function testExtractDateComponentWithStringDate()
    {
        $year = $this->translator->extractDateComponent('YEAR', '2024-03-15');
        
        $this->assertEquals(2024, $year);
    }
    
    /**
     * @test
     * Validates Requirement 12.8: NOW() function
     */
    public function testTranslateWhereWithNowFunction()
    {
        $conditions = $this->translator->translateWhere("created_at < NOW()");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('created_at', $conditions[0]['field']);
        $this->assertEquals('<', $conditions[0]['operator']);
        $this->assertIsInt($conditions[0]['value']);
        $this->assertGreaterThan(0, $conditions[0]['value']);
    }
    
    /**
     * @test
     * Validates Requirement 12.8: DATE_SUB function
     */
    public function testTranslateWhereWithDateSubFunction()
    {
        $beforeTime = time();
        $conditions = $this->translator->translateWhere("expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $afterTime = time();
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('expires_at', $conditions[0]['field']);
        $this->assertEquals('<', $conditions[0]['operator']);
        $this->assertIsInt($conditions[0]['value']);
        
        // Value should be approximately 1 day ago (between before and after time minus 1 day)
        $expectedMin = $beforeTime - 86400 - 1;  // Allow 1 second before
        $expectedMax = $afterTime - 86400 + 1;   // Allow 1 second after
        $this->assertGreaterThanOrEqual($expectedMin, $conditions[0]['value']);
        $this->assertLessThanOrEqual($expectedMax, $conditions[0]['value']);
    }
    
    // ========================================
    // JOIN Translation Tests
    // ========================================
    
    /**
     * @test
     * Validates Requirement 12.5: JOIN clause conversion
     */
    public function testTranslateJoinWithSimpleJoin()
    {
        $queries = $this->translator->translateJoin("JOIN categories ON transactions.category_id = categories.id");
        
        $this->assertCount(1, $queries);
        $this->assertEquals('join', $queries[0]['type']);
        $this->assertEquals('categories', $queries[0]['table']);
        $this->assertEquals('transactions', $queries[0]['left_table']);
        $this->assertEquals('category_id', $queries[0]['left_field']);
        $this->assertEquals('categories', $queries[0]['right_table']);
        $this->assertEquals('id', $queries[0]['right_field']);
    }
    
    /**
     * @test
     * Test JOIN with different table names
     */
    public function testTranslateJoinWithDifferentTables()
    {
        $queries = $this->translator->translateJoin("JOIN users ON transactions.user_id = users.id");
        
        $this->assertCount(1, $queries);
        $this->assertEquals('users', $queries[0]['table']);
        $this->assertEquals('user_id', $queries[0]['left_field']);
    }
    
    // ========================================
    // Application-Specific SQL Pattern Tests
    // ========================================
    
    /**
     * @test
     * Test user dashboard query pattern
     */
    public function testUserDashboardQueryPattern()
    {
        // Pattern: SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income'
        $conditions = $this->translator->translateWhere("user_id = '123' AND type = 'income'");
        
        $this->assertCount(2, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        $this->assertEquals('123', $conditions[0]['value']);
        $this->assertEquals('type', $conditions[1]['field']);
        $this->assertEquals('income', $conditions[1]['value']);
    }
    
    /**
     * @test
     * Test transaction history query pattern
     */
    public function testTransactionHistoryQueryPattern()
    {
        // Pattern: SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10
        $conditions = $this->translator->translateWhere("user_id = '123'");
        $orderBy = $this->translator->parseOrderBy("created_at DESC");
        $limit = $this->translator->parseLimit("10");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        
        $this->assertCount(1, $orderBy);
        $this->assertEquals('created_at', $orderBy[0]['field']);
        $this->assertEquals('DESC', $orderBy[0]['direction']);
        
        $this->assertEquals(10, $limit);
    }
    
    /**
     * @test
     * Test category filter query pattern
     */
    public function testCategoryFilterQueryPattern()
    {
        // Pattern: SELECT * FROM transactions WHERE user_id = ? AND category_id = ?
        $conditions = $this->translator->translateWhere("user_id = '123' AND category_id = '456'");
        
        $this->assertCount(2, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        $this->assertEquals('category_id', $conditions[1]['field']);
    }
    
    /**
     * @test
     * Test expired OTP cleanup query pattern
     */
    public function testExpiredOtpCleanupQueryPattern()
    {
        // Pattern: DELETE FROM otp_verifications WHERE expires_at < NOW()
        $conditions = $this->translator->translateWhere("expires_at < NOW()");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('expires_at', $conditions[0]['field']);
        $this->assertEquals('<', $conditions[0]['operator']);
        $this->assertIsInt($conditions[0]['value']);
    }
    
    /**
     * @test
     * Test admin panel user search pattern
     */
    public function testAdminUserSearchQueryPattern()
    {
        // Pattern: SELECT * FROM users WHERE username LIKE 'john%'
        $conditions = $this->translator->translateWhere("username LIKE 'john%'");
        
        $this->assertCount(1, $conditions);
        $this->assertEquals('username', $conditions[0]['field']);
        $this->assertEquals('LIKE', $conditions[0]['operator']);
        $this->assertEquals('john%', $conditions[0]['value']);
    }
    
    /**
     * @test
     * Test monthly transaction summary pattern
     */
    public function testMonthlyTransactionSummaryPattern()
    {
        // Pattern: SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = ? AND created_at >= ?
        $conditions = $this->translator->translateWhere("user_id = '123' AND type = 'expense' AND created_at >= 1704067200");
        
        $this->assertCount(3, $conditions);
        $this->assertEquals('user_id', $conditions[0]['field']);
        $this->assertEquals('type', $conditions[1]['field']);
        $this->assertEquals('created_at', $conditions[2]['field']);
        $this->assertEquals('>=', $conditions[2]['operator']);
        $this->assertEquals(1704067200, $conditions[2]['value']);
    }
}

