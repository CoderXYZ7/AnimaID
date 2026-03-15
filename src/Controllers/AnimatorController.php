<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\AnimatorService;

/**
 * Animator Controller
 * Handles animator management endpoints
 */
class AnimatorController
{
    private AnimatorService $animatorService;

    public function __construct(AnimatorService $animatorService)
    {
        $this->animatorService = $animatorService;
    }

    /**
     * List animators
     * GET /api/animators
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page   = (int) ($params['page']  ?? 1);
            $limit  = (int) ($params['limit'] ?? 20);

            $filters = [];
            $allowedFilters = ['search', 'status'];

            foreach ($allowedFilters as $key) {
                if (isset($params[$key])) {
                    $filters[$key] = $params[$key];
                }
            }

            $result = $this->animatorService->getAnimators($page, $limit, $filters);

            return $this->jsonResponse($response, [
                'success'    => true,
                'data'       => $result['animators'],
                'pagination' => $result['pagination']
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single animator with all related data
     * GET /api/animators/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $animatorId = (int) $args['id'];
            $animator   = $this->animatorService->getAnimatorById($animatorId);

            if (!$animator) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error'   => 'Animator not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $animator
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new animator
     * POST /api/animators
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data        = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            $animator = $this->animatorService->createAnimator($data, (int) $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $animator,
                'message' => 'Animator created successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an animator
     * PUT /api/animators/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $animatorId = (int) $args['id'];
            $data       = json_decode($request->getBody()->getContents(), true) ?? [];

            $animator = $this->animatorService->updateAnimator($animatorId, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $animator,
                'message' => 'Animator updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete an animator
     * DELETE /api/animators/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $animatorId = (int) $args['id'];
            $this->animatorService->deleteAnimator($animatorId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Animator deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Link a user account to an animator
     * POST /api/animators/{id}/users
     */
    public function linkUser(Request $request, Response $response, array $args): Response
    {
        try {
            $animatorId = (int) $args['id'];
            $data       = json_decode($request->getBody()->getContents(), true) ?? [];

            if (empty($data['user_id'])) {
                throw new \Exception('user_id is required');
            }

            $this->animatorService->linkUser($animatorId, (int) $data['user_id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User linked to animator successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Unlink a user account from an animator
     * DELETE /api/animators/{id}/users/{userId}
     */
    public function unlinkUser(Request $request, Response $response, array $args): Response
    {
        try {
            $animatorId = (int) $args['id'];
            $userId     = (int) $args['userId'];

            $this->animatorService->unlinkUser($animatorId, $userId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User unlinked from animator successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add a note to an animator
     * POST /api/animators/{id}/notes
     */
    public function addNote(Request $request, Response $response, array $args): Response
    {
        try {
            $animatorId  = (int) $args['id'];
            $data        = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            if (empty($data['note'])) {
                throw new \Exception('Note content is required');
            }

            $noteId = $this->animatorService->addNote($animatorId, $data['note'], (int) $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => ['note_id' => $noteId],
                'message' => 'Note added successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Helper method to create JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
