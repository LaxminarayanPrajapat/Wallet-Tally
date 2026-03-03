<?php

/**
 * QueryTranslator - Converts SQL-style query patterns to Firestore queries
 * 
 * This class handles the translation of SQL WHERE clauses, JOIN operations,
 * and aggregate functions to Firebase Firestore equivalents.
 */
class QueryTranslator {
    
    /**
     * Operator mapping from SQL to Firestore
     */
    private array $operatorMap = [
        '=' => '==',
        '!=' => '!=',
        '<' => '<',
        '<=' => '<=',
        '>' => '>',
        '>=' => '>=',
        'LIKE' => 'array-contains'
    ];
    
    /**
     * Translate SQL WHERE clause to Firestore conditions
     * 
     * @param string $whereClause SQL WHERE clause (without the WHERE keyword)
     * @return array Array of conditions [field, operator, value]
     */
    public function translateWhere(string $whereClause): array {
        $conditions = [];
        
        // Remove leading/trailing whitespace
        $whereClause = trim($whereClause);
        
        if (empty($whereClause)) {
            return $conditions;
        }
        
        // Split by AND (simple implementation for common patterns)
        $parts = preg_split('/\s+AND\s+/i', $whereClause);
        
        foreach ($parts as $part) {
            $condition = $this->parseCondition(trim($part));
            if ($condition) {
                $conditions[] = $condition;
            }
        }
        
        return $conditions;
    }
    
    /**
     * Parse a single condition from WHERE clause
     * 
     * @param string $condition Single condition string
     * @return array|null Parsed condition [field, operator, value] or null
     */
    private function parseCondition(string $condition): ?array {
        // Handle comparison operators
        foreach (['<=', '>=', '!=', '<>', '=', '<', '>'] as $op) {
            if (strpos($condition, $op) !== false) {
                list($field, $value) = array_map('trim', explode($op, $condition, 2));
                
                // Convert <> to !=
                if ($op === '<>') {
                    $op = '!=';
                }
                
                // Map SQL operator to Firestore operator
                $firestoreOp = $this->operatorMap[$op] ?? $op;
                
                // Clean field name (remove backticks, quotes)
                $field = $this->cleanFieldName($field);
                
                // Parse value
                $value = $this->parseValue($value);
                
                return [
                    'field' => $field,
                    'operator' => $firestoreOp,
                    'value' => $value
                ];
            }
        }
        
        // Handle LIKE operator
        if (stripos($condition, ' LIKE ') !== false) {
            list($field, $pattern) = preg_split('/\s+LIKE\s+/i', $condition, 2);
            $field = $this->cleanFieldName(trim($field));
            $pattern = $this->parseValue(trim($pattern));
            
            // For simple LIKE patterns, we'll need to handle differently
            // Firestore doesn't have native LIKE, so we return a special marker
            return [
                'field' => $field,
                'operator' => 'LIKE',
                'value' => $pattern
            ];
        }
        
        return null;
    }

    /**
     * Clean field name by removing backticks, quotes, and table prefixes
     * 
     * @param string $field Field name to clean
     * @return string Cleaned field name
     */
    private function cleanFieldName(string $field): string {
        // Remove backticks
        $field = str_replace('`', '', $field);
        
        // Remove quotes
        $field = trim($field, '"\'');
        
        // Remove table prefix (e.g., users.id -> id)
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $field = end($parts);
        }
        
        return trim($field);
    }
    
    /**
     * Parse value from SQL condition
     * 
     * @param string $value Value string from SQL
     * @return mixed Parsed value (string, int, float, bool, or DateTime)
     */
    private function parseValue(string $value): mixed {
        $value = trim($value);
        
        // Handle NULL
        if (strtoupper($value) === 'NULL') {
            return null;
        }
        
        // Handle boolean
        if (strtoupper($value) === 'TRUE') {
            return true;
        }
        if (strtoupper($value) === 'FALSE') {
            return false;
        }
        
        // Handle quoted strings
        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            return substr($value, 1, -1);
        }
        
        // Handle date/time functions
        // Check DATE_SUB first (before NOW) since it contains NOW()
        if (preg_match('/DATE_SUB\s*\(\s*NOW\(\)\s*,\s*INTERVAL\s+(\d+)\s+(\w+)\s*\)/i', $value, $matches)) {
            return $this->handleDateSub((int)$matches[1], $matches[2]);
        }
        
        if (stripos($value, 'NOW()') !== false) {
            return time();
        }
        
        // Handle numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Handle DATE_SUB function
     * 
     * @param int $amount Amount to subtract
     * @param string $unit Time unit (DAY, MONTH, YEAR, etc.)
     * @return int Unix timestamp
     */
    private function handleDateSub(int $amount, string $unit): int {
        $unit = strtoupper($unit);
        
        $interval = match($unit) {
            'SECOND' => $amount,
            'MINUTE' => $amount * 60,
            'HOUR' => $amount * 3600,
            'DAY' => $amount * 86400,
            'WEEK' => $amount * 604800,
            'MONTH' => $amount * 2592000, // Approximate (30 days)
            'YEAR' => $amount * 31536000, // Approximate (365 days)
            default => 0
        };
        
        return time() - $interval;
    }
    
    /**
     * Translate SQL JOIN clause to multiple Firestore queries
     * 
     * @param string $joinClause SQL JOIN clause
     * @return array Array of query specifications
     */
    public function translateJoin(string $joinClause): array {
        $queries = [];
        
        // Parse JOIN pattern: JOIN table ON condition
        if (preg_match('/JOIN\s+(\w+)\s+ON\s+(.+)/i', $joinClause, $matches)) {
            $joinTable = $matches[1];
            $onCondition = $matches[2];
            
            // Parse ON condition (e.g., transactions.category_id = categories.id)
            if (preg_match('/(\w+)\.(\w+)\s*=\s*(\w+)\.(\w+)/i', $onCondition, $condMatches)) {
                $queries[] = [
                    'type' => 'join',
                    'table' => $joinTable,
                    'left_table' => $condMatches[1],
                    'left_field' => $condMatches[2],
                    'right_table' => $condMatches[3],
                    'right_field' => $condMatches[4]
                ];
            }
        }
        
        return $queries;
    }

    /**
     * Translate SQL aggregate functions to client-side calculations
     * 
     * @param string $aggregateFunction SQL aggregate function (SUM, COUNT, AVG)
     * @return callable Closure for client-side calculation
     */
    public function translateAggregate(string $aggregateFunction): callable {
        $aggregateFunction = trim($aggregateFunction);
        
        // Parse aggregate pattern: FUNCTION(field)
        if (preg_match('/(SUM|COUNT|AVG|MAX|MIN)\s*\(\s*([^)]+)\s*\)/i', $aggregateFunction, $matches)) {
            $function = strtoupper($matches[1]);
            $field = $this->cleanFieldName($matches[2]);
            
            return match($function) {
                'SUM' => function(array $documents) use ($field): float {
                    return array_reduce($documents, function($sum, $doc) use ($field) {
                        return $sum + ($doc[$field] ?? 0);
                    }, 0.0);
                },
                'COUNT' => function(array $documents): int {
                    return count($documents);
                },
                'AVG' => function(array $documents) use ($field): float {
                    if (empty($documents)) {
                        return 0.0;
                    }
                    $sum = array_reduce($documents, function($sum, $doc) use ($field) {
                        return $sum + ($doc[$field] ?? 0);
                    }, 0.0);
                    return $sum / count($documents);
                },
                'MAX' => function(array $documents) use ($field): mixed {
                    if (empty($documents)) {
                        return null;
                    }
                    $values = array_filter(array_column($documents, $field), function($val) {
                        return $val !== null;
                    });
                    if (empty($values)) {
                        return null;
                    }
                    return max($values);
                },
                'MIN' => function(array $documents) use ($field): mixed {
                    if (empty($documents)) {
                        return null;
                    }
                    $values = array_filter(array_column($documents, $field), function($val) {
                        return $val !== null;
                    });
                    if (empty($values)) {
                        return null;
                    }
                    return min($values);
                },
                default => function(array $documents): int {
                    return count($documents);
                }
            };
        }
        
        // Default: return count
        return function(array $documents): int {
            return count($documents);
        };
    }
    
    /**
     * Extract date/time component from timestamp
     * 
     * @param string $function Date/time function (YEAR, MONTH, DAY, etc.)
     * @param int|string $timestamp Unix timestamp or date string
     * @return int Extracted component
     */
    public function extractDateComponent(string $function, int|string $timestamp): int {
        $function = strtoupper(trim($function));
        
        // Convert to timestamp if string
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        return match($function) {
            'YEAR' => (int)date('Y', $timestamp),
            'MONTH' => (int)date('m', $timestamp),
            'DAY' => (int)date('d', $timestamp),
            'HOUR' => (int)date('H', $timestamp),
            'MINUTE' => (int)date('i', $timestamp),
            'SECOND' => (int)date('s', $timestamp),
            default => $timestamp
        };
    }
    
    /**
     * Check if a value matches a LIKE pattern
     * 
     * @param string $value Value to check
     * @param string $pattern LIKE pattern with % wildcards
     * @return bool True if matches
     */
    public function matchesLikePattern(string $value, string $pattern): bool {
        // Convert SQL LIKE pattern to regex
        // % matches any sequence of characters
        // _ matches any single character
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace(['%', '_'], ['.*', '.'], $pattern);
        $pattern = '/^' . $pattern . '$/i';
        
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Parse ORDER BY clause
     * 
     * @param string $orderByClause SQL ORDER BY clause
     * @return array Array of [field, direction] pairs
     */
    public function parseOrderBy(string $orderByClause): array {
        $orderBy = [];
        
        // Remove ORDER BY keyword if present
        $orderByClause = preg_replace('/^\s*ORDER\s+BY\s+/i', '', $orderByClause);
        
        // Split by comma for multiple fields
        $parts = array_map('trim', explode(',', $orderByClause));
        
        foreach ($parts as $part) {
            // Parse field and direction
            if (preg_match('/^(.+?)\s+(ASC|DESC)$/i', $part, $matches)) {
                $field = $this->cleanFieldName($matches[1]);
                $direction = strtoupper($matches[2]);
            } else {
                $field = $this->cleanFieldName($part);
                $direction = 'ASC'; // Default
            }
            
            $orderBy[] = [
                'field' => $field,
                'direction' => $direction
            ];
        }
        
        return $orderBy;
    }
    
    /**
     * Parse LIMIT clause
     * 
     * @param string $limitClause SQL LIMIT clause
     * @return int Limit value
     */
    public function parseLimit(string $limitClause): int {
        // Remove LIMIT keyword if present
        $limitClause = preg_replace('/^\s*LIMIT\s+/i', '', $limitClause);
        
        // Handle LIMIT with OFFSET (e.g., "10 OFFSET 20" or "20, 10")
        if (preg_match('/^(\d+)\s*,\s*(\d+)$/', $limitClause, $matches)) {
            // MySQL format: LIMIT offset, count
            return (int)$matches[2];
        }
        
        if (preg_match('/^(\d+)\s+OFFSET\s+(\d+)$/i', $limitClause, $matches)) {
            // Standard SQL format: LIMIT count OFFSET offset
            return (int)$matches[1];
        }
        
        // Simple LIMIT
        return (int)$limitClause;
    }
}
