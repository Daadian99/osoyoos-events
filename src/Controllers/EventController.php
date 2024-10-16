<?php
namespace App\Controllers;

use App\Models\Event;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EventController {
    private $event;

    public function __construct(Event $event) {
        $this->event = $event;
    }

    public function createEvent(Request $request, Response $response) {
        error_log("Entering createEvent method");
        
        $data = $request->getParsedBody();
        $organizerId = $request->getAttribute('userId'); // From JWT middleware

        error_log('Received data: ' . json_encode($data));
        error_log('User ID from token: ' . $organizerId);

        if (!is_array($data)) {
            error_log('Parsed body is not an array. Raw body: ' . $request->getBody()->getContents());
            return $this->jsonResponse($response, ['error' => 'Invalid JSON data'], 400);
        }

        $requiredFields = ['title', 'description', 'date', 'location'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                error_log("Missing or empty required field: $field");
                return $this->jsonResponse($response, ['error' => "Missing required field: $field"], 400);
            }
        }

        $eventId = $this->event->createEvent(
            $data['title'],
            $data['description'],
            $data['date'],
            $data['location'],
            $organizerId
        );
        
        if ($eventId) {
            $responseData = [
                'message' => 'Event created successfully',
                'eventId' => $eventId,
                'title' => $data['title'],
                'description' => $data['description'],
                'date' => $data['date'],
                'location' => $data['location']
            ];
            return $this->jsonResponse($response, $responseData, 201);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to create event'], 500);
        }
    }

    public function getEvent(Request $request, Response $response, $args) {
        $eventId = $args['id'];
        $event = $this->event->getEvent($eventId);

        if ($event) {
            return $this->jsonResponse($response, $event);
        } else {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }
    }

    public function updateEvent(Request $request, Response $response, $args) {
        $eventId = $args['id'];
        $data = $request->getParsedBody();
        $organizerId = $request->getAttribute('userId'); // From JWT middleware

        // Check if the event exists and belongs to the current user
        $existingEvent = $this->event->getEvent($eventId);
        if (!$existingEvent || $existingEvent['organizer_id'] != $organizerId) {
            return $this->jsonResponse($response, ['error' => 'Event not found or you do not have permission to update it'], 404);
        }

        $requiredFields = ['title', 'description', 'date', 'location'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->jsonResponse($response, ['error' => "Missing required field: $field"], 400);
            }
        }

        $success = $this->event->updateEvent(
            $eventId,
            $data['title'],
            $data['description'],
            $data['date'],
            $data['location']
        );

        if ($success) {
            return $this->jsonResponse($response, ['message' => 'Event updated successfully']);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to update event'], 500);
        }
    }

    public function deleteEvent(Request $request, Response $response, $args) {
        $eventId = $args['id'];
        $organizerId = $request->getAttribute('userId'); // From JWT middleware

        // Check if the event exists and belongs to the current user
        $existingEvent = $this->event->getEvent($eventId);
        if (!$existingEvent || $existingEvent['organizer_id'] != $organizerId) {
            return $this->jsonResponse($response, ['error' => 'Event not found or you do not have permission to delete it'], 404);
        }

        $success = $this->event->deleteEvent($eventId);

        if ($success) {
            return $this->jsonResponse($response, ['message' => 'Event deleted successfully']);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to delete event'], 500);
        }
    }

    public function getAllEvents(Request $request, Response $response) {
        $events = $this->event->getAllEvents();

        if ($events !== false) {
            return $this->jsonResponse($response, $events);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to fetch events'], 500);
        }
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
