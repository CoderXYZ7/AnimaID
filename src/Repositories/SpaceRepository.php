<?php

namespace AnimaID\Repositories;

/**
 * Space Repository
 * Handles interactions with 'spaces' and 'space_bookings' tables
 */
class SpaceRepository extends BaseRepository
{
    protected string $table = 'spaces';

    /**
     * Find all active spaces
     */
    public function findAllActive(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    /**
     * Find bookings for a specific space within a date range
     */
    public function findBookings(int $spaceId, string $startDate, string $endDate): array
    {
        return $this->query(
            "SELECT sb.*, u.username as booked_by_name, e.title as event_title 
             FROM space_bookings sb
             INNER JOIN users u ON sb.booked_by = u.id
             LEFT JOIN calendar_events e ON sb.event_id = e.id
             WHERE sb.space_id = ? 
             AND sb.start_time < ? 
             AND sb.end_time > ?
             ORDER BY sb.start_time ASC",
            [$spaceId, $endDate, $startDate]
        );
    }
    
    /**
     * Check for overlapping bookings
     */
    public function checkOverlap(int $spaceId, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM space_bookings 
                WHERE space_id = ? 
                AND status != 'rejected'
                AND start_time < ? 
                AND end_time > ?";
        
        $params = [$spaceId, $endTime, $startTime];

        if ($excludeBookingId) {
            $sql .= " AND id != ?";
            $params[] = $excludeBookingId;
        }

        $result = $this->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Create a booking
     */
    public function createBooking(array $data): int
    {
        $fields = ['space_id', 'event_id', 'booked_by', 'start_time', 'end_time', 'purpose', 'status'];
        $placeholders = array_fill(0, count($fields), '?');
        
        $insertData = [];
        foreach ($fields as $field) {
            $insertData[] = $data[$field] ?? null;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO space_bookings (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")"
        );
        
        $stmt->execute($insertData);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a booking
     */
    public function updateBooking(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            // Only allow updating known fields
            if (in_array($key, ['start_time', 'end_time', 'purpose', 'status', 'event_id'])) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE space_bookings SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Find booking by ID
     */
    public function findBookingById(int $id): ?array
    {
        return $this->queryOne(
            "SELECT * FROM space_bookings WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Delete booking
     */
    public function deleteBooking(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM space_bookings WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
