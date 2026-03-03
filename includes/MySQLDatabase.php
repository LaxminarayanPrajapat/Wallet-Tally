<?php

require_once __DIR__ . '/DatabaseInterface.php';
require_once __DIR__ . '/PreparedStatement.php';

/**
 * MySQLDatabase
 * 
 * Concrete implementation of DatabaseInterface using mysqli extension.
 * Wraps existing MySQL operations in the DatabaseInterface contract.
 */
class MySQLDatabase implements DatabaseInterface {
    private ?mysqli $conn = null;
    private bool $inTransaction = false;
    private array $config;
    
    /**
     * Constructor
     * @param array $config Database configuration (host, username, password, database)
     */
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'wallet_tally',
            'charset' => 'utf8mb4'
        ], $config);
    }
    
    /**
     * Establish connection to MySQL database
     */
    public function connect(): bool {
        try {
            $this->conn = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database']
            );
            
            if ($this->conn->connect_error) {
                error_log("MySQL connection failed: " . $this->conn->connect_error);
                return false;
            }
            
            $this->conn->set_charset($this->config['charset']);
            return true;
        } catch (Exception $e) {
            error_log("MySQL connection error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Close database connection
     */
    public function disconnect(): void {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
    
    /**
     * Check if database is connected
     */
    public function isConnected(): bool {
        return $this->conn !== null && $this->conn->ping();
    }
    
    /**
     * Insert a new record
     */
    public function insert(string $collection, array $data): string|false {
        if (!$this->isConnected()) {
            return false;
        }
        
        $fields = array_keys($data);
        $values = array_values($data);
        
        $fieldList = implode(', ', array_map(fn($f) => "`$f`", $fields));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $sql = "INSERT INTO `$collection` ($fieldList) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return false;
        }
        
        $types = $this->getBindTypes($values);
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            error_log("MySQL insert failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $insertId = (string)$stmt->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Update an existing record
     */
    public function update(string $collection, string $id, array $data): bool {
        if (!$this->isConnected()) {
            return false;
        }
        
        $setParts = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "`$field` = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE `$collection` SET $setClause WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return false;
        }
        
        $types = $this->getBindTypes($values);
        $stmt->bind_param($types, ...$values);
        
        $success = $stmt->execute();
        if (!$success) {
            error_log("MySQL update failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Delete a record by ID
     */
    public function delete(string $collection, string $id): bool {
        if (!$this->isConnected()) {
            return false;
        }
        
        $sql = "DELETE FROM `$collection` WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('s', $id);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("MySQL delete failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Find a single record by ID
     */
    public function findById(string $collection, string $id): ?array {
        if (!$this->isConnected()) {
            return null;
        }
        
        $sql = "SELECT * FROM `$collection` WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param('s', $id);
        
        if (!$stmt->execute()) {
            error_log("MySQL query failed: " . $stmt->error);
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        
        $stmt->close();
        return $record ?: null;
    }
    
    /**
     * Query records with conditions, ordering, and limit
     */
    public function query(string $collection, array $conditions = [], array $orderBy = [], int $limit = 0): array {
        if (!$this->isConnected()) {
            return [];
        }
        
        $sql = "SELECT * FROM `$collection`";
        $params = [];
        $types = '';
        
        // Build WHERE clause
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $condition) {
                if (is_array($condition)) {
                    [$operator, $value] = $condition;
                    $whereParts[] = "`$field` $operator ?";
                    $params[] = $value;
                } else {
                    $whereParts[] = "`$field` = ?";
                    $params[] = $condition;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        // Build ORDER BY clause
        if (!empty($orderBy)) {
            $orderParts = [];
            foreach ($orderBy as $field => $direction) {
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $orderParts[] = "`$field` $direction";
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }
        
        // Build LIMIT clause
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return [];
        }
        
        // Bind parameters if any
        if (!empty($params)) {
            $types = $this->getBindTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("MySQL query failed: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $records = [];
        
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        
        $stmt->close();
        return $records;
    }
    
    /**
     * Query for a single record matching conditions
     */
    public function queryOne(string $collection, array $conditions): ?array {
        $results = $this->query($collection, $conditions, [], 1);
        return $results[0] ?? null;
    }
    
    /**
     * Count records matching conditions
     */
    public function count(string $collection, array $conditions = []): int {
        if (!$this->isConnected()) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as count FROM `$collection`";
        $params = [];
        
        // Build WHERE clause
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $condition) {
                if (is_array($condition)) {
                    [$operator, $value] = $condition;
                    $whereParts[] = "`$field` $operator ?";
                    $params[] = $value;
                } else {
                    $whereParts[] = "`$field` = ?";
                    $params[] = $condition;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return 0;
        }
        
        if (!empty($params)) {
            $types = $this->getBindTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("MySQL count failed: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Calculate sum of a numeric field
     */
    public function sum(string $collection, string $field, array $conditions = []): float {
        return $this->aggregate('SUM', $collection, $field, $conditions);
    }
    
    /**
     * Calculate average of a numeric field
     */
    public function avg(string $collection, string $field, array $conditions = []): float {
        return $this->aggregate('AVG', $collection, $field, $conditions);
    }
    
    /**
     * Helper method for aggregate functions
     */
    private function aggregate(string $function, string $collection, string $field, array $conditions): float {
        if (!$this->isConnected()) {
            return 0.0;
        }
        
        $sql = "SELECT $function(`$field`) as result FROM `$collection`";
        $params = [];
        
        // Build WHERE clause
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $f => $condition) {
                if (is_array($condition)) {
                    [$operator, $value] = $condition;
                    $whereParts[] = "`$f` $operator ?";
                    $params[] = $value;
                } else {
                    $whereParts[] = "`$f` = ?";
                    $params[] = $condition;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return 0.0;
        }
        
        if (!empty($params)) {
            $types = $this->getBindTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("MySQL aggregate failed: " . $stmt->error);
            $stmt->close();
            return 0.0;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $stmt->close();
        return (float)($row['result'] ?? 0.0);
    }
    
    /**
     * Begin a database transaction
     */
    public function beginTransaction(): void {
        if ($this->isConnected() && !$this->inTransaction) {
            $this->conn->begin_transaction();
            $this->inTransaction = true;
        }
    }
    
    /**
     * Commit the current transaction
     */
    public function commit(): void {
        if ($this->isConnected() && $this->inTransaction) {
            $this->conn->commit();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Rollback the current transaction
     */
    public function rollback(): void {
        if ($this->isConnected() && $this->inTransaction) {
            $this->conn->rollback();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Delete multiple records by IDs in a batch operation
     */
    public function batchDelete(string $collection, array $ids): bool {
        if (!$this->isConnected() || empty($ids)) {
            return false;
        }
        
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM `$collection` WHERE id IN ($placeholders)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return false;
        }
        
        $types = str_repeat('s', count($ids));
        $stmt->bind_param($types, ...$ids);
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("MySQL batch delete failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Insert multiple records in a batch operation
     */
    public function batchInsert(string $collection, array $records): bool {
        if (!$this->isConnected() || empty($records)) {
            return false;
        }
        
        // Get fields from first record
        $fields = array_keys($records[0]);
        $fieldList = implode(', ', array_map(fn($f) => "`$f`", $fields));
        
        // Build values placeholders
        $recordPlaceholder = '(' . implode(', ', array_fill(0, count($fields), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $recordPlaceholder));
        
        $sql = "INSERT INTO `$collection` ($fieldList) VALUES $allPlaceholders";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return false;
        }
        
        // Flatten all values
        $allValues = [];
        foreach ($records as $record) {
            foreach ($fields as $field) {
                $allValues[] = $record[$field] ?? null;
            }
        }
        
        $types = $this->getBindTypes($allValues);
        $stmt->bind_param($types, ...$allValues);
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("MySQL batch insert failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Prepare a SQL statement for execution
     */
    public function prepare(string $sql): PreparedStatement {
        return new MySQLPreparedStatement($this->conn, $sql);
    }
    
    /**
     * Helper method to determine bind parameter types
     */
    private function getBindTypes(array $values): string {
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 's'; // Default to string
            }
        }
        return $types;
    }
}
