<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Base Repository
 * Provides common CRUD operations for all repositories
 */
abstract class BaseRepository
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find a record by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Find all records with optional filters
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $query = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count records with optional filters
     */
    public function count(array $filters = []): int
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['count'];
    }

    /**
     * Insert a new record
     */
    public function insert(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($query);
        $stmt->execute(array_values($data));

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
                 " WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Delete a record by ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Check if a record exists
     */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * Execute a custom query
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a custom query and return single row
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
}
