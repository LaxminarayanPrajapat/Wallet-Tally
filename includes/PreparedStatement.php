<?php

/**
 * PreparedStatement Interface
 * 
 * Interface for prepared statement execution, providing compatibility
 * with mysqli prepared statement pattern while supporting multiple backends.
 */
interface PreparedStatement {
    /**
     * Bind parameters to the prepared statement
     * @param array $params Array of parameter values
     * @return void
     */
    public function bind(array $params): void;
    
    /**
     * Execute the prepared statement
     * @return bool True on success, false on failure
     */
    public function execute(): bool;
    
    /**
     * Get result set from executed statement
     * @return array Array of records as associative arrays
     */
    public function getResult(): array;
    
    /**
     * Get the ID of the last inserted record
     * @return string Last insert ID
     */
    public function getInsertId(): string;
    
    /**
     * Get the number of rows affected by the last operation
     * @return int Number of affected rows
     */
    public function getAffectedRows(): int;
}
