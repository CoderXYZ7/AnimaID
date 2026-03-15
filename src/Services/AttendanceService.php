<?php

namespace AnimaID\Services;

use AnimaID\Repositories\AttendanceRepository;
use AnimaID\Config\ConfigManager;

/**
 * Attendance Service
 * Handles attendance check-in/check-out logic for events
 */
class AttendanceService
{
    private AttendanceRepository $attendanceRepository;
    private ConfigManager $config;

    public function __construct(
        AttendanceRepository $attendanceRepository,
        ConfigManager $config
    ) {
        $this->attendanceRepository = $attendanceRepository;
        $this->config = $config;
    }

    /**
     * Get attendance records, optionally filtered
     */
    public function getByEvent(int $eventId): array
    {
        return $this->attendanceRepository->findByEvent($eventId);
    }

    /**
     * Get attendance records with optional filters
     */
    public function getRecords(?int $eventId = null, ?int $participantId = null, ?string $date = null): array
    {
        return $this->attendanceRepository->findAllWithDetails($eventId, $participantId, $date);
    }

    /**
     * Record a check-in for a child at an event
     */
    public function checkIn(array $data, int $staffId): void
    {
        $childId = (int) ($data['child_id'] ?? 0);
        $eventId = (int) ($data['event_id'] ?? 0);
        $notes = $data['notes'] ?? '';

        if (!$childId || !$eventId) {
            throw new \Exception('Child ID and Event ID are required');
        }

        $now = date('Y-m-d H:i:s');

        // Find the participant record for this child in this event
        $participantId = $this->resolveParticipantId($childId, $eventId);

        $this->attendanceRepository->checkIn([
            'participant_id' => $participantId,
            'event_id'       => $eventId,
            'check_in_time'  => $now,
            'check_in_staff' => $staffId,
            'status'         => 'present',
            'notes'          => $notes
        ]);
    }

    /**
     * Record a check-out for a child at an event
     */
    public function checkOut(int $id, array $data): void
    {
        $record = $this->attendanceRepository->findById($id);
        if (!$record) {
            throw new \Exception('Attendance record not found');
        }

        $now = date('Y-m-d H:i:s');
        $staffId = (int) ($data['staff_id'] ?? 0);
        $notes = $data['notes'] ?? $record['notes'] ?? '';

        $this->attendanceRepository->checkOut($id, [
            'check_out_time'  => $now,
            'check_out_staff' => $staffId ?: null,
            'notes'           => $notes
        ]);
    }

    /**
     * Delete an attendance record
     */
    public function delete(int $id): void
    {
        if (!$this->attendanceRepository->exists($id)) {
            throw new \Exception('Attendance record not found');
        }

        $this->attendanceRepository->delete($id);
    }

    /**
     * Resolve the event_participants.id for a given child + event combination.
     * Returns the participant ID or throws if the child is not registered.
     */
    private function resolveParticipantId(int $childId, int $eventId): int
    {
        $db = $this->attendanceRepository->getPdo();

        $stmt = $db->prepare(
            "SELECT id FROM event_participants WHERE child_id = ? AND event_id = ? LIMIT 1"
        );
        $stmt->execute([$childId, $eventId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \Exception('Child is not registered for this event');
        }

        return (int) $row['id'];
    }
}
