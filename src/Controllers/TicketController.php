<?php
namespace App\Controllers;

use App\Models\Ticket;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Tickets",
 *     description="Ticket management operations"
 * )
 */
class TicketController {
    private $ticket;

    public function __construct(Ticket $ticket) {
        $this->ticket = $ticket;
    }

    /**
     * @OA\Post(
     *     path="/events/{eventId}/tickets",
     *     summary="Create a new ticket for an event",
     *     tags={"Tickets"},
     *     @OA\Parameter(
     *         name="eventId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="ticket_type", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="quantity", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Ticket created successfully"),
     *     @OA\Response(response="400", description="Invalid input"),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function createTicket(Request $request, Response $response, $args) {
        $eventId = $args['eventId'];
        $data = $request->getParsedBody();

        $requiredFields = ['ticket_type', 'price', 'quantity'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->jsonResponse($response, ['error' => "Missing required field: $field"], 400);
            }
        }

        $ticketId = $this->ticket->createTicket(
            $eventId,
            $data['ticket_type'],
            $data['price'],
            $data['quantity']
        );

        if ($ticketId) {
            return $this->jsonResponse($response, ['message' => 'Ticket created successfully', 'id' => $ticketId], 201);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to create ticket'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/events/{eventId}/tickets",
     *     summary="Get all tickets for an event",
     *     @OA\Parameter(
     *         name="eventId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Successful operation"),
     *     @OA\Response(response="500", description="Server error")
     * )
     */
    public function getTicketsByEvent(Request $request, Response $response, $args) {
        $eventId = $args['eventId'];
        $tickets = $this->ticket->getTicketsByEvent($eventId);

        if ($tickets !== false) {
            return $this->jsonResponse($response, $tickets);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to fetch tickets'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/tickets/{ticketId}",
     *     summary="Update an existing ticket",
     *     @OA\Parameter(
     *         name="ticketId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="ticket_type", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="quantity", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Ticket updated successfully"),
     *     @OA\Response(response="400", description="Invalid input"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="404", description="Ticket not found")
     * )
     */
    public function updateTicket(Request $request, Response $response, $args) {
        $ticketId = $args['ticketId'];
        $data = $request->getParsedBody();

        $requiredFields = ['ticket_type', 'price', 'quantity'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->jsonResponse($response, ['error' => "Missing required field: $field"], 400);
            }
        }

        $success = $this->ticket->updateTicket(
            $ticketId,
            $data['ticket_type'],
            $data['price'],
            $data['quantity']
        );

        if ($success) {
            return $this->jsonResponse($response, ['message' => 'Ticket updated successfully']);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to update ticket'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/tickets/{ticketId}",
     *     summary="Delete a ticket",
     *     @OA\Parameter(
     *         name="ticketId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Ticket deleted successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="404", description="Ticket not found")
     * )
     */
    public function deleteTicket(Request $request, Response $response, $args) {
        $ticketId = $args['ticketId'];

        $success = $this->ticket->deleteTicket($ticketId);

        if ($success) {
            return $this->jsonResponse($response, ['message' => 'Ticket deleted successfully']);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to delete ticket'], 500);
        }
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
