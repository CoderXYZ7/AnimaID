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
     * Create a space
     */
    public function insert(array $data): int
    {
        // Add parent_id and type to allowed fields if not using BaseRepository generic insert (which I am not? Wait, SpaceRepository extends BaseRepository)
        // BaseRepository insert uses array keys.
        // So I just need to make sure the Service passes the right keys.
        
        // However, I previously overrode createBooking but NOT insert/update for spaces.
        // The BaseRepository insert method looks like:
        /*
        public function insert(array $data): string
        {
            $columns =  implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $this->query($sql, array_values($data));
            return $this->db->lastInsertId();
        }
        */
        // So I don't need to change insert/update in Repository if I use the BaseRepository methods.
        // I DO need to update findAllActive to perhaps order by hierarchy or return parent_id.
        // Actually findAllActive calls `SELECT *`, so it automagically includes new columns.
        
        return parent::insert($data);
    }
    
    /**
     * Find all active spaces ordered by hierarchy
     */
    public function findAllActive(): array
    {
        // Order by parent_id (null first) then name, to group roots then children
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY COALESCE(parent_id, 0), parent_id, name ASC"
        );
    }

    /**
     * Find bookings for a specific space within a date range
     */
    /**
     * Find bookings for a specific space or all spaces within a date range
     */
    public function findBookings(?int $spaceId, string $startDate, string $endDate): array
    {
        $sql = "SELECT sb.*, u.username as booked_by_name, e.title as event_title, s.name as space_name, s.type as space_type 
             FROM space_bookings sb
             INNER JOIN users u ON sb.booked_by = u.id
             LEFT JOIN calendar_events e ON sb.event_id = e.id
             LEFT JOIN spaces s ON sb.space_id = s.id
             WHERE sb.start_time < ? 
             AND sb.end_time > ?";
        
        $params = [$endDate, $startDate];

        if ($spaceId) {
            $sql .= " AND sb.space_id = ?";
            $params[] = $spaceId;
        }

        $sql .= " ORDER BY sb.start_time ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find ALL bookings for all spaces (Alias for findBookings with null spaceId)
     */
    public function findAllBookings(string $startDate, string $endDate): array
    {
        return $this->findBookings(null, $startDate, $endDate);
    }
    
    /**
     * Check for overlapping bookings considering hierarchy (Parent/Child blocking)
     * Returns the conflicting space info array if conflict found, or null if safe.
     */
    public function checkHierarchyOverlap(int $spaceId, string $startTime, string $endTime, ?int $excludeBookingId = null): ?array
    {
        // 1. Resolve all related Space IDs (Self, Ancestors, Descendants)
        // Note: Booking a child blocks the parent? Yes, usually.
        // Booking a parent blocks the child? Yes.
        
        $relatedIds = array_unique(array_merge(
            [$spaceId],
            $this->getAncestorIds($spaceId),
            $this->getDescendantIds($spaceId)
        ));
        
        if (empty($relatedIds)) return null;

        $placeholders = str_repeat('?,', count($relatedIds) - 1) . '?';
        
        $sql = "SELECT sb.*, s.name as space_name FROM space_bookings sb
                JOIN spaces s ON sb.space_id = s.id
                WHERE sb.space_id IN ($placeholders)
                AND sb.status != 'rejected'
                AND sb.start_time < ? 
                AND sb.end_time > ?";
        
        $params = $relatedIds;
        $params[] = $endTime;
        $params[] = $startTime;

        if ($excludeBookingId) {
            $sql .= " AND sb.id != ?";
            $params[] = $excludeBookingId;
        }

        $sql .= " LIMIT 1";

        $result = $this->queryOne($sql, $params);
        
        if ($result) {
            return [
                'id' => $result['id'],
                'name' => $result['space_name'],
                'start_time' => $result['start_time'],
                'end_time' => $result['end_time']
            ];
        }
        
        return null;
    }

    private function getAncestorIds(int $spaceId): array
    {
        $ids = [];
        $currentId = $spaceId;
        
        // Prevent infinite loops with depth limit
        $depth = 0;
        while ($depth < 10) {
            $parent = $this->queryOne("SELECT parent_id FROM spaces WHERE id = ?", [$currentId]);
            if (!$parent || !$parent['parent_id']) break;
            
            $ids[] = $parent['parent_id'];
            $currentId = $parent['parent_id'];
            $depth++;
        }
        return $ids;
    }

    private function getDescendantIds(int $spaceId): array
    {
        $ids = [];
        // Direct children
        $children = $this->query("SELECT id FROM spaces WHERE parent_id = ?", [$spaceId]);
        
        foreach ($children as $child) {
            $ids[] = $child['id'];
            // Recursively get grandchildren
            $ids = array_merge($ids, $this->getDescendantIds($child['id']));
        }
        
        return $ids;
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
