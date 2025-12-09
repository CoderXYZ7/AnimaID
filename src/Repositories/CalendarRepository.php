<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Calendar Repository
 * Handles calendar events and participant operations
 */
class CalendarRepository extends BaseRepository
{
    protected string $table = 'calendar_events';

    /**
     * Find events with filters
     */
    public function findEventsWithFilters(array $filters, int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['event_type'])) {
            $query .= " AND event_type = ?";
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND start_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND end_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (isset($filters['is_public'])) {
            $query .= " AND is_public = ?";
            $params[] = $filters['is_public'];
        }

        $query .= " ORDER BY start_date, start_time LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->query($query, $params);
    }

    /**
     * Count events with filters
     */
    public function countEventsWithFilters(array $filters): int
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['event_type'])) {
            $query .= " AND event_type = ?";
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND start_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND end_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (isset($filters['is_public'])) {
            $query .= " AND is_public = ?";
            $params[] = $filters['is_public'];
        }

        $result = $this->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Get event participants
     */
    public function getParticipants(int $eventId): array
    {
        return $this->query(
            "SELECT * FROM event_participants WHERE event_id = ? ORDER BY registration_date",
            [$eventId]
        );
    }

    /**
     * Add participant to event
     */
    public function addParticipant(int $eventId, array $participantData): int
    {
        $data = array_merge($participantData, ['event_id' => $eventId]);
        
        // We'll use a direct insert here since BaseRepository->insert targets $this->table (calendar_events)
        // Ideally BaseRepository should allow specifying table, but this works for now
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO event_participants (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find participant
     */
    public function findParticipant(int $participantId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM event_participants WHERE id = ?",
            [$participantId]
        );
    }

    /**
     * Update participant
     */
    public function updateParticipant(int $participantId, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }

        $params[] = $participantId;

        $sql = "UPDATE event_participants SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Check if child is already participating
     */
    public function isChildRegistered(int $eventId, int $childId): ?int
    {
        $result = $this->queryOne(
            "SELECT id FROM event_participants WHERE event_id = ? AND child_id = ?",
            [$eventId, $childId]
        );

        return $result ? (int) $result['id'] : null;
    }
    
    /**
     * Delete participant
     */
    public function deleteParticipant(int $participantId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM event_participants WHERE id = ?");
        return $stmt->execute([$participantId]);
    }
}
