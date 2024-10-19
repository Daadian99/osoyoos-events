<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Event;
use App\Models\Location;
use Exception;

class EventController
{
    private $eventModel;
    private $locationModel;

    public function __construct($eventModel, $locationModel)
    {
        $this->eventModel = $eventModel;
        $this->locationModel = $locationModel;
    }

    public function getEvents(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $locationSlug = isset($queryParams['location']) ? $queryParams['location'] : null;

        if ($locationSlug) {
            $location = $this->locationModel->getLocationBySlug($locationSlug);
            if (!$location) {
                return $this->jsonResponse($response, ['error' => 'Location not found'], 404);
            }
            $events = $this->eventModel->getEventsByLocation($location['id'], $page);
        } else {
            $events = $this->eventModel->getAllEvents($page);
        }

        return $this->jsonResponse($response, ['events' => $events]);
    }

    public function createEvent(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Validate input
        $requiredFields = ['title', 'description', 'date', 'location_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->jsonResponse($response, ['error' => "Missing required field: $field"], 400);
            }
        }

        // Check if the location exists
        $location = $this->locationModel->getLocationById($data['location_id']);
        if (!$location) {
            return $this->jsonResponse($response, ['error' => 'Invalid location'], 400);
        }

        $eventId = $this->eventModel->createEvent($data['title'], $data['description'], $data['date'], $data['location_id'], $user['id']);

        if ($eventId) {
            return $this->jsonResponse($response, ['message' => 'Event created successfully', 'event_id' => $eventId], 201);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to create event'], 500);
        }
    }

    public function getEvent(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $event = $this->eventModel->getEvent($id);

        if ($event) {
            return $this->jsonResponse($response, $event);
        } else {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }
    }

    public function updateEvent(Request $request, Response $response, array $args): Response
    {
        error_log('EventController: updateEvent method called');
        $user = $request->getAttribute('user');
        error_log('User attribute: ' . json_encode($user));

        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'User not authenticated'], 401);
        }

        $id = $args['id'];
        $event = $this->eventModel->getEvent($id);

        if (!$event) {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        if ($user['role'] !== 'admin' && $user['id'] !== $event['organizer_id']) {
            return $this->jsonResponse($response, ['error' => 'You do not have permission to update this event'], 403);
        }

        $data = $request->getParsedBody();
        $title = $data['title'] ?? $event['title'];
        $description = $data['description'] ?? $event['description'];
        $date = $data['date'] ?? $event['date'];
        $location = $data['location'] ?? $event['location'];

        try {
            $success = $this->eventModel->updateEvent($id, $title, $description, $date, $location);
            if ($success) {
                $updatedEvent = $this->eventModel->getEvent($id);
                return $this->jsonResponse($response, $updatedEvent);
            } else {
                return $this->jsonResponse($response, ['error' => 'Failed to update event'], 500);
            }
        } catch (Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to update event: ' . $e->getMessage()], 500);
        }
    }

    public function deleteEvent(Request $request, Response $response, array $args): Response
    {
        error_log('EventController: deleteEvent method called');
        $user = $request->getAttribute('user');
        error_log('User attribute: ' . json_encode($user));

        $id = $args['id'];

        // Check if the event exists
        $event = $this->eventModel->getEvent($id);
        if (!$event) {
            return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
        }

        // Check if the user has permission to delete the event
        if ($user['role'] !== 'admin' && $user['id'] !== $event['organizer_id']) {
            return $this->jsonResponse($response, ['error' => 'You do not have permission to delete this event'], 403);
        }

        try {
            $success = $this->eventModel->deleteEvent($id);
            if ($success) {
                return $this->jsonResponse($response, ['message' => 'Event deleted successfully']);
            } else {
                return $this->jsonResponse($response, ['error' => 'Failed to delete event'], 500);
            }
        } catch (Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to delete event: ' . $e->getMessage()], 500);
        }
    }

    public function getLocations(Request $request, Response $response): Response
    {
        $locations = $this->locationModel->getAllLocations();
        return $this->jsonResponse($response, ['locations' => $locations]);
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
