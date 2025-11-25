<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Animator Repository
 * Handles all animator data access operations
 */
class AnimatorRepository extends BaseRepository
{
    protected string $table = 'animators';

    /**
     * Search animators by name or fiscal code
     */
    public function search(string $searchTerm, int $limit = 20, int $offset = 0): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE first_name LIKE ? OR last_name LIKE ? OR fiscal_code LIKE ?
             ORDER BY last_name, first_name
             LIMIT ? OFFSET ?",
            ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%", $limit, $offset]
        );
    }

    /**
     * Get animators with pagination and filters
     */
    public function getPaginated(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR fiscal_code LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }

        $query = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY last_name, first_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->query($query, $params);
    }

    /**
     * Find animator with linked users
     */
    public function findWithLinkedUsers(int $animatorId): ?array
    {
        $animator = $this->findById($animatorId);
        
        if (!$animator) {
            return null;
        }

        $animator['linked_users'] = $this->query(
            "SELECT u.* FROM users u
             INNER JOIN animator_users au ON u.id = au.user_id
             WHERE au.animator_id = ?",
            [$animatorId]
        );

        return $animator;
    }

    /**
     * Find animator with documents
     */
    public function findWithDocuments(int $animatorId): ?array
    {
        $animator = $this->findById($animatorId);
        
        if (!$animator) {
            return null;
        }

        $animator['documents'] = $this->query(
            "SELECT * FROM animator_documents WHERE animator_id = ? ORDER BY uploaded_at DESC",
            [$animatorId]
        );

        return $animator;
    }

    /**
     * Find animator with notes
     */
    public function findWithNotes(int $animatorId): ?array
    {
        $animator = $this->findById($animatorId);
        
        if (!$animator) {
            return null;
        }

        $animator['notes'] = $this->query(
            "SELECT * FROM animator_notes WHERE animator_id = ? ORDER BY created_at DESC",
            [$animatorId]
        );

        return $animator;
    }

    /**
     * Get animator's linked users
     */
    public function getLinkedUsers(int $animatorId): array
    {
        return $this->query(
            "SELECT u.* FROM users u
             INNER JOIN animator_users au ON u.id = au.user_id
             WHERE au.animator_id = ?",
            [$animatorId]
        );
    }

    /**
     * Link user to animator
     */
    public function linkUser(int $animatorId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO animator_users (animator_id, user_id) VALUES (?, ?)"
        );
        return $stmt->execute([$animatorId, $userId]);
    }

    /**
     * Unlink user from animator
     */
    public function unlinkUser(int $animatorId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM animator_users WHERE animator_id = ? AND user_id = ?"
        );
        return $stmt->execute([$animatorId, $userId]);
    }

    /**
     * Get animator's documents
     */
    public function getDocuments(int $animatorId): array
    {
        return $this->query(
            "SELECT * FROM animator_documents WHERE animator_id = ? ORDER BY uploaded_at DESC",
            [$animatorId]
        );
    }

    /**
     * Add document to animator
     */
    public function addDocument(int $animatorId, array $documentData): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO animator_documents (animator_id, document_type, file_name, 
             file_path, uploaded_by, notes) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $animatorId,
            $documentData['document_type'],
            $documentData['file_name'],
            $documentData['file_path'],
            $documentData['uploaded_by'],
            $documentData['notes'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get animator's notes
     */
    public function getNotes(int $animatorId): array
    {
        return $this->query(
            "SELECT * FROM animator_notes WHERE animator_id = ? ORDER BY created_at DESC",
            [$animatorId]
        );
    }

    /**
     * Add note to animator
     */
    public function addNote(int $animatorId, string $note, int $createdBy): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO animator_notes (animator_id, note, created_by) VALUES (?, ?, ?)"
        );

        $stmt->execute([$animatorId, $note, $createdBy]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Check if fiscal code exists
     */
    public function fiscalCodeExists(string $fiscalCode, ?int $excludeAnimatorId = null): bool
    {
        if ($excludeAnimatorId) {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE fiscal_code = ? AND id != ?",
                [$fiscalCode, $excludeAnimatorId]
            );
        } else {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE fiscal_code = ?",
                [$fiscalCode]
            );
        }

        return $result['count'] > 0;
    }

    /**
     * Get active animators
     */
    public function getActive(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY last_name, first_name"
        );
    }

    /**
     * Count animators by status
     */
    public function countByStatus(): array
    {
        return $this->query(
            "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status"
        );
    }
}
