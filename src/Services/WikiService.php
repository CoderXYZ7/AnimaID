<?php

namespace AnimaID\Services;

use AnimaID\Repositories\WikiRepository;
use AnimaID\Config\ConfigManager;

/**
 * Wiki Service
 * Handles wiki logic, content management, and categorization
 */
class WikiService
{
    private WikiRepository $wikiRepository;
    private ConfigManager $config;

    public function __construct(
        WikiRepository $wikiRepository,
        ConfigManager $config
    ) {
        $this->wikiRepository = $wikiRepository;
        $this->config = $config;
    }

    /**
     * Get pages with pagination and filters
     */
    public function getPages(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        
        $pages = $this->wikiRepository->findPagesWithFilters($filters, $limit, $offset);
        $total = $this->wikiRepository->countPagesWithFilters($filters);
        
        return [
            'pages' => $pages,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get single page by ID
     */
    public function getPage(int $id): ?array
    {
        $page = $this->wikiRepository->findById($id);
        if ($page) {
            $this->wikiRepository->incrementViewCount($id);
        }
        return $page;
    }

    /**
     * Get single page by Slug
     */
    public function getPageBySlug(string $slug): ?array
    {
        $page = $this->wikiRepository->findBySlug($slug);
        if ($page) {
            $this->wikiRepository->incrementViewCount($page['id']);
        }
        return $page;
    }

    /**
     * Create page
     */
    public function createPage(array $data, int $userId): array
    {
        if (empty($data['title'])) {
            throw new \Exception('Title is required');
        }

        $slug = $data['slug'] ?? $this->generateSlug($data['title']);

        // Ensure slug is unique
        if ($this->wikiRepository->findBySlug($slug)) {
            $slug .= '-' . time();
        }

        $pageData = [
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'] ?? '',
            'summary' => $data['summary'] ?? '',
            'category_id' => $data['category_id'] ?? null,
            'is_published' => isset($data['is_published']) ? ($data['is_published'] ? 1 : 0) : 1,
            'is_featured' => isset($data['is_featured']) ? ($data['is_featured'] ? 1 : 0) : 0,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $pageId = $this->wikiRepository->insert($pageData);
        return $this->wikiRepository->findById($pageId);
    }

    /**
     * Update page
     */
    public function updatePage(int $id, array $data, int $userId): array
    {
        $page = $this->wikiRepository->findById($id);
        if (!$page) {
            throw new \Exception('Page not found');
        }

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId
        ];

        $allowedFields = ['title', 'content', 'summary', 'category_id', 'is_published', 'is_featured', 'slug'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Handle boolean fields explicitly if present
        if (isset($data['is_published'])) $updateData['is_published'] = $data['is_published'] ? 1 : 0;
        if (isset($data['is_featured'])) $updateData['is_featured'] = $data['is_featured'] ? 1 : 0;

        if (isset($updateData['slug']) && $updateData['slug'] !== $page['slug']) {
             if ($this->wikiRepository->findBySlug($updateData['slug'])) {
                 throw new \Exception('Slug already exists');
             }
        }

        $this->wikiRepository->update($id, $updateData);
        return $this->wikiRepository->findById($id);
    }

    /**
     * Delete page
     */
    public function deletePage(int $id): void
    {
        if (!$this->wikiRepository->exists($id)) {
            throw new \Exception('Page not found');
        }
        $this->wikiRepository->delete($id);
    }

    /**
     * Get categories
     */
    public function getCategories(): array
    {
        return $this->wikiRepository->getAllCategories();
    }

    /**
     * Helper to generate slug
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        return $slug;
    }
}
