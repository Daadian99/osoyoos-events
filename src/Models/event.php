<?php
namespace App\Models;

use PDO;
use PDOException;

class Event {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function createEvent($title, $description, $date, $location, $organizerId) {
        $sql = "INSERT INTO events (title, description, date, location, organizer_id) VALUES (?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $description, $date, $location, $organizerId]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            return false;
        }
    }

    public function getEvent($id) {
        $sql = "SELECT * FROM events WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching event: " . $e->getMessage());
            return false;
        }
    }

    public function updateEvent($id, $title, $description, $date, $location) {
        $sql = "UPDATE events SET title = ?, description = ?, date = ?, location = ? WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $description, $date, $location, $id]);
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            return false;
        }
    }

    public function deleteEvent($id) {
        $sql = "DELETE FROM events WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting event: " . $e->getMessage());
            return false;
        }
    }

    public function getAllEvents() {
        $sql = "SELECT * FROM events ORDER BY date";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all events: " . $e->getMessage());
            return false;
        }
    }
}
