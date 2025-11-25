<?php

namespace AnimaID\Services;

use AnimaID\Repositories\AnimatorRepository;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * Animator Service
 * Handles animator management operations
 */
class AnimatorService
{
    private AnimatorRepository $animatorRepository;
    private ConfigManager $config;
    private PDO $db;

    public function __construct(
        AnimatorRepository $animatorRepository,
        ConfigManager $config,
        PDO $db
    ) {
        $this->animatorRepository = $animatorRepository;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Get animators with pagination and filters
     */
    public function getAnimators(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $animators = $this->animatorRepository->getPaginated($page, $limit, $filters);
        $total = $this->animatorRepository->count();

        return [
            'animators' => $animators,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get animator by ID with all related data
     */
    public function getAnimatorById(int $animatorId, bool $includeRelations = true): ?array
    {
        if (!$includeRelations) {
            return $this->animatorRepository->findById($animatorId);
        }

        $animator = $this->animatorRepository->findById($animatorId);
        
        if (!$animator) {
            return null;
        }

        $animator['linked_users'] = $this->animatorRepository->getLinkedUsers($animatorId);
        $animator['documents'] = $this->animatorRepository->getDocuments($animatorId);
        $animator['notes'] = $this->animatorRepository->getNotes($animatorId);

        return $animator;
    }

    /**
     * Create a new animator
     */
    public function createAnimator(array $data, int $createdBy): array
    {
        // Validate required fields
        $this->validateAnimatorData($data, true);

        // Check if fiscal code exists
        if (!empty($data['fiscal_code']) && $this->animatorRepository->fiscalCodeExists($data['fiscal_code'])) {
            throw new \Exception('Fiscal code already exists');
        }

        // Prepare animator data
        $animatorData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'status' => $data['status'] ?? 'active',
            'hire_date' => $data['hire_date'] ?? date('Y-m-d'),
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert animator
        $animatorId = $this->animatorRepository->insert($animatorData);

        // Link users if provided
        if (!empty($data['user_ids'])) {
            foreach ($data['user_ids'] as $userId) {
                $this->animatorRepository->linkUser($animatorId, $userId);
            }
        }

        return $this->getAnimatorById($animatorId);
    }

    /**
     * Update animator
     */
    public function updateAnimator(int $animatorId, array $data): array
    {
        // Check if animator exists
        $animator = $this->animatorRepository->findById($animatorId);
        if (!$animator) {
            throw new \Exception('Animator not found');
        }

        // Validate data
        $this->validateAnimatorData($data, false);

        // Check fiscal code uniqueness if changed
        if (isset($data['fiscal_code']) && $data['fiscal_code'] !== $animator['fiscal_code']) {
            if ($this->animatorRepository->fiscalCodeExists($data['fiscal_code'], $animatorId)) {
                throw new \Exception('Fiscal code already exists');
            }
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = [
            'first_name', 'last_name', 'birth_date', 'birth_place', 'fiscal_code',
            'phone', 'email', 'address', 'city', 'postal_code', 'status', 
            'hire_date', 'termination_date', 'notes'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // Update animator
        $this->animatorRepository->update($animatorId, $updateData);

        return $this->getAnimatorById($animatorId);
    }

    /**
     * Delete animator
     */
    public function deleteAnimator(int $animatorId): bool
    {
        if (!$this->animatorRepository->exists($animatorId)) {
            throw new \Exception('Animator not found');
        }

        return $this->animatorRepository->delete($animatorId);
    }

    /**
     * Link user to animator
     */
    public function linkUser(int $animatorId, int $userId): bool
    {
        if (!$this->animatorRepository->exists($animatorId)) {
            throw new \Exception('Animator not found');
        }

        return $this->animatorRepository->linkUser($animatorId, $userId);
    }

    /**
     * Unlink user from animator
     */
    public function unlinkUser(int $animatorId, int $userId): bool
    {
        return $this->animatorRepository->unlinkUser($animatorId, $userId);
    }

    /**
     * Add document to animator
     */
    public function addDocument(int $animatorId, array $documentData, int $uploadedBy): int
    {
        if (!$this->animatorRepository->exists($animatorId)) {
            throw new \Exception('Animator not found');
        }

        $documentData['uploaded_by'] = $uploadedBy;

        return $this->animatorRepository->addDocument($animatorId, $documentData);
    }

    /**
     * Add note to animator
     */
    public function addNote(int $animatorId, string $note, int $createdBy): int
    {
        if (!$this->animatorRepository->exists($animatorId)) {
            throw new \Exception('Animator not found');
        }

        if (empty($note)) {
            throw new \Exception('Note cannot be empty');
        }

        return $this->animatorRepository->addNote($animatorId, $note, $createdBy);
    }

    /**
     * Get active animators
     */
    public function getActiveAnimators(): array
    {
        return $this->animatorRepository->getActive();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $total = $this->animatorRepository->count();
        $byStatus = $this->animatorRepository->countByStatus();

        return [
            'total' => $total,
            'by_status' => $byStatus
        ];
    }

    /**
     * Validate animator data
     */
    private function validateAnimatorData(array $data, bool $isCreate): void
    {
        if ($isCreate) {
            if (empty($data['first_name'])) {
                throw new \Exception('First name is required');
            }
            if (empty($data['last_name'])) {
                throw new \Exception('Last name is required');
            }
        }

        // Validate email format
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format');
            }
        }

        // Validate status
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive', 'suspended'])) {
            throw new \Exception('Invalid status value');
        }

        // Validate dates
        if (isset($data['birth_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['birth_date']);
            if (!$date || $date->format('Y-m-d') !== $data['birth_date']) {
                throw new \Exception('Invalid birth date format (expected Y-m-d)');
            }
        }

        if (isset($data['hire_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['hire_date']);
            if (!$date || $date->format('Y-m-d') !== $data['hire_date']) {
                throw new \Exception('Invalid hire date format (expected Y-m-d)');
            }
        }
    }
}
