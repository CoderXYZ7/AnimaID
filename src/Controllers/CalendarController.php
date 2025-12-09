<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\CalendarService;

/**
 * Calendar Controller
 * Handles calendar and event management endpoints
 */
class CalendarController
{
    private CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * List events
     * GET /api/calendar
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            
            // Extract filters
            $filters = [];
            $allowedFilters = ['status', 'event_type', 'start_date', 'end_date', 'is_public'];
            
            foreach ($allowedFilters as $key) {
                if (isset($params[$key])) {
                    $filters[$key] = $params[$key];
                }
            }

            $result = $this->calendarService->getEvents($page, $limit, $filters);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result['events'],
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
     * Get single event
     * GET /api/calendar/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = (int) $args['id'];
            $event = $this->calendarService->getEvent($eventId);

            if (!$event) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Event not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $event
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create event
     * POST /api/calendar
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $currentUser = $request->getAttribute('user');
            
            $event = $this->calendarService->createEvent($data, $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $event,
                'message' => 'Event created successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update event
     * PUT /api/calendar/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $event = $this->calendarService->updateEvent($eventId, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $event,
                'message' => 'Event updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete event
     * DELETE /api/calendar/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = (int) $args['id'];
            $this->calendarService->deleteEvent($eventId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get event participants
     * GET /api/calendar/{id}/participants
     */
    public function participants(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = (int) $args['id'];
            $participants = $this->calendarService->getParticipants($eventId);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $participants
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Register child for event
     * POST /api/calendar/{id}/register
     */
    public function register(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (empty($data['child_id'])) {
                throw new \Exception('child_id is required');
            }

            $participantId = $this->calendarService->registerChild($eventId, (int)$data['child_id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['participant_id' => $participantId],
                'message' => 'Child registered successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove participant
     * DELETE /api/calendar/{id}/participants/{participantId}
     */
    public function unregister(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = (int) $args['id'];
            $participantId = (int) $args['participantId'];

            $this->calendarService->removeParticipant($eventId, $participantId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Participant removed successfully'
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
