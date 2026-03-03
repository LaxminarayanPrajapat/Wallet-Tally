# Property-Based Tests

This directory contains property-based tests using the Eris library for PHP. Property-based testing validates universal properties that should hold true across all possible inputs, providing more comprehensive coverage than example-based unit tests.

## What is Property-Based Testing?

Property-based testing focuses on defining properties (invariants) that should always be true, then automatically generating hundreds of test cases to verify those properties. Instead of writing specific examples, you describe the general behavior your code should exhibit.

## Running Property Tests

```bash
# Run all property tests
composer test:property

# Run specific property test file
vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php

# Run with verbose output
vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php --verbose

# Run with specific number of iterations (default is 100)
ERIS_ITERATIONS=200 vendor/bin/phpunit tests/Property/DatabaseAbstractionPropertiesTest.php
```

## Environment Setup

Property tests require both MySQL and Firebase (emulator) to be available:

```bash
# Set up environment variables
export DB_HOST=localhost
export DB_USER=root
export DB_PASS=your_password
export DB_NAME=wallet_tally_test
export FIREBASE_PROJECT_ID=your-project-id
export FIREBASE_CREDENTIALS_PATH=/path/to/credentials.json
export FIREBASE_EMULATOR=true
```

## Test Organization

### DatabaseAbstractionPropertiesTest.php

Tests Property 22: Database Implementation Switching

**Properties tested:**
- Insert operations produce equivalent results across MySQL and Firebase
- Query operations return same results across implementations
- Update operations apply changes equivalently
- Delete operations remove records equivalently
- Count operations return same values

**Validates Requirements:**
- 1.5: Database abstraction layer requires no code changes when switching implementations
- 20.1: Configuration flag switches between MySQL and Firebase
- 20.2: Rollback to MySQL without code changes

### QueryTranslationPropertiesTest.php

Tests Property 9: Query Translation Equivalence

**Properties tested:**
- WHERE clause translation preserves comparison semantics
- Multiple AND conditions are correctly parsed
- ORDER BY clause parsing preserves field and direction
- Multiple ORDER BY fields are correctly parsed
- LIMIT clause parsing extracts correct value
- SUM aggregate function produces correct calculation
- COUNT aggregate function produces correct count
- AVG aggregate function produces correct average
- LIKE pattern matching works correctly
- Date component extraction produces correct values
- Field name cleaning removes SQL artifacts
- Numeric value parsing preserves type
- Empty WHERE clause produces empty conditions
- JOIN translation identifies table relationships

**Validates Requirements:**
- 12.1: WHERE clause conversion to Firestore conditions
- 12.2: ORDER BY conversion to Firestore orderBy()
- 12.3: LIMIT conversion to Firestore limit()
- 12.4: Aggregate functions (SUM, COUNT, AVG) to client-side calculations
- 12.5: JOIN operations to multiple Firebase queries
- 12.6: Prepared statement parameters to Firebase query parameters
- 12.7: Comparison operators (=, !=, <, <=, >, >=, LIKE)
- 12.8: Date/time functions (NOW(), DATE_SUB(), YEAR(), MONTH())

## Property Test Structure

Each property test follows this pattern:

```php
/**
 * Feature: firebase-migration, Property X: Property Name
 * 
 * Description of the property being tested.
 * 
 * Validates: Requirements X.X, Y.Y, Z.Z
 * 
 * @test
 */
public function testPropertyName()
{
    $this->forAll(
        Generator\associative([...]) // Generate random test data
    )
    ->then(function ($data) {
        // Test the property holds for this data
        $this->assertEquals($expected, $actual);
    });
}
```

## Generators Used

- `Generator\string()`: Random strings with configurable size
- `Generator\nat()`: Natural numbers (0, 1, 2, ...)
- `Generator\elements([...])`: Random selection from array
- `Generator\associative([...])`: Random associative arrays
- `Generator\seq(...)`: Random sequences/arrays
- `Generator\oneOf(...)`: Random choice between generators

## Debugging Failed Properties

When a property test fails, Eris will:
1. Show the failing input that violated the property
2. Attempt to shrink the input to find the minimal failing case
3. Display the shrunk input for easier debugging

Example output:
```
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'US'
+'UK'

Failure after 42 iterations with seed 1234567890
Minimal failing input: ['country' => 'UK']
```

## Best Practices

1. **Keep properties simple**: Each test should verify one property
2. **Use appropriate generators**: Match data types to your domain
3. **Clean up after tests**: Remove test data in tearDown()
4. **Document requirements**: Link each property to spec requirements
5. **Run with high iteration counts**: Use at least 100 iterations for confidence

## Firebase Emulator

For testing without consuming production quota, use the Firebase Local Emulator Suite:

```bash
# Install Firebase CLI
npm install -g firebase-tools

# Start emulators
firebase emulators:start --only firestore

# Run tests against emulator
FIREBASE_EMULATOR=true composer test:property
```

## Coverage Goals

- All 33 correctness properties from the design document should have property tests
- Each property test should run minimum 100 iterations
- Property tests complement unit tests (not replace them)
- Focus on universal invariants, not specific examples

## References

- [Eris Documentation](https://github.com/giorgiosironi/eris)
- [Property-Based Testing Introduction](https://hypothesis.works/articles/what-is-property-based-testing/)
- Design Document: `.kiro/specs/firebase-migration/design.md` (Correctness Properties section)
