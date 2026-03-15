<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\CommunicationService;
use AnimaID\Exceptions\NotFoundException;
use AnimaID\Exceptions\ForbiddenException;
use AnimaID\Exceptions\ValidationException;

/**
 * Communication Controller
 * Handles communication listing, detail, creation, update, deletion, and comments
 */
class CommunicationController
{
    private CommunicationService $communicationService;

    public function __construct(CommunicationService $communicationService)
    {
        $this->communicationService = $communicationService;
    }

    /**
     * List communications
     * GET /api/communications
     *
     * Query parameters:
     *   public=1            → public published list, no auth required
     *   page, limit         → pagination
     *   status, communication_type, priority, target_audience → filters (internal only)
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $isPublic = (int) ($params['public'] ?? 0);

            if ($isPublic) {
                // Public listing – no authentication required
                $page = (int) ($params['page'] ?? 1);
                $limit = (int) ($params['limit'] ?? 10);

                $result = $this->communicationService->getPublicCommunications($page, $limit);
            } else {
                // Internal listing – requires authenticated user (middleware enforces this)
                $page = (int) ($params['page'] ?? 1);
                $limit = (int) ($params['limit'] ?? 20);

                $filters = [];
                $allowedFilters = ['status', 'communication_type', 'priority', 'target_audience'];
                foreach ($allowedFilters as $key) {
                    if (isset($params[$key])) {
                        $filters[$key] = $params[$key];
                    }
                }

                $result = $this->communicationService->getCommunications($page, $limit, $filters);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result['communications'],
                'pagination' => $result['pagination'],
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single communication
     * GET /api/communications/{id}
     *
     * Public communications are accessible without authentication.
     * Private ones require an authenticated user (resolved via middleware).
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $communicationId = (int) $args['id'];
            $currentUser = $request->getAttribute('user');
            $userId = $currentUser['id'] ?? null;
            $isAuthenticated = $currentUser !== null;

            $communication = $this->communicationService->getCommunication(
                $communicationId,
                $isAuthenticated,
                $userId
            );

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $communication,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (ForbiddenException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a communication
     * POST /api/communications
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            $communicationId = $this->communicationService->createCommunication($data, $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['communication_id' => $communicationId],
                'message' => 'Communication created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a communication
     * PUT /api/communications/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $communicationId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            $this->communicationService->updateCommunication($communicationId, $data, $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Communication updated successfully',
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a communication
     * DELETE /api/communications/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $communicationId = (int) $args['id'];

            $this->communicationService->deleteCommunication($communicationId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Communication deleted successfully',
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // -------------------------------------------------------------------------
    // Comments sub-resource
    // -------------------------------------------------------------------------

    /**
     * List comments for a communication
     * GET /api/communications/{id}/comments
     */
    public function getComments(Request $request, Response $response, array $args): Response
    {
        try {
            $communicationId = (int) $args['id'];

            $comments = $this->communicationService->getComments($communicationId);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $comments,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a comment to a communication
     * POST /api/communications/{id}/comments
     *
     * Authentication is optional; anonymous comments are allowed.
     */
    public function addComment(Request $request, Response $response, array $args): Response
    {
        try {
            $communicationId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');
            $userId = $currentUser['id'] ?? null;

            $commentId = $this->communicationService->addComment($communicationId, $data, $userId);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['comment_id' => $commentId],
                'message' => 'Comment added successfully',
            ], 201);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
