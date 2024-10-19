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
        $sql = "SELECT e.*, l.name as location_name, l.slug as location_slug 
                FROM events e
                JOIN locations l ON e.location_id = l.id
                ORDER BY e.date DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

    public function createEvent($title, $description, $date, $location, $organizerId)
    {
        try {
            $sql = "INSERT INTO events (title, description, date, location, organizer_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $description, $date, $location, $organizerId]);
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

    public function updateEvent($id, $title, $description, $date, $location)
    {
        try {
            $sql = "UPDATE events SET title = ?, description = ?, date = ?, location = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$title, $description, $date, $location, $id]);
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

    // Add other methods (createEvent, getEvent, updateEvent, deleteEvent) here...
}

