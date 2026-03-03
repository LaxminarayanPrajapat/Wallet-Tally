<?php

namespace WalletTally\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/QueryTranslator.php';

/**
 * Property-Based Tests for Query Translation
 * 
 * Tests universal properties that should hold for SQL to Firestore query translation.
 * 
 * Feature: firebase-migration
 * Property 9: Query Translation Equivalence
 * Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8
 */
class QueryTranslationPropertiesTest extends TestCase
{
    use TestTrait;
    
    private \QueryTranslator $translator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = new \QueryTranslator();
    }
    
    /**
     * Property: WHERE clause translation preserves comparison semantics
     * 
     * For any field, operator, and value, translating a WHERE clause should
     * produce conditions that match the same records as the original SQL.
     * 
     * Validates: Requirement 12.1 (WHERE clause conversion)
     * 
     * @test
     */
    public function testWhereClauseTranslationPreservesComparisonSemantics()
    {
        $this->forAll(
            Generator\elements(['user_id', 'amount', 'created_at', 'type', 'status']),
            Generator\elements(['=', '!=', '<', '<=', '>', '>=']),
            Generator\oneOf(
                Generator\int(),
                Generator\string()->withMaxSize(50),
                Generator\elements(['income', 'expense', 'SUCCESS', 'FAILED'])
            )
        )
        ->then(function ($field, $operator, $value) {
            // Build SQL WHERE clause
            $valueStr = is_string($value) ? "'$value'" : $value;
            $whereClause = "$field $operator $valueStr";
            
            // Translate to Firestore conditions
            $conditions = $this->translator->translateWhere($whereClause);
            
            // Should produce exactly one condition
            $this->assertCount(1, $conditions, 'Single comparison should produce one condition');
            
            $condition = $conditions[0];
            
            // Verify field is preserved
            $this->assertEquals($field, $condition['field'], 'Field name should be preserved');
            
            // Verify operator is mapped correctly
            $expectedOperator = match($operator) {
                '=' => '==',
                '!=' => '!=',
                '<' => '<',
                '<=' => '<=',
                '>' => '>',
                '>=' => '>='
            };
            $this->assertEquals($expectedOperator, $condition['operator'], 'Operator should be mapped correctly');
            
            // Verify value is preserved
            $this->assertEquals($value, $condition['value'], 'Value should be preserved');
        });
    }
    
    /**
     * Property: Multiple AND conditions are correctly parsed
     * 
     * For any set of conditions joined by AND, translation should produce
     * separate conditions that can be applied sequentially.
     * 
     * Validates: Requirement 12.1 (WHERE clause with multiple conditions)
     * 
     * @test
     */
    public function testMultipleAndConditionsAreCorrectlyParsed()
    {
        $this->forAll(
            Generator\int(1, 1000),
            Generator\elements(['income', 'expense']),
            Generator\int(1, 100)
        )
        ->then(function ($userId, $type, $amount) {
            // Build WHERE clause with multiple AND conditions
            $whereClause = "user_id = $userId AND type = '$type' AND amount > $amount";
            
            // Translate
            $conditions = $this->translator->translateWhere($whereClause);
            
            // Should produce three conditions
            $this->assertCount(3, $conditions, 'Three AND conditions should produce three separate conditions');
            
            // Verify first condition (user_id)
            $this->assertEquals('user_id', $conditions[0]['field']);
            $this->assertEquals('==', $conditions[0]['operator']);
            $this->assertEquals($userId, $conditions[0]['value']);
            
            // Verify second condition (type)
            $this->assertEquals('type', $conditions[1]['field']);
            $this->assertEquals('==', $conditions[1]['operator']);
            $this->assertEquals($type, $conditions[1]['value']);
            
            // Verify third condition (amount)
            $this->assertEquals('amount', $conditions[2]['field']);
            $this->assertEquals('>', $conditions[2]['operator']);
            $this->assertEquals($amount, $conditions[2]['value']);
        });
    }
    
    /**
     * Property: ORDER BY clause parsing preserves field and direction
     * 
     * For any field and direction, parsing ORDER BY should correctly
     * extract both components.
     * 
     * Validates: Requirement 12.2 (ORDER BY conversion)
     * 
     * @test
     */
    public function testOrderByClauseParsingPreservesFieldAndDirection()
    {
        $this->forAll(
            Generator\elements(['created_at', 'amount', 'username', 'email']),
            Generator\elements(['ASC', 'DESC'])
        )
        ->then(function ($field, $direction) {
            // Build ORDER BY clause
            $orderByClause = "$field $direction";
            
            // Parse
            $orderBy = $this->translator->parseOrderBy($orderByClause);
            
            // Should produce one ordering
            $this->assertCount(1, $orderBy, 'Single ORDER BY should produce one ordering');
            
            // Verify field and direction
            $this->assertEquals($field, $orderBy[0]['field'], 'Field should be preserved');
            $this->assertEquals($direction, $orderBy[0]['direction'], 'Direction should be preserved');
        });
    }
    
    /**
     * Property: Multiple ORDER BY fields are correctly parsed
     * 
     * For any set of fields with directions, parsing should preserve
     * the order and all components.
     * 
     * Validates: Requirement 12.2 (ORDER BY with multiple fields)
     * 
     * @test
     */
    public function testMultipleOrderByFieldsAreCorrectlyParsed()
    {
        $this->forAll(
            Generator\elements(['user_id', 'category_id', 'type']),
            Generator\elements(['ASC', 'DESC']),
            Generator\elements(['created_at', 'amount']),
            Generator\elements(['ASC', 'DESC'])
        )
        ->then(function ($field1, $dir1, $field2, $dir2) {
            // Build ORDER BY clause with multiple fields
            $orderByClause = "$field1 $dir1, $field2 $dir2";
            
            // Parse
            $orderBy = $this->translator->parseOrderBy($orderByClause);
            
            // Should produce two orderings
            $this->assertCount(2, $orderBy, 'Two ORDER BY fields should produce two orderings');
            
            // Verify first ordering
            $this->assertEquals($field1, $orderBy[0]['field']);
            $this->assertEquals($dir1, $orderBy[0]['direction']);
            
            // Verify second ordering
            $this->assertEquals($field2, $orderBy[1]['field']);
            $this->assertEquals($dir2, $orderBy[1]['direction']);
        });
    }
    
    /**
     * Property: LIMIT clause parsing extracts correct value
     * 
     * For any positive integer, parsing LIMIT should extract the value.
     * 
     * Validates: Requirement 12.3 (LIMIT conversion)
     * 
     * @test
     */
    public function testLimitClauseParsingExtractsCorrectValue()
    {
        $this->forAll(
            Generator\int(1, 1000)
        )
        ->then(function ($limit) {
            // Build LIMIT clause
            $limitClause = (string)$limit;
            
            // Parse
            $parsedLimit = $this->translator->parseLimit($limitClause);
            
            // Should match input
            $this->assertEquals($limit, $parsedLimit, 'LIMIT value should be preserved');
        });
    }
    
    /**
     * Property: SUM aggregate function produces correct calculation
     * 
     * For any set of documents with numeric field, SUM aggregate should
     * calculate the correct total.
     * 
     * Validates: Requirement 12.4 (SUM aggregate conversion)
     * 
     * @test
     */
    public function testSumAggregateFunctionProducesCorrectCalculation()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'amount' => Generator\choose(0, 10000)
                ])
            )->withMaxSize(20)
        )
        ->then(function ($documents) {
            // Translate SUM aggregate
            $sumFunction = $this->translator->translateAggregate('SUM(amount)');
            
            // Calculate using translated function
            $result = $sumFunction($documents);
            
            // Calculate expected sum
            $expectedSum = array_reduce($documents, function($sum, $doc) {
                return $sum + $doc['amount'];
            }, 0);
            
            // Should match
            $this->assertEquals($expectedSum, $result, 'SUM should calculate correct total');
        });
    }
    
    /**
     * Property: COUNT aggregate function produces correct count
     * 
     * For any set of documents, COUNT should return the number of documents.
     * 
     * Validates: Requirement 12.4 (COUNT aggregate conversion)
     * 
     * @test
     */
    public function testCountAggregateFunctionProducesCorrectCount()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'id' => Generator\int(1, 1000)
                ])
            )->withMaxSize(50)
        )
        ->then(function ($documents) {
            // Translate COUNT aggregate
            $countFunction = $this->translator->translateAggregate('COUNT(*)');
            
            // Calculate using translated function
            $result = $countFunction($documents);
            
            // Should match document count
            $this->assertEquals(count($documents), $result, 'COUNT should return document count');
        });
    }
    
    /**
     * Property: AVG aggregate function produces correct average
     * 
     * For any set of documents with numeric field, AVG should calculate
     * the correct average.
     * 
     * Validates: Requirement 12.4 (AVG aggregate conversion)
     * 
     * @test
     */
    public function testAvgAggregateFunctionProducesCorrectAverage()
    {
        $this->forAll(
            Generator\seq(
                Generator\associative([
                    'amount' => Generator\choose(1, 1000)
                ])
            )->withMinSize(1)->withMaxSize(20)
        )
        ->then(function ($documents) {
            // Translate AVG aggregate
            $avgFunction = $this->translator->translateAggregate('AVG(amount)');
            
            // Calculate using translated function
            $result = $avgFunction($documents);
            
            // Calculate expected average
            $sum = array_reduce($documents, function($sum, $doc) {
                return $sum + $doc['amount'];
            }, 0);
            $expectedAvg = $sum / count($documents);
            
            // Should match (with floating point tolerance)
            $this->assertEqualsWithDelta($expectedAvg, $result, 0.01, 'AVG should calculate correct average');
        });
    }
    
    /**
     * Property: LIKE pattern matching works correctly
     * 
     * For any string and pattern, LIKE matching should follow SQL semantics
     * where % matches any sequence and _ matches single character.
     * 
     * Validates: Requirement 12.7 (LIKE operator handling)
     * 
     * @test
     */
    public function testLikePatternMatchingWorksCorrectly()
    {
        $this->forAll(
            Generator\string()->withMaxSize(20),
            Generator\elements(['%test%', 'test%', '%test', 't_st', '%'])
        )
        ->then(function ($value, $pattern) {
            // Test LIKE matching
            $matches = $this->translator->matchesLikePattern($value, $pattern);
            
            // Verify result is boolean
            $this->assertIsBool($matches, 'LIKE matching should return boolean');
            
            // Verify specific patterns
            if ($pattern === '%') {
                $this->assertTrue($matches, '% pattern should match any string');
            }
            
            if ($pattern === '%test%' && str_contains(strtolower($value), 'test')) {
                $this->assertTrue($matches, '%test% should match strings containing "test"');
            }
            
            if ($pattern === 'test%' && str_starts_with(strtolower($value), 'test')) {
                $this->assertTrue($matches, 'test% should match strings starting with "test"');
            }
            
            if ($pattern === '%test' && str_ends_with(strtolower($value), 'test')) {
                $this->assertTrue($matches, '%test should match strings ending with "test"');
            }
        });
    }
    
    /**
     * Property: Date component extraction produces correct values
     * 
     * For any timestamp, extracting date components (YEAR, MONTH, DAY)
     * should produce correct values.
     * 
     * Validates: Requirement 12.8 (date/time function conversion)
     * 
     * @test
     */
    public function testDateComponentExtractionProducesCorrectValues()
    {
        $this->forAll(
            Generator\int(946684800, 2147483647), // 2000-01-01 to 2038-01-19
            Generator\elements(['YEAR', 'MONTH', 'DAY', 'HOUR', 'MINUTE', 'SECOND'])
        )
        ->then(function ($timestamp, $component) {
            // Extract component
            $result = $this->translator->extractDateComponent($component, $timestamp);
            
            // Verify result is integer
            $this->assertIsInt($result, 'Date component should be integer');
            
            // Verify range based on component
            switch ($component) {
                case 'YEAR':
                    $this->assertGreaterThanOrEqual(2000, $result);
                    $this->assertLessThanOrEqual(2038, $result);
                    break;
                case 'MONTH':
                    $this->assertGreaterThanOrEqual(1, $result);
                    $this->assertLessThanOrEqual(12, $result);
                    break;
                case 'DAY':
                    $this->assertGreaterThanOrEqual(1, $result);
                    $this->assertLessThanOrEqual(31, $result);
                    break;
                case 'HOUR':
                    $this->assertGreaterThanOrEqual(0, $result);
                    $this->assertLessThanOrEqual(23, $result);
                    break;
                case 'MINUTE':
                case 'SECOND':
                    $this->assertGreaterThanOrEqual(0, $result);
                    $this->assertLessThanOrEqual(59, $result);
                    break;
            }
        });
    }
    
    /**
     * Property: Field name cleaning removes SQL artifacts
     * 
     * For any field name with backticks, quotes, or table prefixes,
     * cleaning should produce a simple field name.
     * 
     * Validates: Requirement 12.6 (prepared statement parameter conversion)
     * 
     * @test
     */
    public function testFieldNameCleaningRemovesSqlArtifacts()
    {
        $this->forAll(
            Generator\elements(['user_id', 'created_at', 'amount', 'type'])
        )
        ->then(function ($fieldName) {
            // Test various SQL field name formats
            $formats = [
                "`$fieldName`",
                "\"$fieldName\"",
                "'$fieldName'",
                "users.$fieldName",
                "`users`.`$fieldName`",
                "transactions.$fieldName"
            ];
            
            foreach ($formats as $format) {
                // Build WHERE clause with formatted field
                $whereClause = "$format = 123";
                
                // Translate
                $conditions = $this->translator->translateWhere($whereClause);
                
                // Should produce clean field name
                $this->assertCount(1, $conditions);
                $this->assertEquals($fieldName, $conditions[0]['field'], 
                    "Field name should be cleaned from format: $format");
            }
        });
    }
    
    /**
     * Property: Numeric value parsing preserves type
     * 
     * For any numeric value, parsing should preserve integer vs float distinction.
     * 
     * Validates: Requirement 12.6 (parameter type preservation)
     * 
     * @test
     */
    public function testNumericValueParsingPreservesType()
    {
        $this->forAll(
            Generator\oneOf(
                Generator\int(-1000, 1000),
                Generator\float()
            )
        )
        ->then(function ($value) {
            // Build WHERE clause
            $whereClause = "amount = $value";
            
            // Translate
            $conditions = $this->translator->translateWhere($whereClause);
            
            // Verify type is preserved
            $this->assertCount(1, $conditions);
            
            if (is_int($value)) {
                $this->assertIsInt($conditions[0]['value'], 'Integer values should remain integers');
            } else {
                $this->assertIsFloat($conditions[0]['value'], 'Float values should remain floats');
            }
        });
    }
    
    /**
     * Property: Empty WHERE clause produces empty conditions
     * 
     * For an empty WHERE clause, translation should produce no conditions.
     * 
     * Validates: Requirement 12.1 (edge case handling)
     * 
     * @test
     */
    public function testEmptyWhereClauseProducesEmptyConditions()
    {
        $emptyInputs = ['', '   ', "\t", "\n"];
        
        foreach ($emptyInputs as $input) {
            $conditions = $this->translator->translateWhere($input);
            $this->assertEmpty($conditions, 'Empty WHERE clause should produce no conditions');
        }
    }
    
    /**
     * Property: JOIN translation identifies table relationships
     * 
     * For any JOIN clause, translation should identify the tables and
     * join conditions.
     * 
     * Validates: Requirement 12.5 (JOIN conversion)
     * 
     * @test
     */
    public function testJoinTranslationIdentifiesTableRelationships()
    {
        $this->forAll(
            Generator\elements(['transactions', 'categories', 'users']),
            Generator\elements(['categories', 'users', 'feedback']),
            Generator\elements(['category_id', 'user_id', 'id']),
            Generator\elements(['id', 'user_id', 'category_id'])
        )
        ->then(function ($leftTable, $rightTable, $leftField, $rightField) {
            // Skip if tables are the same
            if ($leftTable === $rightTable) {
                $this->assertTrue(true);
                return;
            }
            
            // Build JOIN clause
            $joinClause = "JOIN $rightTable ON $leftTable.$leftField = $rightTable.$rightField";
            
            // Translate
            $queries = $this->translator->translateJoin($joinClause);
            
            // Should produce join specification
            $this->assertNotEmpty($queries, 'JOIN should produce query specification');
            $this->assertEquals('join', $queries[0]['type']);
            $this->assertEquals($rightTable, $queries[0]['table']);
            $this->assertEquals($leftTable, $queries[0]['left_table']);
            $this->assertEquals($leftField, $queries[0]['left_field']);
            $this->assertEquals($rightTable, $queries[0]['right_table']);
            $this->assertEquals($rightField, $queries[0]['right_field']);
        });
    }
}
