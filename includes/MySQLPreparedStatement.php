<?php

require_once __DIR__ . '/PreparedStatement.php';

/**
 * MySQLPreparedStatement
 * 
 * Concrete implementation of PreparedStatement interface using mysqli_stmt.
 * Provides compatibility with mysqli prepared statement pattern.
 */
class MySQLPreparedStatement implements PreparedStatement {
    private mysqli $conn;
    private string $sql;
    private ?mysqli_stmt $stmt = null;
    private array $params = [];
    private ?mysqli_result $result = null;
    
    /**
     * Constructor
     * @param mysqli $conn MySQL connection
     * @param string $sql SQL query string
     */
    public function __construct(mysqli $conn, string $sql) {
        $this->conn = $conn;
        $this->sql = $sql;
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
        $this->stmt = $this->conn->prepare($this->sql);
        
        if (!$this->stmt) {
            error_log("MySQL prepare failed: " . $this->conn->error);
            return false;
        }
        
        // Bind parameters if any
        if (!empty($this->params)) {
            $types = $this->getBindTypes($this->params);
            $this->stmt->bind_param($types, ...$this->params);
        }
        
        $success = $this->stmt->execute();
        
        if (!$success) {
            error_log("MySQL execute failed: " . $this->stmt->error);
            return false;
        }
        
        // Store result for SELECT queries
        $this->result = $this->stmt->get_result();
        
        return true;
    }
    
    /**
     * Get result set from executed statement
     */
    public function getResult(): array {
        if (!$this->result) {
            return [];
        }
        
        $records = [];
        while ($row = $this->result->fetch_assoc()) {
            $records[] = $row;
        }
        
        return $records;
    }
    
    /**
     * Get the ID of the last inserted record
     */
    public function getInsertId(): string {
        if (!$this->stmt) {
            return '0';
        }
        
        return (string)$this->stmt->insert_id;
    }
    
    /**
     * Get the number of rows affected by the last operation
     */
    public function getAffectedRows(): int {
        if (!$this->stmt) {
            return 0;
        }
        
        return $this->stmt->affected_rows;
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
    
    /**
     * Destructor - close statement
     */
    public function __destruct() {
        if ($this->stmt) {
            $this->stmt->close();
        }
    }
}
