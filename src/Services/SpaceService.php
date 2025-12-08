<?php

namespace AnimaID\Services;

use AnimaID\Repositories\SpaceRepository;
use AnimaID\Config\ConfigManager;

/**
 * Space Service
 * Handles space booking logic, conflict checking, and management
 */
class SpaceService
{
    private SpaceRepository $spaceRepository;
    private ConfigManager $config;

    public function __construct(SpaceRepository $spaceRepository, ConfigManager $config)
    {
        $this->spaceRepository = $spaceRepository;
        $this->config = $config;
    }

    /**
     * Get all spaces
     */
    public function getAllSpaces(bool $activeOnly = true): array
    {
        if ($activeOnly) {
            return $this->spaceRepository->findAllActive();
        }
        return $this->spaceRepository->findAll();
    }

    /**
     * Get single space
     */
    public function getSpace(int $id): ?array
    {
        return $this->spaceRepository->findById($id);
    }

    /**
     * Create space
     */
    public function createSpace(array $data): int
    {
        if (empty($data['name'])) {
            throw new \Exception('Space name is required');
        }

        // Validate capacity
        if (isset($data['capacity']) && $data['capacity'] < 0) {
             throw new \Exception('Capacity cannot be negative');
        }

        return $this->spaceRepository->insert([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'capacity' => $data['capacity'] ?? 0,
            'location' => $data['location'] ?? '',
            'is_active' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update space
     */
    public function updateSpace(int $id, array $data): void
    {
        if (!$this->spaceRepository->exists($id)) {
            throw new \Exception('Space not found');
        }
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['capacity'])) $updateData['capacity'] = $data['capacity'];
        if (isset($data['location'])) $updateData['location'] = $data['location'];
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'] ? 1 : 0;

        $this->spaceRepository->update($id, $updateData);
    }

    /**
     * Delete space
     */
    public function deleteSpace(int $id): void
    {
        // Ideally checking for future bookings before delete
        // For now, rely on database cascade or simple delete
        if (!$this->spaceRepository->exists($id)) {
            throw new \Exception('Space not found');
        }
        $this->spaceRepository->delete($id);
    }

    /**
     * Create booking
     */
    public function createBooking(array $data, int $userId): int
    {
        $this->validateBookingData($data);

        // Check availability
        if ($this->spaceRepository->checkOverlap($data['space_id'], $data['start_time'], $data['end_time'])) {
            throw new \Exception('Space is already booked for this time period');
        }

        $bookingData = [
            'space_id' => $data['space_id'],
            'booked_by' => $userId,
            'event_id' => $data['event_id'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'] ?? '',
            'status' => 'confirmed' // Auto-confirm by default for now
        ];

        return $this->spaceRepository->createBooking($bookingData);
    }

    /**
     * Update booking
     */
    public function updateBooking(int $id, array $data): void
    {
        $booking = $this->spaceRepository->findBookingById($id);
        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        // If time is changing, check overlap
        if (isset($data['start_time']) || isset($data['end_time'])) {
            $startTime = $data['start_time'] ?? $booking['start_time'];
            $endTime = $data['end_time'] ?? $booking['end_time'];
            
            // Validate time sanity
            if (strtotime($endTime) <= strtotime($startTime)) {
                throw new \Exception('End time must be after start time');
            }

            if ($this->spaceRepository->checkOverlap($booking['space_id'], $startTime, $endTime, $id)) {
                throw new \Exception('Space is already booked for this time period');
            }
        }

        $this->spaceRepository->updateBooking($id, $data);
    }

    /**
     * Get bookings for a space
     */
    public function getSpaceBookings(int $spaceId, ?string $start = null, ?string $end = null): array
    {
        // Default to current month if no dates provided
        if (!$start) $start = date('Y-m-01');
        if (!$end) $end = date('Y-m-t');

        return $this->spaceRepository->findBookings($spaceId, $start, $end);
    }
    
    /**
     * Cancel/Delete booking
     */
    public function deleteBooking(int $id): void
    {
        if (!$this->spaceRepository->findBookingById($id)) {
            throw new \Exception('Booking not found');
        }
        $this->spaceRepository->deleteBooking($id);
    }

    /**
     * Helper to validate booking request
     */
    private function validateBookingData(array $data): void
    {
        if (empty($data['space_id'])) throw new \Exception('Space ID is required');
        if (empty($data['start_time'])) throw new \Exception('Start time is required');
        if (empty($data['end_time'])) throw new \Exception('End time is required');
        
        $start = strtotime($data['start_time']);
        $end = strtotime($data['end_time']);
        
        if (!$start || !$end) {
            throw new \Exception('Invalid date format');
        }
        
        if ($end <= $start) {
            throw new \Exception('End time must be after start time');
        }
        
        if (!$this->spaceRepository->exists($data['space_id'])) {
            throw new \Exception('Space not found');
        }
    }
}
