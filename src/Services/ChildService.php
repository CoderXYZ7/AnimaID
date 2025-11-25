<?php

namespace AnimaID\Services;

use AnimaID\Repositories\ChildRepository;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * Child Service
 * Handles child management operations
 */
class ChildService
{
    private ChildRepository $childRepository;
    private ConfigManager $config;
    private PDO $db;

    public function __construct(
        ChildRepository $childRepository,
        ConfigManager $config,
        PDO $db
    ) {
        $this->childRepository = $childRepository;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Get children with pagination and filters
     */
    public function getChildren(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $children = $this->childRepository->getPaginated($page, $limit, $filters);
        $total = $this->childRepository->count();

        return [
            'children' => $children,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get child by ID with all related data
     */
    public function getChildById(int $childId, bool $includeRelations = true): ?array
    {
        if (!$includeRelations) {
            return $this->childRepository->findById($childId);
        }

        $child = $this->childRepository->findById($childId);
        
        if (!$child) {
            return null;
        }

        $child['guardians'] = $this->childRepository->getGuardians($childId);
        $child['documents'] = $this->childRepository->getDocuments($childId);
        $child['notes'] = $this->childRepository->getNotes($childId);

        return $child;
    }

    /**
     * Create a new child
     */
    public function createChild(array $data, int $createdBy): array
    {
        // Validate required fields
        $this->validateChildData($data, true);

        // Check if fiscal code exists
        if (!empty($data['fiscal_code']) && $this->childRepository->fiscalCodeExists($data['fiscal_code'])) {
            throw new \Exception('Fiscal code already exists');
        }

        // Prepare child data
        $childData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'],
            'birth_place' => $data['birth_place'] ?? null,
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert child
        $childId = $this->childRepository->insert($childData);

        // Add guardians if provided
        if (!empty($data['guardians'])) {
            foreach ($data['guardians'] as $guardian) {
                $this->childRepository->addGuardian($childId, $guardian);
            }
        }

        return $this->getChildById($childId);
    }

    /**
     * Update child
     */
    public function updateChild(int $childId, array $data): array
    {
        // Check if child exists
        $child = $this->childRepository->findById($childId);
        if (!$child) {
            throw new \Exception('Child not found');
        }

        // Validate data
        $this->validateChildData($data, false);

        // Check fiscal code uniqueness if changed
        if (isset($data['fiscal_code']) && $data['fiscal_code'] !== $child['fiscal_code']) {
            if ($this->childRepository->fiscalCodeExists($data['fiscal_code'], $childId)) {
                throw new \Exception('Fiscal code already exists');
            }
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = [
            'first_name', 'last_name', 'birth_date', 'birth_place', 
            'fiscal_code', 'gender', 'address', 'city', 'postal_code', 'notes'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // Update child
        $this->childRepository->update($childId, $updateData);

        return $this->getChildById($childId);
    }

    /**
     * Delete child
     */
    public function deleteChild(int $childId): bool
    {
        if (!$this->childRepository->exists($childId)) {
            throw new \Exception('Child not found');
        }

        return $this->childRepository->delete($childId);
    }

    /**
     * Add guardian to child
     */
    public function addGuardian(int $childId, array $guardianData): int
    {
        if (!$this->childRepository->exists($childId)) {
            throw new \Exception('Child not found');
        }

        // Validate guardian data
        if (empty($guardianData['first_name']) || empty($guardianData['last_name'])) {
            throw new \Exception('Guardian first name and last name are required');
        }

        return $this->childRepository->addGuardian($childId, $guardianData);
    }

    /**
     * Add document to child
     */
    public function addDocument(int $childId, array $documentData, int $uploadedBy): int
    {
        if (!$this->childRepository->exists($childId)) {
            throw new \Exception('Child not found');
        }

        $documentData['uploaded_by'] = $uploadedBy;

        return $this->childRepository->addDocument($childId, $documentData);
    }

    /**
     * Add note to child
     */
    public function addNote(int $childId, string $note, int $createdBy): int
    {
        if (!$this->childRepository->exists($childId)) {
            throw new \Exception('Child not found');
        }

        if (empty($note)) {
            throw new \Exception('Note cannot be empty');
        }

        return $this->childRepository->addNote($childId, $note, $createdBy);
    }

    /**
     * Get children by age range
     */
    public function getChildrenByAgeRange(int $minAge, int $maxAge): array
    {
        return $this->childRepository->getByAgeRange($minAge, $maxAge);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $total = $this->childRepository->count();
        $byGender = $this->childRepository->countByGender();

        return [
            'total' => $total,
            'by_gender' => $byGender
        ];
    }

    /**
     * Validate child data
     */
    private function validateChildData(array $data, bool $isCreate): void
    {
        if ($isCreate) {
            if (empty($data['first_name'])) {
                throw new \Exception('First name is required');
            }
            if (empty($data['last_name'])) {
                throw new \Exception('Last name is required');
            }
            if (empty($data['birth_date'])) {
                throw new \Exception('Birth date is required');
            }
        }

        // Validate birth date format
        if (isset($data['birth_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['birth_date']);
            if (!$date || $date->format('Y-m-d') !== $data['birth_date']) {
                throw new \Exception('Invalid birth date format (expected Y-m-d)');
            }
        }

        // Validate gender
        if (isset($data['gender']) && !in_array($data['gender'], ['M', 'F', 'Other'])) {
            throw new \Exception('Invalid gender value');
        }
    }
}
