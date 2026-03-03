# Query Translation Property Test

## Overview

This document describes the property-based test for Query Translation Equivalence (Property 9).

**Test File:** `tests/Property/QueryTranslationPropertiesTest.php`

**Feature:** firebase-migration

**Property:** Query Translation Equivalence

**Validates Requirements:** 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8

## What This Test Validates

The QueryTranslationPropertiesTest ensures that the QueryTranslator component correctly converts SQL-style query patterns to Firestore-compatible queries while preserving semantic equivalence.

### Properties Tested

1. **WHERE Clause Translation** (Req 12.1)
   - Comparison operators (=, !=, <, <=, >, >=) are correctly mapped
   - Field names are preserved
   - Values are correctly parsed and typed

2. **Multiple AND Conditions** (Req 12.1)
   - Multiple conditions joined by AND are split correctly
   - Each condition is independently parseable
   - Order is preserved

3. **ORDER BY Parsing** (Req 12.2)
   - Field names are extracted correctly
   - Direction (ASC/DESC) is preserved
   - Multiple ORDER BY fields maintain order

4. **LIMIT Parsing** (Req 12.3)
   - Numeric limit values are extracted correctly
   - Various LIMIT formats are supported

5. **Aggregate Functions** (Req 12.4)
   - SUM calculates correct totals
   - COUNT returns correct document counts
   - AVG calculates correct averages
   - Client-side calculation produces accurate results

6. **LIKE Pattern Matching** (Req 12.7)
   - % wildcard matches any sequence
   - _ wildcard matches single character
   - Pattern matching follows SQL semantics

7. **Date/Time Functions** (Req 12.8)
   - YEAR, MONTH, DAY extraction works correctly
   - HOUR, MINUTE, SECOND extraction works correctly
   - Values are within valid ranges

8. **Field Name Cleaning** (Req 12.6)
   - Backticks are removed
   - Quotes are removed
   - Table prefixes are stripped
   - Clean field names are produced

9. **Type Preservation** (Req 12.6)
   - Integer values remain integers
   - Float values remain floats
   - String values remain strings
   - Type information is not lost

10. **JOIN Translation** (Req 12.5)
    - Table relationships are identified
    - Join conditions are parsed
    - Left and right tables/fields are extracted

## Running the Test

### Basic Execution

```bash
# Run the query translation property test
vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php

# Run with verbose output
vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php --verbose

# Run specific test method
vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php --filter testWhereClauseTranslationPreservesComparisonSemantics
```

### With Custom Iterations

```bash
# Run with 200 iterations per property (default is 100)
ERIS_ITERATIONS=200 vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php

# Run with 500 iterations for thorough testing
ERIS_ITERATIONS=500 vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php
```

### Quick Test (Fewer Iterations)

```bash
# Run with only 10 iterations for quick validation
ERIS_ITERATIONS=10 vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php
```

## Test Data Generation

The test uses Eris generators to create random test data:

- **Field names:** user_id, amount, created_at, type, status, category_id, etc.
- **Operators:** =, !=, <, <=, >, >=
- **Values:** Integers, floats, strings, enums (income/expense, SUCCESS/FAILED)
- **Timestamps:** Unix timestamps from 2000-01-01 to 2038-01-19
- **Patterns:** Various LIKE patterns with % and _ wildcards

## Expected Output

### Successful Run

```
PHPUnit 9.x.x by Sebastian Bergmann and contributors.

..............                                                    14 / 14 (100%)

Time: 00:05.234, Memory: 10.00 MB

OK (14 tests, 1400 assertions)
```

### Failed Property

If a property fails, Eris will show:

```
Failed asserting that two values are equal.
--- Expected
+++ Actual
@@ @@
-'user_id'
+'userId'

Failure after 23 iterations with seed 1234567890
Minimal failing input: ['field' => 'user_id', 'operator' => '=', 'value' => 123]
```

## Debugging Tips

1. **Check the seed:** Failed tests show a seed value - use it to reproduce
2. **Look at minimal input:** Eris shrinks failing inputs to simplest case
3. **Run single test:** Isolate the failing property with --filter
4. **Add debug output:** Use var_dump() or error_log() in the test
5. **Reduce iterations:** Use ERIS_ITERATIONS=10 for faster debugging

## Integration with CI/CD

Add to your CI pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Property Tests
  run: |
    ERIS_ITERATIONS=100 vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php
```

## Coverage

This test provides comprehensive coverage of the QueryTranslator component:

- **14 property tests** covering all major translation scenarios
- **100+ iterations per property** (default) = 1400+ test cases
- **All 8 requirements** (12.1-12.8) are validated
- **Edge cases** like empty inputs, multiple conditions, and type preservation

## Next Steps

After this test passes:

1. Implement FirebaseDatabase class (Task 4)
2. Write property tests for FirebaseDatabase operations
3. Test query translation with real Firebase queries
4. Validate performance meets requirements

## References

- Design Document: `.kiro/specs/firebase-migration/design.md`
- Requirements: `.kiro/specs/firebase-migration/requirements.md` (Section 12)
- QueryTranslator Implementation: `includes/QueryTranslator.php`
- Eris Documentation: https://github.com/giorgiosironi/eris
