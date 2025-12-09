<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\WikiService;

/**
 * Wiki Controller
 * Handles wiki endpoints
 */
class WikiController
{
    private WikiService $wikiService;

    public function __construct(WikiService $wikiService)
    {
        $this->wikiService = $wikiService;
    }

    /**
     * List pages
     * GET /api/wiki/pages
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            
            $filters = [];
            if (isset($params['search'])) $filters['search'] = $params['search'];
            if (isset($params['category_id'])) $filters['category_id'] = $params['category_id'];
            if (isset($params['is_published'])) $filters['is_published'] = $params['is_published'];

            $result = $this->wikiService->getPages($page, $limit, $filters);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result['pages'],
                'pagination' => $result['pagination']
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single page
     * GET /api/wiki/pages/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            
            // Check if ID is numeric, otherwise treat as slug
            if (is_numeric($id)) {
                $page = $this->wikiService->getPage((int)$id);
            } else {
                $page = $this->wikiService->getPageBySlug($id);
            }

            if (!$page) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Page not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $page
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create page
     * POST /api/wiki/pages
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $currentUser = $request->getAttribute('user');
            
            $page = $this->wikiService->createPage($data, $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $page,
                'message' => 'Page created successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update page
     * PUT /api/wiki/pages/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            $currentUser = $request->getAttribute('user');

            $page = $this->wikiService->updatePage($id, $data, $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $page,
                'message' => 'Page updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete page
     * DELETE /api/wiki/pages/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $this->wikiService->deletePage($id);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Page deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get categories
     * GET /api/wiki/categories
     */
    public function categories(Request $request, Response $response): Response
    {
        try {
            $categories = $this->wikiService->getCategories();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
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
