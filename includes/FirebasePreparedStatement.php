<?php

require_once __DIR__ . '/PreparedStatement.php';
require_once __DIR__ . '/QueryTranslator.php';

/**
 * FirebasePreparedStatement
 * 
 * Compatibility wrapper for prepared statement pattern with Firebase.
 * Translates SQL queries to Firestore operations.
 */
class FirebasePreparedStatement implements PreparedStatement {
    private $database;
    private string $sql;
    private QueryTranslator $translator;
    private array $params = [];
    private array $result = [];
    private string $lastInsertId = '';
    private int $affectedRows = 0;
    
    /**
     * Constructor
     * @param FirebaseDatabase $database Firebase database instance
     * @param string $sql SQL query string
     * @param QueryTranslator $translator Query translator instance
     */
    public function __construct($database, string $sql, QueryTranslator $translator) {
        $this->database = $database;
        $this->sql = $sql;
        $this->translator = $translator;
    }
    
    /**
     * Bind parameters to the prepared statement
     */
    public function bind(array $params): void {
        $this->params = $params;
    }
    
    /**
     * Execute the prepared statement
     */
    public function execute(): bool {
        try {
            // Parse SQL query type
            $sql = trim($this->sql);
            $sqlUpper = strtoupper($sql);
            
            // Replace parameter placeholders with actual values
            $sql = $this->replaceParameters($sql);
            
            if (str_starts_with($sqlUpper, 'SELECT')) {
                return $this->executeSelect($sql);
            } elseif (str_starts_with($sqlUpper, 'INSERT')) {
                return $this->executeInsert($sql);
            } elseif (str_starts_with($sqlUpper, 'UPDATE')) {
                return $this->executeUpdate($sql);
            } elseif (str_starts_with($sqlUpper, 'DELETE')) {
                return $this->executeDelete($sql);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("FirebasePreparedStatement execute failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Replace parameter placeholders with actual values
     */
    private function replaceParameters(string $sql): string {
        $paramIndex = 0;
        
        return preg_replace_callback('/\?/', function($matches) use (&$paramIndex) {
            if (!isset($this->params[$paramIndex])) {
                return '?';
            }
            
            $value = $this->params[$paramIndex++];
            
            if ($value === null) {
                return 'NULL';
            } elseif (is_string($value)) {
                return "'" . addslashes($value) . "'";
            } elseif (is_bool($value)) {
                return $value ? 'TRUE' : 'FALSE';
            } else {
                return $value;
            }
        }, $sql);
    }
    
    /**
     * Execute SELECT query
     */
    private function executeSelect(string $sql): bool {
        // Parse SELECT query
        $collection = $this->extractCollection($sql);
        $conditions = $this->extractConditions($sql);
        $orderBy = $this->extractOrderBy($sql);
        $limit = $this->extractLimit($sql);
        
        $this->result = $this->database->query($collection, $conditions, $orderBy, $limit);
        $this->affectedRows = count($this->result);
        
        return true;
    }
    
    /**
     * Execute INSERT query
     */
    private function executeInsert(string $sql): bool {
        // Parse INSERT query
        $collection = $this->extractCollection($sql);
        $data = $this->extractInsertData($sql);
        
        $id = $this->database->insert($collection, $data);
        
        if ($id !== false) {
            $this->lastInsertId = $id;
            $this->affectedRows = 1;
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute UPDATE query
     */
    private function executeUpdate(string $sql): bool {
        // Parse UPDATE query
        $collection = $this->extractCollection($sql);
        $data = $this->extractUpdateData($sql);
        $conditions = $this->extractConditions($sql);
        
        // For simplicity, update first matching record
        $records = $this->database->query($collection, $conditions, [], 1);
        
        if (!empty($records)) {
            $id = $records[0]['id'];
            $success = $this->database->update($collection, $id, $data);
            
            if ($success) {
                $this->affectedRows = 1;
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Execute DELETE query
     */
    private function executeDelete(string $sql): bool {
        // Parse DELETE query
        $collection = $this->extractCollection($sql);
        $conditions = $this->extractConditions($sql);
        
        // Query matching records
        $records = $this->database->query($collection, $conditions);
        
        if (!empty($records)) {
            $ids = array_column($records, 'id');
            $success = $this->database->batchDelete($collection, $ids);
            
            if ($success) {
                $this->affectedRows = count($ids);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract collection/table name from SQL
     */
    private function extractCollection(string $sql): string {
        // Match FROM, INTO, or UPDATE table name
        if (preg_match('/(?:FROM|INTO|UPDATE)\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    /**
     * Extract WHERE conditions from SQL
     */
    private function extractConditions(string $sql): array {
        if (!preg_match('/WHERE\s+(.+?)(?:ORDER BY|LIMIT|$)/is', $sql, $matches)) {
            return [];
        }
        
        $whereClause = trim($matches[1]);
        $conditions = [];
        
        // Parse simple conditions (field = value, field > value, etc.)
        $parts = preg_split('/\s+AND\s+/i', $whereClause);
        
        foreach ($parts as $part) {
            foreach (['<=', '>=', '!=', '<>', '=', '<', '>'] as $op) {
                if (strpos($part, $op) !== false) {
                    list($field, $value) = array_map('trim', explode($op, $part, 2));
                    
                    // Clean field name
                    $field = str_replace(['`', '"', "'"], '', $field);
                    
                    // Clean value
                    $value = trim($value, " '\"");
                    
                    // Convert <> to !=
                    if ($op === '<>') {
                        $op = '!=';
                    }
                    
                    if ($op === '=') {
                        $conditions[$field] = $value;
                    } else {
                        $conditions[$field] = [$op, $value];
                    }
                    
                    break;
                }
            }
        }
        
        return $conditions;
    }
    
    /**
     * Extract ORDER BY clause from SQL
     */
    private function extractOrderBy(string $sql): array {
        if (!preg_match('/ORDER BY\s+(.+?)(?:LIMIT|$)/is', $sql, $matches)) {
            return [];
        }
        
        $orderByClause = trim($matches[1]);
        $orderBy = [];
        
        $parts = array_map('trim', explode(',', $orderByClause));
        
        foreach ($parts as $part) {
            if (preg_match('/^`?(\w+)`?\s+(ASC|DESC)$/i', $part, $fieldMatches)) {
                $orderBy[$fieldMatches[1]] = strtoupper($fieldMatches[2]);
            } else {
                $field = str_replace(['`', '"', "'"], '', $part);
                $orderBy[$field] = 'ASC';
            }
        }
        
        return $orderBy;
    }
    
    /**
     * Extract LIMIT clause from SQL
     */
    private function extractLimit(string $sql): int {
        if (preg_match('/LIMIT\s+(\d+)/i', $sql, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }
    
    /**
     * Extract INSERT data from SQL
     */
    private function extractInsertData(string $sql): array {
        // Match INSERT INTO table (field1, field2) VALUES (value1, value2)
        if (!preg_match('/INSERT INTO\s+`?(\w+)`?\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $sql, $matches)) {
            return [];
        }
        
        $fields = array_map('trim', explode(',', $matches[2]));
        $values = array_map('trim', explode(',', $matches[3]));
        
        $data = [];
        
        for ($i = 0; $i < count($fields); $i++) {
            $field = str_replace(['`', '"', "'"], '', $fields[$i]);
            $value = isset($values[$i]) ? trim($values[$i], " '\"") : null;
            
            $data[$field] = $value;
        }
        
        return $data;
    }
    
    /**
     * Extract UPDATE data from SQL
     */
    private function extractUpdateData(string $sql): array {
        // Match UPDATE table SET field1 = value1, field2 = value2
        if (!preg_match('/SET\s+(.+?)(?:WHERE|$)/is', $sql, $matches)) {
            return [];
        }
        
        $setClause = trim($matches[1]);
        $data = [];
        
        $parts = array_map('trim', explode(',', $setClause));
        
        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                list($field, $value) = array_map('trim', explode('=', $part, 2));
                
                $field = str_replace(['`', '"', "'"], '', $field);
                $value = trim($value, " '\"");
                
                $data[$field] = $value;
            }
        }
        
        return $data;
    }
    
    /**
     * Get result set from executed statement
     */
    public function getResult(): array {
        return $this->result;
    }
    
    /**
     * Get the ID of the last inserted record
     */
    public function getInsertId(): string {
        return $this->lastInsertId;
    }
    
    /**
     * Get the number of rows affected by the last operation
     */
    public function getAffectedRows(): int {
        return $this->affectedRows;
    }
}

