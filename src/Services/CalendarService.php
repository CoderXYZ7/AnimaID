<?php

namespace AnimaID\Services;

use AnimaID\Repositories\CalendarRepository;
use AnimaID\Repositories\ChildRepository;
use AnimaID\Config\ConfigManager;

/**
 * Calendar Service
 * Handles calendar event logic and participant management
 */
class CalendarService
{
    private CalendarRepository $calendarRepository;
    private ChildRepository $childRepository;
    private ConfigManager $config;

    public function __construct(
        CalendarRepository $calendarRepository,
        ChildRepository $childRepository,
        ConfigManager $config
    ) {
        $this->calendarRepository = $calendarRepository;
        $this->childRepository = $childRepository;
        $this->config = $config;
    }

    /**
     * Get events with pagination and filters
     */
    public function getEvents(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        
        $events = $this->calendarRepository->findEventsWithFilters($filters, $limit, $offset);
        $total = $this->calendarRepository->countEventsWithFilters($filters);
        
        return [
            'events' => $events,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get single event
     */
    public function getEvent(int $eventId): ?array
    {
        return $this->calendarRepository->findById($eventId);
    }

    /**
     * Create event
     */
    public function createEvent(array $data, int $userId): array
    {
        // Validate required fields
        if (empty($data['title']) || empty($data['start_date']) || empty($data['end_date'])) {
            throw new \Exception('Title, start date, and end date are required');
        }

        $eventData = array_merge($data, [
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Sanitize boolean
        if (isset($eventData['is_public'])) {
            $eventData['is_public'] = $eventData['is_public'] ? 1 : 0;
        }

        $eventId = $this->calendarRepository->insert($eventData);
        
        return $this->calendarRepository->findById($eventId);
    }

    /**
     * Update event
     */
    public function updateEvent(int $eventId, array $data): array
    {
        $event = $this->calendarRepository->findById($eventId);
        if (!$event) {
            throw new \Exception('Event not found');
        }

        $allowedFields = [
            'title', 'description', 'event_type', 'start_date', 'end_date', 
            'start_time', 'end_time', 'is_all_day', 'location', 
            'max_participants', 'age_min', 'age_max', 'status', 'is_public'
        ];
        
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return $event;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($updateData['is_public'])) {
            $updateData['is_public'] = $updateData['is_public'] ? 1 : 0;
        }

        $this->calendarRepository->update($eventId, $updateData);
        
        return $this->calendarRepository->findById($eventId);
    }

    /**
     * Delete event
     */
    public function deleteEvent(int $eventId): void
    {
        if (!$this->calendarRepository->exists($eventId)) {
            throw new \Exception('Event not found');
        }

        $this->calendarRepository->delete($eventId);
    }

    /**
     * Get event participants
     */
    public function getParticipants(int $eventId): array
    {
        if (!$this->calendarRepository->exists($eventId)) {
            throw new \Exception('Event not found');
        }

        return $this->calendarRepository->getParticipants($eventId);
    }

    /**
     * Register a child for an event
     */
    public function registerChild(int $eventId, int $childId): int
    {
        // Check if event exists
        if (!$this->calendarRepository->exists($eventId)) {
            throw new \Exception('Event not found');
        }

        // Check if child exists
        // Note: We're using ChildRepository here, assuming it has a findById method
        $child = $this->childRepository->findById($childId);
        if (!$child) {
            throw new \Exception('Child not found');
        }

        // Check if already registered
        $existingId = $this->calendarRepository->isChildRegistered($eventId, $childId);
        if ($existingId) {
            return $existingId;
        }
        
        // Get guardian info for the snapshot
        $guardian = $this->childRepository->getPrimaryGuardian($childId);
        $medical = $this->childRepository->getMedicalInfo($childId);

        // Prepare participant data
        $participantData = [
            'child_id' => $childId,
            'child_name' => $child['first_name'],
            'child_surname' => $child['last_name'],
            'birth_date' => $child['birth_date'],
            'parent_name' => $guardian ? $guardian['first_name'] . ' ' . $guardian['last_name'] : '',
            'parent_email' => $guardian ? $guardian['email'] : '',
            'parent_phone' => $guardian ? $guardian['phone'] : '',
            'emergency_contact' => $medical ? $medical['emergency_contact_name'] . ' ' . $medical['emergency_contact_phone'] : '',
            'medical_notes' => $medical ? $medical['allergies'] : '',
            'status' => 'registered',
            'registration_date' => date('Y-m-d H:i:s')
        ];

        return $this->calendarRepository->addParticipant($eventId, $participantData);
    }

    /**
     * Remove participant from event
     */
    public function removeParticipant(int $eventId, int $participantId): void
    {
        $participant = $this->calendarRepository->findParticipant($participantId);
        
        if (!$participant) {
            throw new \Exception('Participant not found');
        }

        if ($participant['event_id'] != $eventId) {
            throw new \Exception('Participant does not belong to this event');
        }
        
        $this->calendarRepository->deleteParticipant($participantId);
    }
}
