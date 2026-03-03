#!/bin/bash

# Quick script to run Query Translation Property Test
# Task 3.2: Write property test for query translation equivalence

echo "=========================================="
echo "Query Translation Property Test"
echo "Property 9: Query Translation Equivalence"
echo "Validates: Requirements 12.1-12.8"
echo "=========================================="
echo ""

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Error: vendor directory not found. Run 'composer install' first."
    exit 1
fi

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "Error: PHPUnit not found. Run 'composer install' first."
    exit 1
fi

# Default iterations
ITERATIONS=${ERIS_ITERATIONS:-100}

echo "Running with $ITERATIONS iterations per property..."
echo ""

# Run the test
ERIS_ITERATIONS=$ITERATIONS vendor/bin/phpunit tests/Property/QueryTranslationPropertiesTest.php --verbose

# Check exit code
if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✓ All property tests passed!"
    echo "=========================================="
    exit 0
else
    echo ""
    echo "=========================================="
    echo "✗ Some property tests failed"
    echo "=========================================="
    exit 1
fi
