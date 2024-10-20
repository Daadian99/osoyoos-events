<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Event;
use PDO;

class EventTest extends TestCase
{
    private $pdo;
    private $event;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->event = new Event($this->pdo);
    }

    public function testCreateEvent()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([
                 'Test Event',
                 'Test Description',
                 '2023-12-31',
                 1,
                 1
             ])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("INSERT INTO events (title, description, date, location_id, organizer_id) VALUES (?, ?, ?, ?, ?)")
             ->willReturn($stmt);

        $this->pdo->expects($this->once())
             ->method('lastInsertId')
             ->willReturn('1');

        $result = $this->event->createEvent('Test Event', 'Test Description', '2023-12-31', 1, 1);

        $this->assertEquals('1', $result);
    }

    public function testGetEvent()
    {
        $eventData = [
            'id' => 1,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'date' => '2023-12-31',
            'location_id' => 1,
            'organizer_id' => 1
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')
             ->willReturn($eventData);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("SELECT * FROM events WHERE id = ?")
             ->willReturn($stmt);

        $result = $this->event->getEvent(1);

        $this->assertEquals($eventData, $result);
    }

    public function testUpdateEvent()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with(['New Title', 'New Description', '2024-01-01', 2, 1])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("UPDATE events SET title = ?, description = ?, date = ?, location_id = ? WHERE id = ?")
             ->willReturn($stmt);

        $result = $this->event->updateEvent(1, 'New Title', 'New Description', '2024-01-01', 2);

        $this->assertTrue($result);
    }

    public function testDeleteEvent()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([1])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("DELETE FROM events WHERE id = ?")
             ->willReturn($stmt);

        $result = $this->event->deleteEvent(1);

        $this->assertTrue($result);
    }

    public function testGetAllEvents()
    {
        $eventData = [
            ['id' => 1, 'title' => 'Event 1'],
            ['id' => 2, 'title' => 'Event 2']
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')
             ->willReturn($eventData);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with($this->stringContains("SELECT e.*, l.name as location_name, l.slug as location_slug"))
             ->willReturn($stmt);

        $result = $this->event->getAllEvents();

        $this->assertEquals($eventData, $result);
    }
}
