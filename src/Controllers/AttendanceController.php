<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\AttendanceService;

/**
 * Attendance Controller
 * Handles attendance check-in/check-out endpoints
 */
class AttendanceController
{
    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * List attendance records
     * GET /api/attendance
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();

            $eventId       = isset($params['event_id'])       ? (int) $params['event_id']       : null;
            $participantId = isset($params['participant_id']) ? (int) $params['participant_id'] : null;
            $date          = $params['date'] ?? null;

            $records = $this->attendanceService->getRecords($eventId, $participantId, $date);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $records
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record a check-in
     * POST /api/attendance/checkin
     */
    public function checkIn(Request $request, Response $response): Response
    {
        try {
            $data        = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            $this->attendanceService->checkIn($data, (int) $currentUser['id']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Check-in recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Record a check-out for an existing attendance record
     * POST /api/attendance/{id}/checkout
     */
    public function checkOut(Request $request, Response $response, array $args): Response
    {
        try {
            $id          = (int) $args['id'];
            $data        = json_decode($request->getBody()->getContents(), true) ?? [];
            $currentUser = $request->getAttribute('user');

            // Inject staff_id from the authenticated user if not explicitly provided
            if (empty($data['staff_id'])) {
                $data['staff_id'] = $currentUser['id'];
            }

            $this->attendanceService->checkOut($id, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Check-out recorded successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete an attendance record
     * DELETE /api/attendance/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $this->attendanceService->delete($id);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Attendance record deleted successfully'
            ]);
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
