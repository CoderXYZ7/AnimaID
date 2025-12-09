<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Child Repository
 * Handles all child data access operations
 */
class ChildRepository extends BaseRepository
{
    protected string $table = 'children';

    /**
     * Search children by name or fiscal code
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
     * Get children with pagination and filters
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

        if (!empty($filters['gender'])) {
            $conditions[] = "gender = ?";
            $params[] = $filters['gender'];
        }

        if (!empty($filters['birth_year'])) {
            $conditions[] = "strftime('%Y', birth_date) = ?";
            $params[] = $filters['birth_year'];
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
     * Find child with guardians
     */
    public function findWithGuardians(int $childId): ?array
    {
        $child = $this->findById($childId);
        
        if (!$child) {
            return null;
        }

        $child['guardians'] = $this->query(
            "SELECT * FROM child_guardians WHERE child_id = ? ORDER BY is_primary DESC",
            [$childId]
        );

        return $child;
    }

    /**
     * Find child with documents
     */
    public function findWithDocuments(int $childId): ?array
    {
        $child = $this->findById($childId);
        
        if (!$child) {
            return null;
        }

        $child['documents'] = $this->query(
            "SELECT * FROM child_documents WHERE child_id = ? ORDER BY uploaded_at DESC",
            [$childId]
        );

        return $child;
    }

    /**
     * Find child with notes
     */
    public function findWithNotes(int $childId): ?array
    {
        $child = $this->findById($childId);
        
        if (!$child) {
            return null;
        }

        $child['notes'] = $this->query(
            "SELECT * FROM child_notes WHERE child_id = ? ORDER BY created_at DESC",
            [$childId]
        );

        return $child;
    }

    /**
     * Get child's guardians
     */
    public function getGuardians(int $childId): array
    {
        return $this->query(
            "SELECT * FROM child_guardians WHERE child_id = ? ORDER BY is_primary DESC",
            [$childId]
        );
    }

    /**
     * Add guardian to child
     */
    public function addGuardian(int $childId, array $guardianData): int
    {
        $guardianData['child_id'] = $childId;
        
        $stmt = $this->db->prepare(
            "INSERT INTO child_guardians (child_id, first_name, last_name, relationship, 
             phone, email, is_primary, can_pickup, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $childId,
            $guardianData['first_name'],
            $guardianData['last_name'],
            $guardianData['relationship'],
            $guardianData['phone'] ?? null,
            $guardianData['email'] ?? null,
            $guardianData['is_primary'] ?? 0,
            $guardianData['can_pickup'] ?? 1,
            $guardianData['notes'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get child's documents
     */
    public function getDocuments(int $childId): array
    {
        return $this->query(
            "SELECT * FROM child_documents WHERE child_id = ? ORDER BY uploaded_at DESC",
            [$childId]
        );
    }

    /**
     * Add document to child
     */
    public function addDocument(int $childId, array $documentData): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO child_documents (child_id, document_type, file_name, 
             file_path, uploaded_by, notes) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $childId,
            $documentData['document_type'],
            $documentData['file_name'],
            $documentData['file_path'],
            $documentData['uploaded_by'],
            $documentData['notes'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get child's notes
     */
    public function getNotes(int $childId): array
    {
        return $this->query(
            "SELECT * FROM child_notes WHERE child_id = ? ORDER BY created_at DESC",
            [$childId]
        );
    }

    /**
     * Add note to child
     */
    public function addNote(int $childId, string $note, int $createdBy): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO child_notes (child_id, note, created_by) VALUES (?, ?, ?)"
        );

        $stmt->execute([$childId, $note, $createdBy]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Check if fiscal code exists
     */
    public function fiscalCodeExists(string $fiscalCode, ?int $excludeChildId = null): bool
    {
        if ($excludeChildId) {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE fiscal_code = ? AND id != ?",
                [$fiscalCode, $excludeChildId]
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
     * Get children by age range
     */
    public function getByAgeRange(int $minAge, int $maxAge): array
    {
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE (julianday('now') - julianday(birth_date)) / 365.25 BETWEEN ? AND ?
             ORDER BY birth_date DESC",
            [$minAge, $maxAge]
        );
    }

    /**
     * Count children by gender
     */
    public function countByGender(): array
    {
        return $this->query(
            "SELECT gender, COUNT(*) as count FROM {$this->table} GROUP BY gender"
        );
    }

    /**
     * Get primary guardian
     */
    public function getPrimaryGuardian(int $childId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM child_guardians WHERE child_id = ? AND is_primary = 1 LIMIT 1",
            [$childId]
        );
    }

    /**
     * Get medical info
     */
    public function getMedicalInfo(int $childId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM child_medical WHERE child_id = ?",
            [$childId]
        );
    }
}
