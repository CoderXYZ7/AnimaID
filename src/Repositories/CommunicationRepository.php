<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Communication Repository
 * Handles communications, attachments, comments, and read tracking
 */
class CommunicationRepository extends BaseRepository
{
    protected string $table = 'communications';

    /**
     * Find all communications with optional filters and pagination
     */
    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT c.*, u.username as created_by_name
                  FROM {$this->table} c
                  LEFT JOIN users u ON c.created_by = u.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['communication_type'])) {
            $query .= ' AND c.communication_type = ?';
            $params[] = $filters['communication_type'];
        }

        if (!empty($filters['priority'])) {
            $query .= ' AND c.priority = ?';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['target_audience'])) {
            $query .= ' AND c.target_audience LIKE ?';
            $params[] = '%' . $filters['target_audience'] . '%';
        }

        if (isset($filters['is_public'])) {
            $query .= ' AND c.is_public = ?';
            $params[] = $filters['is_public'];
        }

        $query .= ' ORDER BY c.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        return $this->query($query, $params);
    }

    /**
     * Count communications with optional filters
     */
    public function countWithFilters(array $filters = []): int
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} c WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['communication_type'])) {
            $query .= ' AND c.communication_type = ?';
            $params[] = $filters['communication_type'];
        }

        if (!empty($filters['priority'])) {
            $query .= ' AND c.priority = ?';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['target_audience'])) {
            $query .= ' AND c.target_audience LIKE ?';
            $params[] = '%' . $filters['target_audience'] . '%';
        }

        if (isset($filters['is_public'])) {
            $query .= ' AND c.is_public = ?';
            $params[] = $filters['is_public'];
        }

        $result = $this->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Find a single communication with its attachments and approved comments
     */
    public function findById(int $id): ?array
    {
        $communication = $this->queryOne(
            "SELECT c.*, u.username as created_by_name, pu.username as published_by_name
             FROM {$this->table} c
             LEFT JOIN users u ON c.created_by = u.id
             LEFT JOIN users pu ON c.published_by = pu.id
             WHERE c.id = ?",
            [$id]
        );

        if (!$communication) {
            return null;
        }

        // Eager-load attachments
        $communication['attachments'] = $this->query(
            "SELECT ca.*, u.username as uploaded_by_name
             FROM communication_attachments ca
             LEFT JOIN users u ON ca.uploaded_by = u.id
             WHERE ca.communication_id = ?
             ORDER BY ca.created_at",
            [$id]
        );

        // Eager-load approved comments only
        $communication['comments'] = $this->query(
            "SELECT cc.*, u.username as created_by_name, m.username as moderated_by_name
             FROM communication_comments cc
             LEFT JOIN users u ON cc.created_by = u.id
             LEFT JOIN users m ON cc.moderated_by = m.id
             WHERE cc.communication_id = ? AND cc.status = 'approved'
             ORDER BY cc.created_at",
            [$id]
        );

        return $communication;
    }

    /**
     * Find all public published communications (no auth required)
     */
    public function findPublic(int $limit = 10, int $offset = 0): array
    {
        return $this->query(
            "SELECT c.*, u.username as created_by_name
             FROM {$this->table} c
             LEFT JOIN users u ON c.created_by = u.id
             WHERE c.is_public = 1 AND c.status = 'published'
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Count public published communications
     */
    public function countPublic(): int
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE is_public = 1 AND status = 'published'"
        );
        return (int) $result['count'];
    }

    /**
     * Create a communication and return its ID
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Update a communication by ID
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Delete a communication by ID
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Increment view count and record the individual view event
     */
    public function recordView(int $communicationId, ?int $userId): void
    {
        $this->db->prepare(
            "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?"
        )->execute([$communicationId]);

        $stmt = $this->db->prepare(
            "INSERT INTO communication_reads (communication_id, user_id, ip_address, user_agent)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $communicationId,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    /**
     * Get all comments for a communication (all statuses)
     */
    public function findComments(int $communicationId): array
    {
        return $this->query(
            "SELECT cc.*, u.username as created_by_name, m.username as moderated_by_name
             FROM communication_comments cc
             LEFT JOIN users u ON cc.created_by = u.id
             LEFT JOIN users m ON cc.moderated_by = m.id
             WHERE cc.communication_id = ?
             ORDER BY cc.created_at",
            [$communicationId]
        );
    }

    /**
     * Add a comment to a communication
     */
    public function addComment(int $communicationId, array $commentData, ?int $createdBy): int
    {
        $data = array_merge($commentData, [
            'communication_id' => $communicationId,
            'created_by' => $createdBy,
        ]);

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO communication_comments (" . implode(', ', $fields) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->db->lastInsertId();
    }
}
