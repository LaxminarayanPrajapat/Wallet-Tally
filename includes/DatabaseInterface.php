<?php

/**
 * DatabaseInterface
 * 
 * Abstract interface defining database operations independent of underlying implementation.
 * Supports both MySQL and Firebase Firestore backends through concrete implementations.
 */
interface DatabaseInterface {
    /**
     * Establish connection to the database
     * @return bool True if connection successful, false otherwise
     */
    public function connect(): bool;
    
    /**
     * Close database connection
     * @return void
     */
    public function disconnect(): void;
    
    /**
     * Check if database is connected
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool;
    
    /**
     * Insert a new record into a collection/table
     * @param string $collection Collection/table name
     * @param array $data Associative array of field => value pairs
     * @return string|false Document/record ID on success, false on failure
     */
    public function insert(string $collection, array $data): string|false;
    
    /**
     * Update an existing record
     * @param string $collection Collection/table name
     * @param string $id Record ID
     * @param array $data Associative array of field => value pairs to update
     * @return bool True on success, false on failure
     */
    public function update(string $collection, string $id, array $data): bool;
    
    /**
     * Delete a record by ID
     * @param string $collection Collection/table name
     * @param string $id Record ID
     * @return bool True on success, false on failure
     */
    public function delete(string $collection, string $id): bool;
    
    /**
     * Find a single record by ID
     * @param string $collection Collection/table name
     * @param string $id Record ID
     * @return array|null Record data as associative array, or null if not found
     */
    public function findById(string $collection, string $id): ?array;
    
    /**
     * Query records with conditions, ordering, and limit
     * @param string $collection Collection/table name
     * @param array $conditions Associative array of field => value or field => [operator, value]
     * @param array $orderBy Associative array of field => direction ('ASC' or 'DESC')
     * @param int $limit Maximum number of records to return (0 = no limit)
     * @return array Array of records as associative arrays
     */
    public function query(string $collection, array $conditions = [], array $orderBy = [], int $limit = 0): array;
    
    /**
     * Query for a single record matching conditions
     * @param string $collection Collection/table name
     * @param array $conditions Associative array of field => value or field => [operator, value]
     * @return array|null Record data as associative array, or null if not found
     */
    public function queryOne(string $collection, array $conditions): ?array;
    
    /**
     * Count records matching conditions
     * @param string $collection Collection/table name
     * @param array $conditions Associative array of field => value or field => [operator, value]
     * @return int Number of matching records
     */
    public function count(string $collection, array $conditions = []): int;
    
    /**
     * Calculate sum of a numeric field
     * @param string $collection Collection/table name
     * @param string $field Field name to sum
     * @param array $conditions Associative array of field => value or field => [operator, value]
     * @return float Sum of field values
     */
    public function sum(string $collection, string $field, array $conditions = []): float;
    
    /**
     * Calculate average of a numeric field
     * @param string $collection Collection/table name
     * @param string $field Field name to average
     * @param array $conditions Associative array of field => value or field => [operator, value]
     * @return float Average of field values
     */
    public function avg(string $collection, string $field, array $conditions = []): float;
    
    /**
     * Begin a database transaction
     * @return void
     */
    public function beginTransaction(): void;
    
    /**
     * Commit the current transaction
     * @return void
     */
    public function commit(): void;
    
    /**
     * Rollback the current transaction
     * @return void
     */
    public function rollback(): void;
    
    /**
     * Delete multiple records by IDs in a batch operation
     * @param string $collection Collection/table name
     * @param array $ids Array of record IDs to delete
     * @return bool True on success, false on failure
     */
    public function batchDelete(string $collection, array $ids): bool;
    
    /**
     * Insert multiple records in a batch operation
     * @param string $collection Collection/table name
     * @param array $records Array of associative arrays (each representing a record)
     * @return bool True on success, false on failure
     */
    public function batchInsert(string $collection, array $records): bool;
    
    /**
     * Prepare a SQL statement for execution (compatibility method)
     * @param string $sql SQL query string
     * @return PreparedStatement Prepared statement object
     */
    public function prepare(string $sql): PreparedStatement;
}
