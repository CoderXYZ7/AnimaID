<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Wiki Repository
 * Handles wiki pages, categories, and tags
 */
class WikiRepository extends BaseRepository
{
    protected string $table = 'wiki_pages';

    /**
     * Find page by slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Find pages with filters
     */
    public function findPagesWithFilters(array $filters, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT p.*, c.name as category_name, c.color as category_color, u.username as author_name 
                  FROM {$this->table} p
                  LEFT JOIN wiki_categories c ON p.category_id = c.id
                  LEFT JOIN users u ON p.created_by = u.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (p.title LIKE ? OR p.summary LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (isset($filters['is_published'])) {
            $query .= " AND p.is_published = ?";
            $params[] = $filters['is_published'];
        }

        $query .= " ORDER BY p.updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->query($query, $params);
    }

    /**
     * Count pages with filters
     */
    public function countPagesWithFilters(array $filters): int
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} p WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (p.title LIKE ? OR p.summary LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (isset($filters['is_published'])) {
            $query .= " AND p.is_published = ?";
            $params[] = $filters['is_published'];
        }

        $result = $this->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Get all categories
     */
    public function getAllCategories(): array
    {
        return $this->query("SELECT * FROM wiki_categories ORDER BY sort_order, name");
    }

    /**
     * Find category by slug
     */
    public function findCategoryBySlug(string $slug): ?array
    {
        return $this->queryOne(
            "SELECT * FROM wiki_categories WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Create category
     */
    public function createCategory(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO wiki_categories (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE wiki_categories SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(int $pageId): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$pageId]);
    }
}
