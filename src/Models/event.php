<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Event {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAllEvents($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT * FROM events ORDER BY date DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalEventsCount($search = '') {
        $sql = "SELECT COUNT(*) FROM events";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE title LIKE ? OR description LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Error counting events: " . $e->getMessage());
        }
    }

    public function createEvent($title, $description, $date, $locationId, $organizerId)
    {
        try {
            $sql = "INSERT INTO events (title, description, date, location_id, organizer_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $description, $date, $locationId, $organizerId]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error creating event: " . $e->getMessage());
        }
    }

    public function getEvent($id)
    {
        try {
            $sql = "SELECT * FROM events WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                return null;
            }
            
            return $event;
        } catch (PDOException $e) {
            throw new Exception("Error fetching event: " . $e->getMessage());
        }
    }

    public function updateEvent($id, $title, $description, $date, $locationId)
    {
        try {
            $sql = "UPDATE events SET title = ?, description = ?, date = ?, location_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$title, $description, $date, $locationId, $id]);
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Error updating event: " . $e->getMessage());
        }
    }

    public function deleteEvent($id)
    {
        try {
            $sql = "DELETE FROM events WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Error deleting event: " . $e->getMessage());
        }
    }

    public function getEventsByLocation($locationId, $page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, l.name as location_name, l.slug as location_slug 
                FROM events e
                JOIN locations l ON e.location_id = l.id
                WHERE e.location_id = ?
                ORDER BY e.date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$locationId, $perPage, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEventWithOrganizer($id) {
        try {
            $sql = "SELECT e.*, u.username as organizer_name, u.display_name_on_events 
                    FROM events e 
                    LEFT JOIN users u ON e.organizer_id = u.id 
                    WHERE e.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                error_log("No event found with ID: $id");
            }
            return $result;
        } catch (PDOException $e) {
            error_log('Database error in getEventWithOrganizer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getTicketTypesForEvent($eventId)
    {
        $sql = "SELECT id, name, price, capacity FROM ticket_types WHERE event_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add other methods (createEvent, getEvent, updateEvent, deleteEvent) here...

    public function getAllEventsWithOrganizer($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, u.username as organizer_name, u.display_name_on_events 
                FROM events e
                LEFT JOIN users u ON e.organizer_id = u.id
                ORDER BY e.date DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
