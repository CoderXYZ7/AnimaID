<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\ChildService;

/**
 * Child Controller
 * Handles child management endpoints
 */
class ChildController
{
    private ChildService $childService;

    public function __construct(ChildService $childService)
    {
        $this->childService = $childService;
    }

    /**
     * List children
     * GET /api/children
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page   = (int) ($params['page']  ?? 1);
            $limit  = (int) ($params['limit'] ?? 20);

            $filters = [];
            $allowedFilters = ['search', 'gender', 'birth_year'];

            foreach ($allowedFilters as $key) {
                if (isset($params[$key])) {
                    $filters[$key] = $params[$key];
                }
            }

            $result = $this->childService->getChildren($page, $limit, $filters);

            return $this->jsonResponse($response, [
                'success'    => true,
                'children'   => $result['children'],
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
     * Get single child with all related data
     * GET /api/children/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $childId = (int) $args['id'];
            $child   = $this->childService->getChildById($childId);

            if (!$child) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error'   => 'Child not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $child
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new child
     * POST /api/children
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data        = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            $child = $this->childService->createChild($data, (int) $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $child,
                'message' => 'Child created successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update a child
     * PUT /api/children/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $childId = (int) $args['id'];
            $data    = json_decode($request->getBody()->getContents(), true) ?? [];

            $child = $this->childService->updateChild($childId, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $child,
                'message' => 'Child updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a child
     * DELETE /api/children/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $childId = (int) $args['id'];
            $this->childService->deleteChild($childId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Child deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get guardians for a child
     * GET /api/children/{id}/guardians
     */
    public function guardians(Request $request, Response $response, array $args): Response
    {
        try {
            $childId = (int) $args['id'];
            $child   = $this->childService->getChildById($childId, false);

            if (!$child) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error'   => 'Child not found'
                ], 404);
            }

            // getChildById with relations already includes guardians; fetch with relations
            $childWithRelations = $this->childService->getChildById($childId);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $childWithRelations['guardians'] ?? []
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add a guardian to a child
     * POST /api/children/{id}/guardians
     */
    public function addGuardian(Request $request, Response $response, array $args): Response
    {
        try {
            $childId      = (int) $args['id'];
            $guardianData = json_decode($request->getBody()->getContents(), true) ?? [];

            $guardianId = $this->childService->addGuardian($childId, $guardianData);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => ['guardian_id' => $guardianId],
                'message' => 'Guardian added successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add a note to a child
     * POST /api/children/{id}/notes
     */
    public function addNote(Request $request, Response $response, array $args): Response
    {
        try {
            $childId     = (int) $args['id'];
            $data        = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            if (empty($data['note'])) {
                throw new \Exception('Note content is required');
            }

            $noteId = $this->childService->addNote($childId, $data['note'], (int) $currentUser['id']);

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
