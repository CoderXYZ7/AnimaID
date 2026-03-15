<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\ReportService;

/**
 * Report Controller
 * Handles report endpoints - all routes require admin/coordinator permission
 */
class ReportController
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * List available report types
     * GET /api/reports
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $reports = $this->reportService->getAvailableReports();

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $reports
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attendance report - per-event check-in/check-out counts
     * GET /api/reports/attendance
     */
    public function attendance(Request $request, Response $response): Response
    {
        try {
            $params  = $request->getQueryParams();
            $filters = [];

            if (!empty($params['start_date'])) {
                $filters['start_date'] = $params['start_date'];
            }
            if (!empty($params['end_date'])) {
                $filters['end_date'] = $params['end_date'];
            }

            $result = $this->reportService->getAttendanceReport($filters);

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Children statistics report
     * GET /api/reports/children
     */
    public function children(Request $request, Response $response): Response
    {
        try {
            $result = $this->reportService->getChildrenStats();

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Animator statistics report
     * GET /api/reports/animators
     */
    public function animators(Request $request, Response $response): Response
    {
        try {
            $result = $this->reportService->getAnimatorStats();

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary report - aggregate numbers across all modules
     * GET /api/reports/summary
     */
    public function summary(Request $request, Response $response): Response
    {
        try {
            $result = $this->reportService->getSummary();

            return $this->jsonResponse($response, [
                'success' => true,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage()
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
