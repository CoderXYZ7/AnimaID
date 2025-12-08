<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\SpaceService;

/**
 * Space Controller
 * Handles space management and bookings
 */
class SpaceController
{
    private SpaceService $spaceService;

    public function __construct(SpaceService $spaceService)
    {
        $this->spaceService = $spaceService;
    }

    /**
     * List spaces
     * GET /api/spaces
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $spaces = $this->spaceService->getAllSpaces();
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $spaces
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single space
     * GET /api/spaces/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $space = $this->spaceService->getSpace($id);

            if (!$space) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Space not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $space
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create space
     * POST /api/spaces
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $spaceId = $this->spaceService->createSpace($data);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['id' => $spaceId],
                'message' => 'Space created successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update space
     * PUT /api/spaces/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            $this->spaceService->updateSpace($id, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Space updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete space
     * DELETE /api/spaces/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $this->spaceService->deleteSpace($id);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Space deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get space bookings
     * GET /api/spaces/{id}/bookings
     */
    public function getBookings(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $params = $request->getQueryParams();
            $start = $params['start'] ?? null;
            $end = $params['end'] ?? null;

            $bookings = $this->spaceService->getSpaceBookings($id, $start, $end);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $bookings
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create booking
     * POST /api/spaces/bookings
     */
    public function createBooking(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $currentUser = $request->getAttribute('user');
            
            $bookingId = $this->spaceService->createBooking($data, $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['id' => $bookingId],
                'message' => 'Booking created successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete booking
     * DELETE /api/spaces/bookings/{id}
     */
    public function deleteBooking(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $this->spaceService->deleteBooking($id);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
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
