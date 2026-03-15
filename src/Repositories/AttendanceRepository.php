<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Attendance Repository
 * Handles all attendance record data access operations
 */
class AttendanceRepository extends BaseRepository
{
    protected string $table = 'attendance_records';

    /**
     * Find attendance records for a specific event, with participant and child details
     */
    public function findByEvent(int $eventId): array
    {
        return $this->query(
            "SELECT
                ar.*,
                COALESCE(c.first_name, ep.child_name) as child_name,
                COALESCE(c.last_name, ep.child_surname) as child_surname,
                ce.title as event_title
            FROM {$this->table} ar
            JOIN event_participants ep ON ar.participant_id = ep.id
            LEFT JOIN children c ON ep.child_id = c.id
            JOIN calendar_events ce ON ar.event_id = ce.id
            WHERE ar.event_id = ?
            ORDER BY ar.check_in_time DESC",
            [$eventId]
        );
    }

    /**
     * Find attendance records for a specific participant
     */
    public function findByParticipant(int $participantId): array
    {
        return $this->query(
            "SELECT
                ar.*,
                COALESCE(c.first_name, ep.child_name) as child_name,
                COALESCE(c.last_name, ep.child_surname) as child_surname,
                ce.title as event_title
            FROM {$this->table} ar
            JOIN event_participants ep ON ar.participant_id = ep.id
            LEFT JOIN children c ON ep.child_id = c.id
            JOIN calendar_events ce ON ar.event_id = ce.id
            WHERE ar.participant_id = ?
            ORDER BY ar.check_in_time DESC",
            [$participantId]
        );
    }

    /**
     * Find all records with optional filters, joining participant/child/event data
     */
    public function findAllWithDetails(?int $eventId = null, ?int $participantId = null, ?string $date = null): array
    {
        $where = [];
        $params = [];

        if ($eventId !== null) {
            $where[] = 'ar.event_id = ?';
            $params[] = $eventId;
        }

        if ($participantId !== null) {
            $where[] = 'ar.participant_id = ?';
            $params[] = $participantId;
        }

        if ($date !== null) {
            $where[] = 'DATE(ar.check_in_time) = DATE(?)';
            $params[] = $date;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->query(
            "SELECT
                ar.*,
                COALESCE(c.first_name, ep.child_name) as child_name,
                COALESCE(c.last_name, ep.child_surname) as child_surname,
                ce.title as event_title
            FROM {$this->table} ar
            JOIN event_participants ep ON ar.participant_id = ep.id
            LEFT JOIN children c ON ep.child_id = c.id
            JOIN calendar_events ce ON ar.event_id = ce.id
            {$whereClause}
            ORDER BY ar.check_in_time DESC",
            $params
        );
    }

    /**
     * Record a check-in
     */
    public function checkIn(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Update an existing record with check-out data
     */
    public function checkOut(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Find an existing attendance record for a participant/event on a given date
     */
    public function findExistingRecord(int $participantId, int $eventId, string $date): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table}
             WHERE participant_id = ? AND event_id = ? AND DATE(check_in_time) = DATE(?)",
            [$participantId, $eventId, $date]
        );
    }
}
