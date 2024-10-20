<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Ticket
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createTicket($eventId, $ticketType, $price, $quantity)
    {
        $sql = "INSERT INTO tickets (event_id, ticket_type, price, quantity) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId, $ticketType, $price, $quantity]);
        return $this->db->lastInsertId();
    }

    public function getTicketsByEvent($eventId)
    {
        $sql = "SELECT * FROM tickets WHERE event_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTicket($ticketId, $eventId)
    {
        $sql = "SELECT * FROM tickets WHERE id = ? AND event_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ticketId, $eventId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("SQL query for getTicket: $sql with ticket ID: $ticketId and event ID: $eventId");
        error_log("Retrieved ticket: " . json_encode($ticket));
        return $ticket;
    }

    public function updateTicket($ticketId, $eventId, $data)
    {
        try {
            // First, check if the ticket exists
            $checkSql = "SELECT * FROM tickets WHERE id = ? AND event_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$ticketId, $eventId]);
            $ticket = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                return false; // Ticket not found
            }

            // If ticket exists, proceed with update
            $sql = "UPDATE tickets SET ticket_type = ?, price = ?, quantity = ? WHERE id = ? AND event_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['ticket_type'],
                $data['price'],
                $data['quantity'],
                $ticketId,
                $eventId
            ]);

            return $result;
        } catch (PDOException $e) {
            // Log the error and return false
            error_log("Error updating ticket: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTicket($ticketId, $eventId)
    {
        $sql = "DELETE FROM tickets WHERE id = ? AND event_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$ticketId, $eventId]);
    }

    public function purchaseTicket($ticketId, $userId, $quantity)
    {
        try {
            $this->db->beginTransaction();

            // Check available quantity
            $availableQuantity = $this->getAvailableQuantity($ticketId);
            if ($availableQuantity < $quantity) {
                throw new Exception("Not enough tickets available. Requested: $quantity, Available: $availableQuantity");
            }

            // Update ticket quantity
            $stmt = $this->db->prepare("UPDATE tickets SET quantity = quantity - :quantity WHERE id = :id");
            $stmt->execute(['quantity' => $quantity, 'id' => $ticketId]);

            // Record the purchase
            $stmt = $this->db->prepare("INSERT INTO ticket_purchases (user_id, ticket_id, quantity, purchase_date) VALUES (:user_id, :ticket_id, :quantity, NOW())");
            $stmt->execute([
                'user_id' => $userId,
                'ticket_id' => $ticketId,
                'quantity' => $quantity
            ]);

            $purchaseId = $this->db->lastInsertId();

            // Remove the reservation
            $stmt = $this->db->prepare("DELETE FROM ticket_reservations WHERE ticket_id = :ticketId AND user_id = :userId");
            $stmt->execute(['ticketId' => $ticketId, 'userId' => $userId]);

            $this->db->commit();
            return $purchaseId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error in purchaseTicket: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getUserPurchasedTickets($userId)
    {
        $sql = "SELECT tp.id as purchase_id, tp.quantity, tp.purchase_date, 
                       t.id as ticket_id, t.ticket_type, t.price,
                       e.id as event_id, e.title as event_title, e.date as event_date
            FROM ticket_purchases tp
            JOIN tickets t ON tp.ticket_id = t.id
            JOIN events e ON t.event_id = e.id
            WHERE tp.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelTicketPurchase($purchaseId, $userId)
    {
        try {
            $this->db->beginTransaction();

            // First, get the purchase details
            $stmt = $this->db->prepare("
                SELECT tp.quantity, t.id as ticket_id, t.event_id
                FROM ticket_purchases tp
                JOIN tickets t ON tp.ticket_id = t.id
                WHERE tp.id = :purchaseId AND tp.user_id = :userId
            ");
            $stmt->execute(['purchaseId' => $purchaseId, 'userId' => $userId]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchase) {
                $this->db->rollBack();
                return 0; // Purchase not found
            }

            // Check if the event hasn't started yet
            $stmt = $this->db->prepare("
                SELECT 1 FROM events WHERE id = :eventId AND date > NOW()
            ");
            $stmt->execute(['eventId' => $purchase['event_id']]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                throw new Exception('Cannot cancel tickets for past or ongoing events');
            }

            // Update the ticket quantity
            $stmt = $this->db->prepare("
                UPDATE tickets 
                SET quantity = quantity + :quantity 
                WHERE id = :ticketId
            ");
            $stmt->execute([
                'quantity' => $purchase['quantity'],
                'ticketId' => $purchase['ticket_id']
            ]);

            // Delete the purchase
            $stmt = $this->db->prepare("
                DELETE FROM ticket_purchases 
                WHERE id = :purchaseId AND user_id = :userId
            ");
            $result = $stmt->execute([
                'purchaseId' => $purchaseId,
                'userId' => $userId
            ]);

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error in cancelTicketPurchase: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getUserTicketHistory($userId)
    {
        $sql = "SELECT tp.id as purchase_id, tp.quantity, tp.purchase_date, 
                   t.id as ticket_id, t.ticket_type, t.price,
                   e.id as event_id, e.title as event_title, e.date as event_date,
                   CASE WHEN tp.cancelled_at IS NOT NULL THEN 'Cancelled' ELSE 'Active' END as status,
                   tp.cancelled_at
            FROM ticket_purchases tp
            JOIN tickets t ON tp.ticket_id = t.id
            JOIN events e ON t.event_id = e.id
            WHERE tp.user_id = ?
            ORDER BY tp.purchase_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserPurchases($userId)
    {
        $stmt = $this->db->prepare("
            SELECT tp.id as purchase_id, t.id as ticket_id, e.id as event_id, e.title as event_title, 
                   t.ticket_type, tp.quantity, tp.purchase_date, (t.price * tp.quantity) as total_price
            FROM ticket_purchases tp
            JOIN tickets t ON tp.ticket_id = t.id
            JOIN events e ON t.event_id = e.id
            WHERE tp.user_id = :user_id
            ORDER BY tp.purchase_date DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function releaseExpiredReservations()
    {
        $stmt = $this->db->prepare("
            DELETE FROM ticket_reservations
            WHERE expiration_time < NOW()
        ");
        return $stmt->execute();
    }

    public function getAvailableQuantity($ticketId)
    {
        $stmt = $this->db->prepare("
            SELECT t.quantity - COALESCE(SUM(tr.quantity), 0) as available
            FROM tickets t
            LEFT JOIN ticket_reservations tr ON t.id = tr.ticket_id
            WHERE t.id = :ticketId
            GROUP BY t.id, t.quantity
        ");
        $stmt->execute(['ticketId' => $ticketId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['available'] : 0;
    }

    public function createTemporaryReservation($ticketId, $quantity, $userId)
    {
        $expirationTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $stmt = $this->db->prepare("
            INSERT INTO ticket_reservations (ticket_id, user_id, quantity, expiration_time)
            VALUES (:ticketId, :userId, :quantity, :expirationTime)
        ");
        return $stmt->execute([
            'ticketId' => $ticketId,
            'userId' => $userId,
            'quantity' => $quantity,
            'expirationTime' => $expirationTime
        ]);
    }

    public function getUserPurchasedQuantity($userId, $eventId)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(tp.quantity), 0) as total_purchased
            FROM ticket_purchases tp
            JOIN tickets t ON tp.ticket_id = t.id
            WHERE tp.user_id = :userId AND t.event_id = :eventId
        ");
        $stmt->execute(['userId' => $userId, 'eventId' => $eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total_purchased'];
    }

    public function getTicketDetailsByPurchaseId($purchaseId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT tp.id as purchase_id, t.id as ticket_id, e.id as event_id, e.title as event_title,
                   t.ticket_type, tp.quantity, tp.purchase_date, (t.price * tp.quantity) as total_price
            FROM ticket_purchases tp
            JOIN tickets t ON tp.ticket_id = t.id
            JOIN events e ON t.event_id = e.id
            WHERE tp.id = :purchaseId AND tp.user_id = :userId
        ");
        $stmt->execute(['purchaseId' => $purchaseId, 'userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function purchaseTickets(int $userId, int $eventId, array $tickets): array
    {
        $this->db->beginTransaction();

        try {
            $purchaseId = $this->createPurchase($userId, $eventId);

            foreach ($tickets as $ticketTypeId => $quantity) {
                $this->reserveTickets($purchaseId, $ticketTypeId, $quantity);
            }

            $this->db->commit();
            return ['purchaseId' => $purchaseId];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function createPurchase(int $userId, int $eventId): int
    {
        $stmt = $this->db->prepare("INSERT INTO purchases (user_id, event_id, purchase_date) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $eventId]);
        return $this->db->lastInsertId();
    }

    private function reserveTickets(int $purchaseId, int $ticketTypeId, int $quantity): void
    {
        // Check available capacity
        $stmt = $this->db->prepare("SELECT capacity, (SELECT COUNT(*) FROM purchased_tickets WHERE ticket_type_id = ?) as sold FROM ticket_types WHERE id = ?");
        $stmt->execute([$ticketTypeId, $ticketTypeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['capacity'] - $result['sold'] < $quantity) {
            throw new \Exception("Not enough tickets available for ticket type {$ticketTypeId}");
        }

        // Reserve tickets
        $stmt = $this->db->prepare("INSERT INTO purchased_tickets (purchase_id, ticket_type_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$purchaseId, $ticketTypeId, $quantity]);
    }
}
