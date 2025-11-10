<?php
/**
 * Availability checking utility for the new week types system
 */

class AvailabilityChecker
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if an animator is available at a specific date and time
     *
     * @param int $animatorId
     * @param string $dateTime DateTime string (YYYY-MM-DD HH:MM:SS)
     * @return array ['available' => bool, 'reason' => string, 'week_type' => string]
     */
    public function checkAvailability($animatorId, $dateTime)
    {
        try {
            // Parse the date and time
            $dateTimeObj = new DateTime($dateTime);
            $date = $dateTimeObj->format('Y-m-d');
            $time = $dateTimeObj->format('H:i:s');
            $dayOfWeek = $dateTimeObj->format('l'); // Full day name (Monday, Tuesday, etc.)

            // Step 1: Check for date-specific exceptions first
            $exception = $this->getDateException($animatorId, $date);
            if ($exception) {
                return [
                    'available' => (bool)$exception['is_available'],
                    'reason' => $exception['notes'] ?: 'Date-specific exception',
                    'week_type' => null,
                    'exception' => true
                ];
            }

            // Step 2: Get the active week type for this animator
            // For now, we'll use the first week type (in a real system, you'd have logic to determine which week type is active)
            $weekType = $this->getActiveWeekType($animatorId);
            if (!$weekType) {
                return [
                    'available' => false,
                    'reason' => 'No availability schedule configured',
                    'week_type' => null,
                    'exception' => false
                ];
            }

            // Step 3: Check availability for this day and time
            $availability = $this->getDayAvailability($weekType['id'], $dayOfWeek);
            if (!$availability) {
                return [
                    'available' => false,
                    'reason' => "No availability set for {$dayOfWeek}",
                    'week_type' => $weekType['name'],
                    'exception' => false
                ];
            }

            if (!$availability['is_available']) {
                return [
                    'available' => false,
                    'reason' => "{$dayOfWeek} is marked as unavailable",
                    'week_type' => $weekType['name'],
                    'exception' => false
                ];
            }

            // Step 4: Check if the time falls within the available hours
            if ($time < $availability['start_time'] || $time > $availability['end_time']) {
                return [
                    'available' => false,
                    'reason' => "Time {$time} is outside available hours ({$availability['start_time']} - {$availability['end_time']})",
                    'week_type' => $weekType['name'],
                    'exception' => false
                ];
            }

            return [
                'available' => true,
                'reason' => "Available from {$availability['start_time']} to {$availability['end_time']}",
                'week_type' => $weekType['name'],
                'exception' => false
            ];

        } catch (Exception $e) {
            return [
                'available' => false,
                'reason' => 'Error checking availability: ' . $e->getMessage(),
                'week_type' => null,
                'exception' => false
            ];
        }
    }

    /**
     * Get date-specific exception for an animator
     */
    private function getDateException($animatorId, $date)
    {
        $stmt = $this->pdo->prepare("
            SELECT awte.*
            FROM animator_week_type_exceptions awte
            JOIN animator_week_types awt ON awte.week_type_id = awt.id
            WHERE awt.animator_id = ? AND awte.exception_date = ?
            ORDER BY awte.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$animatorId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the active week type for an animator
     * For now, returns the first week type. In a real system, you'd have logic
     * to determine which week type is active based on date ranges, priorities, etc.
     */
    private function getActiveWeekType($animatorId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM animator_week_types
            WHERE animator_id = ?
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$animatorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get availability for a specific day in a week type
     */
    private function getDayAvailability($weekTypeId, $dayOfWeek)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM animator_week_availability
            WHERE week_type_id = ? AND day_of_week = ?
            LIMIT 1
        ");
        $stmt->execute([$weekTypeId, $dayOfWeek]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all availability for a week type
     */
    public function getWeekTypeAvailability($weekTypeId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM animator_week_availability
            WHERE week_type_id = ?
            ORDER BY CASE day_of_week
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END
        ");
        $stmt->execute([$weekTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all week types for an animator
     */
    public function getAnimatorWeekTypes($animatorId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                awt.*,
                COUNT(awta.id) as availability_count
            FROM animator_week_types awt
            LEFT JOIN animator_week_availability awta ON awt.id = awta.week_type_id
            WHERE awt.animator_id = ?
            GROUP BY awt.id
            ORDER BY awt.created_at ASC
        ");
        $stmt->execute([$animatorId]);

        $weekTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add availability data to each week type
        foreach ($weekTypes as &$weekType) {
            $weekType['availability'] = $this->getWeekTypeAvailability($weekType['id']);
        }

        return $weekTypes;
    }

    /**
     * Get availability exceptions for a week type
     */
    public function getWeekTypeExceptions($weekTypeId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM animator_week_type_exceptions
            WHERE week_type_id = ?
            ORDER BY exception_date ASC
        ");
        $stmt->execute([$weekTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check availability for a time range
     */
    public function checkAvailabilityRange($animatorId, $startDateTime, $endDateTime)
    {
        $start = new DateTime($startDateTime);
        $end = new DateTime($endDateTime);

        $results = [];
        $current = clone $start;

        while ($current <= $end) {
            $checkResult = $this->checkAvailability($animatorId, $current->format('Y-m-d H:i:s'));
            $results[] = [
                'date_time' => $current->format('Y-m-d H:i:s'),
                'available' => $checkResult['available'],
                'reason' => $checkResult['reason']
            ];

            // Check every 30 minutes
            $current->modify('+30 minutes');
        }

        return $results;
    }

    /**
     * Find available time slots for an animator on a specific date
     */
    public function findAvailableSlots($animatorId, $date, $durationMinutes = 60)
    {
        $availableSlots = [];

        // Check every 30-minute interval from 6 AM to 10 PM
        $startTime = new DateTime($date . ' 06:00:00');
        $endTime = new DateTime($date . ' 22:00:00');

        $current = clone $startTime;

        while ($current < $endTime) {
            $slotEnd = clone $current;
            $slotEnd->modify("+{$durationMinutes} minutes");

            // Check if the entire slot is available
            $slotAvailable = true;
            $checkTime = clone $current;

            while ($checkTime < $slotEnd) {
                $availability = $this->checkAvailability($animatorId, $checkTime->format('Y-m-d H:i:s'));
                if (!$availability['available']) {
                    $slotAvailable = false;
                    break;
                }
                $checkTime->modify('+30 minutes');
            }

            if ($slotAvailable) {
                $availableSlots[] = [
                    'start' => $current->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'duration' => $durationMinutes
                ];
            }

            $current->modify('+30 minutes');
        }

        return $availableSlots;
    }
}
?>
