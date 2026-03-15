<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\ReportService;
use PDO;
use PDOStatement;

class ReportServiceTest extends TestCase
{
    private $db;
    private $reportService;

    protected function setUp(): void
    {
        $this->db = $this->createMock(PDO::class);

        $this->reportService = new ReportService($this->db);
    }

    // ---------- Helper ----------

    /**
     * Build a mock PDOStatement that returns the given data from fetchAll() or fetch().
     */
    private function makePdoStatement(array $fetchAllResult = [], $fetchResult = false, $fetchColumnResult = 0): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($fetchAllResult);
        $stmt->method('fetch')->willReturn($fetchResult);
        $stmt->method('fetchColumn')->willReturn($fetchColumnResult);
        return $stmt;
    }

    // ---------- Tests ----------

    public function testGetAvailableReports(): void
    {
        $result = $this->reportService->getAvailableReports();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $ids = array_column($result, 'id');
        $this->assertContains('attendance', $ids);
        $this->assertContains('children',   $ids);
        $this->assertContains('animators',  $ids);

        // Each entry must have the required keys
        foreach ($result as $report) {
            $this->assertArrayHasKey('id',          $report);
            $this->assertArrayHasKey('name',        $report);
            $this->assertArrayHasKey('description', $report);
            $this->assertArrayHasKey('endpoint',    $report);
        }
    }

    public function testGetAttendanceReport(): void
    {
        $mockRows = [
            [
                'event_title'      => 'Summer Camp',
                'start_date'       => '2026-03-01',
                'end_date'         => '2026-03-01',
                'checked_in'       => 10,
                'checked_out'      => 8,
                'total_registered' => 12
            ]
        ];

        $stmt = $this->makePdoStatement($mockRows);
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->reportService->getAttendanceReport([
            'start_date' => '2026-03-01',
            'end_date'   => '2026-03-31'
        ]);

        $this->assertEquals('attendance', $result['report_type']);
        $this->assertEquals('2026-03-01', $result['period']['start']);
        $this->assertEquals('2026-03-31', $result['period']['end']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Summer Camp', $result['data'][0]['event_title']);
    }

    public function testGetAttendanceReportUsesDefaultDateRange(): void
    {
        $stmt = $this->makePdoStatement([]);
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->reportService->getAttendanceReport();

        $this->assertArrayHasKey('period', $result);
        $this->assertNotEmpty($result['period']['start']);
        $this->assertNotEmpty($result['period']['end']);
    }

    public function testGetChildrenStats(): void
    {
        $mockRow = [
            'total_children'  => 50,
            'active_children' => 45,
            'avg_age_years'   => 7.3,
            'under_3'         => 5,
            'age_3_6'         => 15,
            'age_6_12'        => 25
        ];

        $stmt = $this->makePdoStatement([], $mockRow);
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->reportService->getChildrenStats();

        $this->assertEquals('children', $result['report_type']);
        $this->assertEquals(50, $result['data']['total_children']);
        $this->assertEquals(45, $result['data']['active_children']);
    }

    public function testGetChildrenStatsEmptyDb(): void
    {
        $stmt = $this->makePdoStatement([], false);
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->reportService->getChildrenStats();

        $this->assertEquals('children', $result['report_type']);
        $this->assertEmpty($result['data']);
    }

    public function testGetAnimatorStats(): void
    {
        $mockRow = [
            'total_animators'  => 20,
            'active_animators' => 18,
            'hired_animators'  => 20,
            'avg_years_service' => 3.5
        ];

        $stmt = $this->makePdoStatement([], $mockRow);
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->reportService->getAnimatorStats();

        $this->assertEquals('animators', $result['report_type']);
        $this->assertEquals(20, $result['data']['total_animators']);
        $this->assertEquals(18, $result['data']['active_animators']);
    }

    public function testGetSummary(): void
    {
        // getSummary calls prepare 4 times, each returning a different fetchColumn value
        $stmtChildren  = $this->makePdoStatement([], false, 100);
        $stmtAnimators = $this->makePdoStatement([], false, 15);
        $stmtEvents    = $this->makePdoStatement([], false, 8);
        $stmtAttend    = $this->makePdoStatement([], false, 42);

        $this->db->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtChildren, $stmtAnimators, $stmtEvents, $stmtAttend);

        $result = $this->reportService->getSummary();

        $this->assertEquals('summary', $result['report_type']);
        $this->assertEquals(100, $result['total_children']);
        $this->assertEquals(15,  $result['active_animators']);
        $this->assertEquals(8,   $result['published_events']);
        $this->assertEquals(42,  $result['checkins_last_30_days']);
    }
}
