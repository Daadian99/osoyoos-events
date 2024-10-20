<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Location;
use App\Services\EmailService;

class TicketController
{
    private $ticketModel;
    private $eventModel;
    private $locationModel;
    private $emailService;

    public function __construct(Ticket $ticketModel, Event $eventModel, Location $locationModel, EmailService $emailService)
    {
        $this->ticketModel = $ticketModel;
        $this->eventModel = $eventModel;
        $this->locationModel = $locationModel;
        $this->emailService = $emailService;
    }

    public function createTicket(Request $request, Response $response, array $args): Response
    {
        $eventId = $args['eventId'];
        $data = $request->getParsedBody();

        // Check if the event exists
        $event = $this->eventModel->getEvent($eventId);
        if (!$event) {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        // Validate input
        if (!isset($data['ticket_type']) || !isset($data['price']) || !isset($data['quantity'])) {
            return $this->jsonResponse($response, ['error' => 'Missing required fields'], 400);
        }

        // Create the ticket
        $ticketId = $this->ticketModel->createTicket($eventId, $data['ticket_type'], $data['price'], $data['quantity']);

        if (!$ticketId) {
            return $this->jsonResponse($response, ['error' => 'Failed to create ticket'], 500);
        }

        $ticket = $this->ticketModel->getTicket($ticketId, $eventId);
        return $this->jsonResponse($response, $ticket);
    }

    public function getTicketsByEvent(Request $request, Response $response, array $args): Response
    {
        $eventId = $args['eventId'];

        // Check if the event exists
        $event = $this->eventModel->getEvent($eventId);
        if (!$event) {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        // Get all tickets for the event
        $tickets = $this->ticketModel->getTicketsByEvent($eventId);

        return $this->jsonResponse($response, [
            'event' => $event,
            'tickets' => $tickets
        ]);
    }

    public function getTicket(Request $request, Response $response, array $args): Response
    {
        $eventId = $args['eventId'];
        $ticketId = $args['ticketId'];
        error_log("Attempting to get ticket ID: $ticketId for event ID: $eventId");

        // Check if the event exists
        $event = $this->eventModel->getEvent($eventId);
        if (!$event) {
            error_log("Event not found for ID: $eventId");
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        $ticket = $this->ticketModel->getTicket($ticketId, $eventId);
        if (!$ticket) {
            error_log("Ticket not found for ID: $ticketId and event ID: $eventId");
            return $this->jsonResponse($response, ['error' => 'Ticket not found'], 404);
        }

        error_log("Retrieved ticket: " . json_encode($ticket));
        return $this->jsonResponse($response, $ticket);
    }

    public function updateTicket(Request $request, Response $response, array $args): Response
    {
        $eventId = $args['eventId'];
        $ticketId = $args['ticketId'];
        $data = $request->getParsedBody();

        // Check if the event exists
        $event = $this->eventModel->getEvent($eventId);
        if (!$event) {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        // Check if the ticket exists
        $ticket = $this->ticketModel->getTicket($ticketId, $eventId);
        if (!$ticket) {
            return $this->jsonResponse($response, ['error' => 'Ticket not found'], 404);
        }

        // Validate input
        if (!isset($data['ticket_type']) || !isset($data['price']) || !isset($data['quantity'])) {
            return $this->jsonResponse($response, ['error' => 'Missing required fields'], 400);
        }

        // Update the ticket
        $updatedTicket = $this->ticketModel->updateTicket($ticketId, $eventId, $data);
        if (!$updatedTicket) {
            return $this->jsonResponse($response, ['error' => 'Failed to update ticket'], 500);
        }

        return $this->jsonResponse($response, $updatedTicket);
    }

    public function deleteTicket(Request $request, Response $response, array $args): Response
    {
        $eventId = $args['eventId'];
        $ticketId = $args['ticketId'];

        // Check if the event exists
        $event = $this->eventModel->getEvent($eventId);
        if (!$event) {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        // Check if the ticket exists
        $ticket = $this->ticketModel->getTicket($ticketId, $eventId);
        if (!$ticket) {
            return $this->jsonResponse($response, ['error' => 'Ticket not found'], 404);
        }

        // Delete the ticket
        $result = $this->ticketModel->deleteTicket($ticketId, $eventId);
        if (!$result) {
            return $this->jsonResponse($response, ['error' => 'Failed to delete ticket'], 500);
        }

        return $this->jsonResponse($response, ['message' => 'Ticket deleted successfully']);
    }

    public function purchaseTicket(Request $request, Response $response, array $args): Response
    {
        $eventId = $args['eventId'];
        $ticketId = $args['ticketId'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Validate input
        $errors = $this->validatePurchaseInput($data, $user);
        if (!empty($errors)) {
            return $this->jsonResponse($response, ['errors' => $errors], 400);
        }

        $quantity = (int)$data['quantity'];

        try {
            // Check if the event exists
            $event = $this->eventModel->getEvent($eventId);
            if (!$event) {
                return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
            }

            // Check if the ticket exists
            $ticket = $this->ticketModel->getTicket($ticketId, $eventId);
            if (!$ticket) {
                return $this->jsonResponse($response, ['error' => 'Ticket not found'], 404);
            }

            // Release expired reservations
            $this->ticketModel->releaseExpiredReservations();

            // Check ticket availability
            $availableQuantity = $this->ticketModel->getAvailableQuantity($ticketId);
            if ($availableQuantity < $quantity) {
                return $this->jsonResponse($response, ['error' => "Only {$availableQuantity} tickets available"], 400);
            }

            // Check if the user has already purchased the maximum allowed tickets
            $userPurchasedQuantity = $this->ticketModel->getUserPurchasedQuantity($user['id'], $eventId);
            $maxTicketsPerUser = 10; // You can adjust this value or make it configurable
            if ($userPurchasedQuantity + $quantity > $maxTicketsPerUser) {
                return $this->jsonResponse($response, ['error' => "You can only purchase up to {$maxTicketsPerUser} tickets for this event"], 400);
            }

            // Create a temporary reservation
            if (!$this->ticketModel->createTemporaryReservation($ticketId, $quantity, $user['id'])) {
                throw new \Exception('Failed to create temporary reservation');
            }

            // Perform the purchase
            $purchaseId = $this->ticketModel->purchaseTicket($ticketId, $user['id'], $quantity);

            if ($purchaseId) {
                // Send confirmation email
                $totalPrice = $quantity * $ticket['price'];
                $this->emailService->sendPurchaseConfirmation($user['email'], $event['title'], $ticket['ticket_type'], $quantity, $totalPrice);

                return $this->jsonResponse($response, [
                    'message' => 'Ticket purchased successfully',
                    'purchase_id' => $purchaseId
                ], 201);
            } else {
                throw new \Exception('Failed to purchase ticket');
            }
        } catch (\Exception $e) {
            error_log('Error in purchaseTicket: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function getUserTickets(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        try {
            $purchases = $this->ticketModel->getUserPurchases($user['id']);
            return $this->jsonResponse($response, ['purchases' => $purchases]);
        } catch (Exception $e) {
            error_log('Error in getUserTickets: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'An error occurred while fetching your tickets'], 500);
        }
    }

    public function cancelTicket(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $purchaseId = $args['purchaseId'];
        
        if (!$user || !isset($user['id'])) {
            return $this->jsonResponse($response, ['error' => 'User not authenticated'], 401);
        }

        $userId = $user['id'];
        $result = $this->ticketModel->cancelTicketPurchase($userId, $purchaseId);

        if ($result === true) {
            return $this->jsonResponse($response, ['message' => 'Ticket cancelled successfully']);
        } elseif (is_string($result)) {
            return $this->jsonResponse($response, ['error' => $result], 400);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to cancel ticket'], 500);
        }
    }

    public function getTicketHistory(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if (!$user || !isset($user['id'])) {
            return $this->jsonResponse($response, ['error' => 'User not authenticated'], 401);
        }

        $userId = $user['id'];
        $history = $this->ticketModel->getUserTicketHistory($userId);

        return $this->jsonResponse($response, ['ticket_history' => $history]);
    }

    public function getTicketDetails(Request $request, Response $response, array $args): Response
    {
        $purchaseId = $args['purchaseId'];
        $user = $request->getAttribute('user');

        $ticketDetails = $this->ticketModel->getTicketDetailsByPurchaseId($purchaseId, $user['id']);

        if (!$ticketDetails) {
            return $this->jsonResponse($response, ['error' => 'Ticket purchase not found'], 404);
        }

        return $this->jsonResponse($response, $ticketDetails);
    }

    public function cancelTicketPurchase(Request $request, Response $response, array $args): Response
    {
        $purchaseId = $args['purchaseId'];
        $user = $request->getAttribute('user');

        try {
            $result = $this->ticketModel->cancelTicketPurchase($purchaseId, $user['id']);

            if ($result === false) {
                return $this->jsonResponse($response, ['error' => 'Unable to cancel ticket purchase'], 400);
            }

            if ($result === 0) {
                return $this->jsonResponse($response, ['error' => 'Ticket purchase not found or already cancelled'], 404);
            }

            // If we get here, the cancellation was successful
            return $this->jsonResponse($response, ['message' => 'Ticket purchase cancelled successfully'], 200);
        } catch (\Exception $e) {
            error_log('Error in cancelTicketPurchase: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    private function validatePurchaseInput($data, $user)
    {
        $errors = [];

        if (!isset($data['quantity'])) {
            $errors[] = 'Quantity is required';
        } elseif (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors[] = 'Quantity must be a positive number';
        }

        if (!isset($user['id'])) {
            $errors[] = 'User is not authenticated';
        }

        return $errors;
    }

    public function purchaseTickets(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('userId'); // Assuming you have middleware that sets this

        try {
            $result = $this->ticketModel->purchaseTickets($userId, $data['eventId'], $data['tickets']);
            
            $responseData = [
                'success' => true,
                'message' => 'Tickets purchased successfully',
                'data' => $result
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $responseData = [
                'success' => false,
                'message' => 'Failed to purchase tickets',
                'error' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}
