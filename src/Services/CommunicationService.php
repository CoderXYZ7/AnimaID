<?php

namespace AnimaID\Services;

use AnimaID\Repositories\CommunicationRepository;
use AnimaID\Config\ConfigManager;
use AnimaID\Exceptions\NotFoundException;
use AnimaID\Exceptions\ForbiddenException;
use AnimaID\Exceptions\ValidationException;

/**
 * Communication Service
 * Handles communication listing, creation, updates, deletion, and comment management
 */
class CommunicationService
{
    private CommunicationRepository $communicationRepository;
    private ConfigManager $config;

    public function __construct(
        CommunicationRepository $communicationRepository,
        ConfigManager $config
    ) {
        $this->communicationRepository = $communicationRepository;
        $this->config = $config;
    }

    /**
     * Get public published communications (no authentication required)
     */
    public function getPublicCommunications(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $communications = $this->communicationRepository->findPublic($limit, $offset);
        $total = $this->communicationRepository->countPublic();

        return [
            'communications' => $communications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    /**
     * Get internal communications with pagination and filters (authentication required)
     */
    public function getCommunications(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $communications = $this->communicationRepository->findAll($filters, $limit, $offset);
        $total = $this->communicationRepository->countWithFilters($filters);

        return [
            'communications' => $communications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    /**
     * Get a single communication, recording the view.
     * Public communications are accessible without authentication;
     * private ones require the caller to confirm the user has permission.
     *
     * @param bool $userAuthenticated Whether the requester is authenticated
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function getCommunication(int $communicationId, bool $userAuthenticated, ?int $userId = null): array
    {
        $communication = $this->communicationRepository->findById($communicationId);

        if (!$communication) {
            throw new NotFoundException('Communication not found');
        }

        // Non-public communications require an authenticated user
        if (!$communication['is_public'] && !$userAuthenticated) {
            throw new ForbiddenException('Insufficient permissions');
        }

        // Record view (anonymous or identified)
        $this->communicationRepository->recordView($communicationId, $userId);

        return $communication;
    }

    /**
     * Create a new communication
     *
     * @throws ValidationException
     */
    public function createCommunication(array $data, int $userId): int
    {
        if (empty($data['title'])) {
            throw new ValidationException('Title is required');
        }

        $communicationData = $data;
        $communicationData['created_by'] = $userId;
        $communicationData['status'] = $communicationData['status'] ?? 'draft';

        // If publishing immediately, set published metadata
        if ($communicationData['status'] === 'published') {
            $communicationData['published_by'] = $userId;
            $communicationData['published_at'] = date('Y-m-d H:i:s');
        }

        return $this->communicationRepository->create($communicationData);
    }

    /**
     * Update an existing communication
     *
     * @throws NotFoundException
     */
    public function updateCommunication(int $communicationId, array $data, int $userId): void
    {
        if (!$this->communicationRepository->exists($communicationId)) {
            throw new NotFoundException('Communication not found');
        }

        $allowedFields = [
            'title', 'content', 'communication_type', 'priority',
            'is_public', 'status', 'target_audience', 'expires_at',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // If transitioning to published status, record who published it
        if (isset($data['status']) && $data['status'] === 'published' && !isset($data['published_at'])) {
            $updateData['published_by'] = $userId;
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }

        $this->communicationRepository->updateById($communicationId, $updateData);
    }

    /**
     * Delete a communication
     *
     * @throws NotFoundException
     */
    public function deleteCommunication(int $communicationId): void
    {
        if (!$this->communicationRepository->exists($communicationId)) {
            throw new NotFoundException('Communication not found');
        }

        $this->communicationRepository->deleteById($communicationId);
    }

    /**
     * Get all comments for a communication
     *
     * @throws NotFoundException
     */
    public function getComments(int $communicationId): array
    {
        if (!$this->communicationRepository->exists($communicationId)) {
            throw new NotFoundException('Communication not found');
        }

        return $this->communicationRepository->findComments($communicationId);
    }

    /**
     * Add a comment to a communication (anonymous comments are allowed)
     *
     * @throws NotFoundException
     */
    public function addComment(int $communicationId, array $commentData, ?int $userId = null): int
    {
        if (!$this->communicationRepository->exists($communicationId)) {
            throw new NotFoundException('Communication not found');
        }

        // Remove fields that should be set by the system, not the caller
        unset($commentData['communication_id'], $commentData['created_by']);

        return $this->communicationRepository->addComment($communicationId, $commentData, $userId);
    }
}
