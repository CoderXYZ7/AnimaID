<?php

namespace AnimaID\Services;

use PDO;

/**
 * Report Service
 * Runs cross-table reporting queries for attendance, children, and animators
 */
class ReportService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get the catalogue of available reports
     */
    public function getAvailableReports(): array
    {
        return [
            [
                'id'          => 'attendance',
                'name'        => 'Attendance Report',
                'description' => 'Report on child attendance for events',
                'endpoint'    => '/api/reports/attendance'
            ],
            [
                'id'          => 'children',
                'name'        => 'Children Report',
                'description' => 'Report on registered children',
                'endpoint'    => '/api/reports/children'
            ],
            [
                'id'          => 'animators',
                'name'        => 'Animators Report',
                'description' => 'Report on registered animators',
                'endpoint'    => '/api/reports/animators'
            ]
        ];
    }

    /**
     * Attendance report: per-event check-in/check-out counts within a date range
     */
    public function getAttendanceReport(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate   = $filters['end_date']   ?? date('Y-m-t');

        $stmt = $this->db->prepare("
            SELECT
                e.title as event_title,
                e.start_date,
                e.end_date,
                COUNT(CASE WHEN ar.check_in_time IS NOT NULL THEN 1 END) as checked_in,
                COUNT(CASE WHEN ar.check_out_time IS NOT NULL THEN 1 END) as checked_out,
                COUNT(ar.id) as total_registered
            FROM calendar_events e
            LEFT JOIN attendance_records ar ON e.id = ar.event_id
            WHERE e.start_date BETWEEN ? AND ?
            GROUP BY e.id, e.title, e.start_date, e.end_date
            ORDER BY e.start_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'report_type' => 'attendance',
            'period'      => ['start' => $startDate, 'end' => $endDate],
            'data'        => $data
        ];
    }

    /**
     * Children stats report: totals and age-bracket breakdown
     */
    public function getChildrenStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_children,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_children,
                AVG(DATE('now') - DATE(birth_date)) as avg_age_years,
                COUNT(CASE WHEN DATE('now', '-3 years') > DATE(birth_date) THEN 1 END) as under_3,
                COUNT(CASE WHEN DATE('now', '-6 years') > DATE(birth_date) AND DATE('now', '-3 years') <= DATE(birth_date) THEN 1 END) as age_3_6,
                COUNT(CASE WHEN DATE('now', '-12 years') > DATE(birth_date) AND DATE('now', '-6 years') <= DATE(birth_date) THEN 1 END) as age_6_12
            FROM children
        ");
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'report_type' => 'children',
            'data'        => $data ?: []
        ];
    }

    /**
     * Animator stats report: totals, active count, and average years of service
     */
    public function getAnimatorStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_animators,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_animators,
                COUNT(CASE WHEN hire_date IS NOT NULL THEN 1 END) as hired_animators,
                AVG(CASE WHEN hire_date IS NOT NULL
                    THEN (julianday('now') - julianday(hire_date)) / 365.25
                    END) as avg_years_service
            FROM animators
        ");
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'report_type' => 'animators',
            'data'        => $data ?: []
        ];
    }

    /**
     * Summary: aggregate key numbers across children, animators, and recent attendance
     */
    public function getSummary(): array
    {
        $stmtChildren = $this->db->prepare(
            "SELECT COUNT(*) as total FROM children"
        );
        $stmtChildren->execute();
        $totalChildren = (int) $stmtChildren->fetchColumn();

        $stmtAnimators = $this->db->prepare(
            "SELECT COUNT(*) as total FROM animators WHERE status = 'active'"
        );
        $stmtAnimators->execute();
        $activeAnimators = (int) $stmtAnimators->fetchColumn();

        $stmtEvents = $this->db->prepare(
            "SELECT COUNT(*) as total FROM calendar_events WHERE status = 'published'"
        );
        $stmtEvents->execute();
        $publishedEvents = (int) $stmtEvents->fetchColumn();

        $stmtAttendance = $this->db->prepare(
            "SELECT COUNT(*) as total FROM attendance_records WHERE DATE(check_in_time) >= DATE('now', '-30 days')"
        );
        $stmtAttendance->execute();
        $recentCheckins = (int) $stmtAttendance->fetchColumn();

        return [
            'report_type'     => 'summary',
            'total_children'  => $totalChildren,
            'active_animators' => $activeAnimators,
            'published_events' => $publishedEvents,
            'checkins_last_30_days' => $recentCheckins
        ];
    }
}
