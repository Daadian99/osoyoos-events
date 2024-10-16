<?php
namespace App\Models;

use PDO;
use PDOException;

class Ticket {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function createTicket($eventId, $ticketType, $price, $quantity) {
        $sql = "INSERT INTO tickets (event_id, ticket_type, price, quantity) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId, $ticketType, $price, $quantity]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating ticket: " . $e->getMessage());
            return false;
        }
    }

    public function getTicketsByEvent($eventId) {
        $sql = "SELECT * FROM tickets WHERE event_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching tickets: " . $e->getMessage());
            return false;
        }
    }

    public function updateTicket($ticketId, $ticketType, $price, $quantity) {
        $sql = "UPDATE tickets SET ticket_type = ?, price = ?, quantity = ? WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$ticketType, $price, $quantity, $ticketId]);
        } catch (PDOException $e) {
            error_log("Error updating ticket: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTicket($ticketId) {
        $sql = "DELETE FROM tickets WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$ticketId]);
        } catch (PDOException $e) {
            error_log("Error deleting ticket: " . $e->getMessage());
            return false;
        }
    }
}

