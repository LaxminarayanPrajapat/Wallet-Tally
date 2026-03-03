<?php

require_once __DIR__ . '/DatabaseInterface.php';
require_once __DIR__ . '/PreparedStatement.php';
require_once __DIR__ . '/QueryTranslator.php';
require_once __DIR__ . '/FirebasePreparedStatement.php';

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\WriteBatch;

/**
 * FirebaseDatabase
 * 
 * Concrete implementation of DatabaseInterface using Firebase Firestore.
 * Provides NoSQL database operations with MySQL-compatible interface.
 */
class FirebaseDatabase implements DatabaseInterface {
    private ?FirestoreClient $firestore = null;
    private ?WriteBatch $batch = null;
    private bool $inTransaction = false;
    private QueryTranslator $queryTranslator;
    private array $config;
    private ?DatabaseInterface $fallbackDb = null;
    private bool $connected = false;
    
    /**
     * Constructor
     * @param array $config Firebase configuration (project_id, credentials_path, fallback_enabled)
     * @param DatabaseInterface|null $fallbackDb Optional MySQL fallback database
     */
    public function __construct(array $config = [], ?DatabaseInterface $fallbackDb = null) {
        $this->config = array_merge([
            'project_id' => '',
            'credentials_path' => '',
            'fallback_enabled' => true,
            'log_errors' => true
        ], $config);
        
        $this->queryTranslator = new QueryTranslator();
        $this->fallbackDb = $fallbackDb;
    }
    
    /**
     * Establish connection to Firebase Firestore
     */
    public function connect(): bool {
        try {
            if (empty($this->config['project_id']) || empty($this->config['credentials_path'])) {
                $this->logError('Firebase configuration incomplete', [
                    'project_id' => !empty($this->config['project_id']),
                    'credentials_path' => !empty($this->config['credentials_path'])
                ]);
                return $this->fallbackConnect();
            }
            
            if (!file_exists($this->config['credentials_path'])) {
                $this->logError('Firebase credentials file not found', [
                    'path' => $this->config['credentials_path']
                ]);
                return $this->fallbackConnect();
            }
            
            $this->firestore = new FirestoreClient([
                'projectId' => $this->config['project_id'],
                'keyFilePath' => $this->config['credentials_path']
            ]);
            
            $this->connected = true;
            return true;
            
        } catch (Exception $e) {
            $this->logError('Firebase connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->fallbackConnect();
        }
    }
    
    /**
     * Attempt to connect to fallback database
     */
    private function fallbackConnect(): bool {
        if ($this->config['fallback_enabled'] && $this->fallbackDb) {
            $this->logError('Attempting fallback to MySQL');
            return $this->fallbackDb->connect();
        }
        return false;
    }
    
    /**
     * Close database connection
     */
    public function disconnect(): void {
        $this->firestore = null;
        $this->connected = false;
        
        if ($this->fallbackDb) {
            $this->fallbackDb->disconnect();
        }
    }
    
    /**
     * Check if database is connected
     */
    public function isConnected(): bool {
        if ($this->connected && $this->firestore !== null) {
            return true;
        }
        
        if ($this->fallbackDb) {
            return $this->fallbackDb->isConnected();
        }
        
        return false;
    }

    
    /**
     * Insert a new record into a collection
     */
    public function insert(string $collection, array $data): string|false {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->insert($collection, $data) : false;
            }
            
            // Validate data before write
            if (!$this->validateData($data)) {
                $this->logError('Data validation failed', ['collection' => $collection, 'data' => $data]);
                return false;
            }
            
            // Convert data types for Firestore
            $data = $this->convertToFirestoreTypes($data);
            
            // Add server timestamp for created_at if not present
            if (!isset($data['created_at'])) {
                $data['created_at'] = FieldValue::serverTimestamp();
            }
            
            // If in transaction, add to batch
            if ($this->inTransaction && $this->batch) {
                $docRef = $this->firestore->collection($collection)->newDocument();
                $this->batch->set($docRef, $data);
                return $docRef->id();
            }
            
            // Regular insert
            $docRef = $this->firestore->collection($collection)->add($data);
            
            $this->logSlowQuery('insert', $collection, $startTime);
            
            return $docRef->id();
            
        } catch (Exception $e) {
            $this->logError('Insert failed', [
                'collection' => $collection,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->insert($collection, $data) : false;
        }
    }
    
    /**
     * Update an existing record
     */
    public function update(string $collection, string $id, array $data): bool {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->update($collection, $id, $data) : false;
            }
            
            // Validate data before write
            if (!$this->validateData($data)) {
                $this->logError('Data validation failed', ['collection' => $collection, 'id' => $id]);
                return false;
            }
            
            // Convert data types for Firestore
            $data = $this->convertToFirestoreTypes($data);
            
            // If in transaction, add to batch
            if ($this->inTransaction && $this->batch) {
                $docRef = $this->firestore->collection($collection)->document($id);
                $this->batch->update($docRef, [
                    ['path' => array_keys($data), 'value' => array_values($data)]
                ]);
                return true;
            }
            
            // Regular update
            $docRef = $this->firestore->collection($collection)->document($id);
            $docRef->update([
                ['path' => array_keys($data), 'value' => array_values($data)]
            ]);
            
            $this->logSlowQuery('update', $collection, $startTime);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Update failed', [
                'collection' => $collection,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->update($collection, $id, $data) : false;
        }
    }
    
    /**
     * Delete a record by ID
     */
    public function delete(string $collection, string $id): bool {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->delete($collection, $id) : false;
            }
            
            // If in transaction, add to batch
            if ($this->inTransaction && $this->batch) {
                $docRef = $this->firestore->collection($collection)->document($id);
                $this->batch->delete($docRef);
                return true;
            }
            
            // Regular delete
            $docRef = $this->firestore->collection($collection)->document($id);
            $docRef->delete();
            
            $this->logSlowQuery('delete', $collection, $startTime);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Delete failed', [
                'collection' => $collection,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->delete($collection, $id) : false;
        }
    }
    
    /**
     * Find a single record by ID
     */
    public function findById(string $collection, string $id): ?array {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->findById($collection, $id) : null;
            }
            
            $docRef = $this->firestore->collection($collection)->document($id);
            $snapshot = $docRef->snapshot();
            
            if (!$snapshot->exists()) {
                return null;
            }
            
            $data = $snapshot->data();
            $data['id'] = $snapshot->id();
            
            // Convert Firestore types to PHP types
            $data = $this->convertFromFirestoreTypes($data);
            
            $this->logSlowQuery('findById', $collection, $startTime);
            
            return $data;
            
        } catch (Exception $e) {
            $this->logError('FindById failed', [
                'collection' => $collection,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->findById($collection, $id) : null;
        }
    }

    
    /**
     * Query records with conditions, ordering, and limit
     */
    public function query(string $collection, array $conditions = [], array $orderBy = [], int $limit = 0): array {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->query($collection, $conditions, $orderBy, $limit) : [];
            }
            
            $query = $this->firestore->collection($collection);
            
            // Apply WHERE conditions
            foreach ($conditions as $field => $condition) {
                $query = $this->applyCondition($query, $field, $condition);
            }
            
            // Apply ORDER BY
            foreach ($orderBy as $field => $direction) {
                $direction = strtoupper($direction) === 'DESC' ? 'DESCENDING' : 'ASCENDING';
                $query = $query->orderBy($field, $direction);
            }
            
            // Apply LIMIT
            if ($limit > 0) {
                $query = $query->limit($limit);
            }
            
            // Execute query
            $documents = $query->documents();
            $results = $this->formatResults($documents);
            
            $this->logSlowQuery('query', $collection, $startTime);
            
            return $results;
            
        } catch (Exception $e) {
            $this->logError('Query failed', [
                'collection' => $collection,
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->query($collection, $conditions, $orderBy, $limit) : [];
        }
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
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->count($collection, $conditions) : 0;
            }
            
            $query = $this->firestore->collection($collection);
            
            // Apply WHERE conditions
            foreach ($conditions as $field => $condition) {
                $query = $this->applyCondition($query, $field, $condition);
            }
            
            // Execute query and count
            $documents = $query->documents();
            $count = 0;
            
            foreach ($documents as $document) {
                $count++;
            }
            
            $this->logSlowQuery('count', $collection, $startTime);
            
            return $count;
            
        } catch (Exception $e) {
            $this->logError('Count failed', [
                'collection' => $collection,
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->count($collection, $conditions) : 0;
        }
    }
    
    /**
     * Apply a single condition to a Firestore query
     */
    private function applyCondition($query, string $field, $condition) {
        if (is_array($condition)) {
            // Condition is [operator, value]
            [$operator, $value] = $condition;
            
            // Handle LIKE operator with client-side filtering
            if (strtoupper($operator) === 'LIKE') {
                // Store LIKE pattern for post-processing
                // Firestore doesn't support LIKE, so we'll filter results later
                return $query;
            }
            
            // Map SQL operators to Firestore operators
            $firestoreOp = match($operator) {
                '=' => '==',
                '!=' => '!=',
                '<>' => '!=',
                '<' => '<',
                '<=' => '<=',
                '>' => '>',
                '>=' => '>=',
                default => '=='
            };
            
            $query = $query->where($field, $firestoreOp, $value);
        } else {
            // Simple equality condition
            $query = $query->where($field, '==', $condition);
        }
        
        return $query;
    }
    
    /**
     * Format Firestore documents to match mysqli result structure
     */
    private function formatResults($documents): array {
        $results = [];
        
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                
                // Convert Firestore types to PHP types
                $data = $this->convertFromFirestoreTypes($data);
                
                $results[] = $data;
            }
        }
        
        return $results;
    }

    
    /**
     * Calculate sum of a numeric field (client-side)
     */
    public function sum(string $collection, string $field, array $conditions = []): float {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->sum($collection, $field, $conditions) : 0.0;
            }
            
            // Query all matching documents
            $documents = $this->query($collection, $conditions);
            
            // Calculate sum client-side
            $sum = 0.0;
            foreach ($documents as $doc) {
                $sum += (float)($doc[$field] ?? 0);
            }
            
            $this->logSlowQuery('sum', $collection, $startTime);
            
            return $sum;
            
        } catch (Exception $e) {
            $this->logError('Sum failed', [
                'collection' => $collection,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->sum($collection, $field, $conditions) : 0.0;
        }
    }
    
    /**
     * Calculate average of a numeric field (client-side)
     */
    public function avg(string $collection, string $field, array $conditions = []): float {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->avg($collection, $field, $conditions) : 0.0;
            }
            
            // Query all matching documents
            $documents = $this->query($collection, $conditions);
            
            if (empty($documents)) {
                return 0.0;
            }
            
            // Calculate average client-side
            $sum = 0.0;
            foreach ($documents as $doc) {
                $sum += (float)($doc[$field] ?? 0);
            }
            
            $avg = $sum / count($documents);
            
            $this->logSlowQuery('avg', $collection, $startTime);
            
            return $avg;
            
        } catch (Exception $e) {
            $this->logError('Avg failed', [
                'collection' => $collection,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->avg($collection, $field, $conditions) : 0.0;
        }
    }

    
    /**
     * Begin a database transaction (using Firebase batch writes)
     */
    public function beginTransaction(): void {
        try {
            if (!$this->isFirestoreAvailable()) {
                if ($this->fallbackDb) {
                    $this->fallbackDb->beginTransaction();
                }
                return;
            }
            
            if ($this->inTransaction) {
                $this->logError('Transaction already in progress');
                return;
            }
            
            $this->batch = $this->firestore->batch();
            $this->inTransaction = true;
            
        } catch (Exception $e) {
            $this->logError('Begin transaction failed', [
                'error' => $e->getMessage()
            ]);
            
            if ($this->fallbackDb) {
                $this->fallbackDb->beginTransaction();
            }
        }
    }
    
    /**
     * Commit the current transaction
     */
    public function commit(): void {
        try {
            if (!$this->isFirestoreAvailable()) {
                if ($this->fallbackDb) {
                    $this->fallbackDb->commit();
                }
                return;
            }
            
            if (!$this->inTransaction || !$this->batch) {
                $this->logError('No transaction in progress');
                return;
            }
            
            // Execute batch atomically
            $this->batch->commit();
            
            $this->batch = null;
            $this->inTransaction = false;
            
        } catch (Exception $e) {
            $this->logError('Commit failed', [
                'error' => $e->getMessage()
            ]);
            
            // Attempt rollback
            $this->rollback();
            
            if ($this->fallbackDb) {
                $this->fallbackDb->commit();
            }
        }
    }
    
    /**
     * Rollback the current transaction
     */
    public function rollback(): void {
        try {
            if (!$this->isFirestoreAvailable()) {
                if ($this->fallbackDb) {
                    $this->fallbackDb->rollback();
                }
                return;
            }
            
            if (!$this->inTransaction) {
                return;
            }
            
            // Discard batch (Firebase doesn't support explicit rollback)
            $this->batch = null;
            $this->inTransaction = false;
            
        } catch (Exception $e) {
            $this->logError('Rollback failed', [
                'error' => $e->getMessage()
            ]);
            
            if ($this->fallbackDb) {
                $this->fallbackDb->rollback();
            }
        }
    }

    
    /**
     * Delete multiple records by IDs in batch operations
     * Handles Firebase 500 operation limit per batch
     */
    public function batchDelete(string $collection, array $ids): bool {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->batchDelete($collection, $ids) : false;
            }
            
            if (empty($ids)) {
                return true;
            }
            
            // Split into batches of 500 (Firebase limit)
            $batches = array_chunk($ids, 500);
            
            foreach ($batches as $batchIds) {
                $batch = $this->firestore->batch();
                
                foreach ($batchIds as $id) {
                    $docRef = $this->firestore->collection($collection)->document($id);
                    $batch->delete($docRef);
                }
                
                $batch->commit();
            }
            
            $this->logSlowQuery('batchDelete', $collection, $startTime);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Batch delete failed', [
                'collection' => $collection,
                'count' => count($ids),
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->batchDelete($collection, $ids) : false;
        }
    }
    
    /**
     * Insert multiple records in batch operations
     * Handles Firebase 500 operation limit per batch
     */
    public function batchInsert(string $collection, array $records): bool {
        $startTime = microtime(true);
        
        try {
            if (!$this->isFirestoreAvailable()) {
                return $this->fallbackDb ? $this->fallbackDb->batchInsert($collection, $records) : false;
            }
            
            if (empty($records)) {
                return true;
            }
            
            // Split into batches of 500 (Firebase limit)
            $batches = array_chunk($records, 500);
            
            foreach ($batches as $batchRecords) {
                $batch = $this->firestore->batch();
                
                foreach ($batchRecords as $record) {
                    // Validate and convert data
                    if (!$this->validateData($record)) {
                        continue;
                    }
                    
                    $record = $this->convertToFirestoreTypes($record);
                    
                    // Add server timestamp if not present
                    if (!isset($record['created_at'])) {
                        $record['created_at'] = FieldValue::serverTimestamp();
                    }
                    
                    $docRef = $this->firestore->collection($collection)->newDocument();
                    $batch->set($docRef, $record);
                }
                
                $batch->commit();
            }
            
            $this->logSlowQuery('batchInsert', $collection, $startTime);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Batch insert failed', [
                'collection' => $collection,
                'count' => count($records),
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackDb ? $this->fallbackDb->batchInsert($collection, $records) : false;
        }
    }

    
    /**
     * Prepare a SQL statement for execution (compatibility method)
     */
    public function prepare(string $sql): PreparedStatement {
        // For Firebase, we'll create a compatibility wrapper
        return new FirebasePreparedStatement($this, $sql, $this->queryTranslator);
    }
    
    /**
     * Check if Firestore is available
     */
    private function isFirestoreAvailable(): bool {
        return $this->connected && $this->firestore !== null;
    }
    
    /**
     * Validate data before write operations
     */
    private function validateData(array $data): bool {
        // Basic validation - can be extended
        if (empty($data)) {
            return false;
        }
        
        // Check for invalid field names (Firestore restrictions)
        foreach (array_keys($data) as $field) {
            if (empty($field) || !is_string($field)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Convert PHP data types to Firestore types
     */
    private function convertToFirestoreTypes(array $data): array {
        $converted = [];
        
        foreach ($data as $key => $value) {
            if ($value === null) {
                $converted[$key] = null;
            } elseif (is_bool($value)) {
                $converted[$key] = $value;
            } elseif (is_int($value)) {
                $converted[$key] = $value;
            } elseif (is_float($value)) {
                $converted[$key] = $value;
            } elseif (is_string($value)) {
                // Check if it's a timestamp field
                if (in_array($key, ['created_at', 'updated_at', 'expires_at', 'token_expiry', 'last_resend_at'])) {
                    // Convert to Firestore Timestamp if it's a Unix timestamp
                    if (is_numeric($value)) {
                        $converted[$key] = new \Google\Cloud\Core\Timestamp(new \DateTime('@' . $value));
                    } else {
                        $converted[$key] = $value;
                    }
                } else {
                    $converted[$key] = $value;
                }
            } elseif (is_array($value)) {
                $converted[$key] = $this->convertToFirestoreTypes($value);
            } else {
                $converted[$key] = $value;
            }
        }
        
        return $converted;
    }
    
    /**
     * Convert Firestore types to PHP types
     */
    private function convertFromFirestoreTypes(array $data): array {
        $converted = [];
        
        foreach ($data as $key => $value) {
            if ($value instanceof \Google\Cloud\Core\Timestamp) {
                // Convert Firestore Timestamp to Unix timestamp
                $converted[$key] = $value->get()->getTimestamp();
            } elseif (is_array($value)) {
                $converted[$key] = $this->convertFromFirestoreTypes($value);
            } else {
                $converted[$key] = $value;
            }
        }
        
        return $converted;
    }
    
    /**
     * Log error with structured format
     */
    private function logError(string $message, array $context = []): void {
        if (!$this->config['log_errors']) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'component' => 'FirebaseDatabase',
            'message' => $message,
            'context' => $context
        ];
        
        error_log(json_encode($logEntry));
    }
    
    /**
     * Log slow queries (exceeding 1 second)
     */
    private function logSlowQuery(string $operation, string $collection, float $startTime): void {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        if ($executionTime > 1000) {
            $this->logError('Slow query detected', [
                'operation' => $operation,
                'collection' => $collection,
                'execution_time_ms' => $executionTime
            ]);
        }
    }
}
