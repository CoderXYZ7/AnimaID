<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\CalendarService;
use AnimaID\Repositories\CalendarRepository;
use AnimaID\Repositories\ChildRepository;
use AnimaID\Config\ConfigManager;

class CalendarServiceTest extends TestCase
{
    private $calendarRepository;
    private $childRepository;
    private $config;
    private $calendarService;

    protected function setUp(): void
    {
        $this->calendarRepository = $this->createMock(CalendarRepository::class);
        $this->childRepository = $this->createMock(ChildRepository::class);
        $this->config = $this->createMock(ConfigManager::class);

        $this->calendarService = new CalendarService(
            $this->calendarRepository,
            $this->childRepository,
            $this->config
        );
    }

    public function testGetEvents()
    {
        $mockEvents = [
            ['id' => 1, 'title' => 'Test Event']
        ];
        
        $this->calendarRepository->expects($this->once())
            ->method('findEventsWithFilters')
            ->willReturn($mockEvents);
            
        $this->calendarRepository->expects($this->once())
            ->method('countEventsWithFilters')
            ->willReturn(1);

        $result = $this->calendarService->getEvents(1, 10, []);
        
        $this->assertEquals($mockEvents, $result['events']);
        $this->assertEquals(1, $result['pagination']['total']);
    }

    public function testCreateEvent()
    {
        $eventData = [
            'title' => 'New Event',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01'
        ];
        
        $userId = 1;
        $createdId = 5;
        
        $this->calendarRepository->expects($this->once())
            ->method('insert')
            ->willReturn($createdId);
            
        $this->calendarRepository->expects($this->once())
            ->method('findById')
            ->with($createdId)
            ->willReturn(array_merge($eventData, ['id' => $createdId]));

        $result = $this->calendarService->createEvent($eventData, $userId);
        
        $this->assertEquals($createdId, $result['id']);
        $this->assertEquals('New Event', $result['title']);
    }

    public function testCreateEventMissingFields()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Title, start date, and end date are required');
        
        $this->calendarService->createEvent(['title' => 'Incomplete'], 1);
    }

    public function testRegisterChild()
    {
        $eventId = 1;
        $childId = 10;
        
        $this->calendarRepository->method('exists')->willReturn(true);
        $this->childRepository->method('findById')->willReturn([
            'id' => 10,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2015-01-01'
        ]);
        
        $this->calendarRepository->method('isChildRegistered')->willReturn(null);
        
        // Mock guardian and medical getters since they are called
        $this->childRepository->method('getPrimaryGuardian')->willReturn([
            'first_name' => 'Parent',
            'last_name' => 'One',
            'email' => 'parent@example.com',
            'phone' => '123456789'
        ]);
        
        $this->childRepository->method('getMedicalInfo')->willReturn([
            'emergency_contact_name' => 'Grandma',
            'emergency_contact_phone' => '987654321',
            'allergies' => 'None'
        ]);

        $this->calendarRepository->expects($this->once())
            ->method('addParticipant')
            ->with($eventId, $this->callback(function($data) {
                return $data['child_name'] === 'John' &&
                       $data['parent_name'] === 'Parent One';
            }))
            ->willReturn(100);

        $result = $this->calendarService->registerChild($eventId, $childId);
        
        $this->assertEquals(100, $result);
    }
}
