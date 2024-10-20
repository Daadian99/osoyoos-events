<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Event;
use App\Models\Location;
use App\Models\Category;
use Exception;
use PDO;

class EventController
{
    private $eventModel;
    private $locationModel;
    private $categoryModel;
    private $db;

    public function __construct(Event $eventModel, Location $locationModel, Category $categoryModel, PDO $db)
    {
        $this->eventModel = $eventModel;
        $this->locationModel = $locationModel;
        $this->categoryModel = $categoryModel;
        $this->db = $db;
    }

    public function getEvents(Request $request, Response $response): Response
    {
        error_log("EventController: getEvents method called");
        
        try {
            $queryParams = $request->getQueryParams();
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $locationSlug = isset($queryParams['location']) ? $queryParams['location'] : null;

            error_log("Page: " . $page . ", Location Slug: " . ($locationSlug ?? 'null'));

            if ($locationSlug) {
                $location = $this->locationModel->getLocationBySlug($locationSlug);
                if (!$location) {
                    error_log("Location not found for slug: " . $locationSlug);
                    return $this->jsonResponse($response, ['error' => 'Location not found'], 404);
                }
                $events = $this->eventModel->getEventsByLocation($location['id'], $page);
            } else {
                $events = $this->eventModel->getAllEvents($page);
            }

            error_log("Number of events retrieved: " . count($events));

            if (empty($events)) {
                error_log("No events found");
                return $this->jsonResponse($response, ['events' => []], 200);
            }

            return $this->jsonResponse($response, ['events' => $events]);
        } catch (\Exception $e) {
            error_log("Error in getEvents: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse($response, ['error' => 'Internal server error'], 500);
        }
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

        $categoryIds = $data['category_ids'] ?? [];
        
        try {
            $this->db->beginTransaction();
            
            $eventId = $this->eventModel->createEvent($data['title'], $data['description'], $data['date'], $data['location_id'], $user['id']);
            
            foreach ($categoryIds as $categoryId) {
                $this->categoryModel->addEventCategory($eventId, $categoryId);
            }
            
            $this->db->commit();
            
            $event = $this->eventModel->getEvent($eventId);
            $event['categories'] = $this->categoryModel->getEventCategories($eventId);
            
            return $this->jsonResponse($response, $event, 201);
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, ['error' => 'Failed to create event: ' . $e->getMessage()], 500);
        }
    }

    public function getEvent(Request $request, Response $response, array $args): Response
    {
        try {
            error_log('Fetching event with ID: ' . $args['id']);
            $event = $this->eventModel->getEventWithOrganizer($args['id']);
            error_log('Event data: ' . json_encode($event));
            
            if ($event) {
                // Format the date to ISO 8601
                $date = new \DateTime($event['date']);
                $formattedDate = $date->format('c'); // 'c' format gives ISO 8601 date

                $eventData = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'description' => $event['description'],
                    'date' => $formattedDate, // Use the formatted date
                    'location' => $event['location'],
                    'organizer_id' => $event['organizer_id'],
                    'organizer_name' => $event['organizer_name'] ?? null,
                ];
                return $this->jsonResponse($response, $eventData);
            } else {
                error_log('Event not found for ID: ' . $args['id']);
                return $this->jsonResponse($response, ['error' => 'Event not found'], 404);
            }
        } catch (Exception $e) {
            error_log('Error in getEvent: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return $this->jsonResponse($response, ['error' => 'Internal server error'], 500);
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
        $categoryIds = $data['category_ids'] ?? null;

        try {
            $this->db->beginTransaction();
            
            $success = $this->eventModel->updateEvent($id, $title, $description, $date, $location);
            
            if ($categoryIds !== null) {
                // Remove all existing categories
                $existingCategories = $this->categoryModel->getEventCategories($id);
                foreach ($existingCategories as $category) {
                    $this->categoryModel->removeEventCategory($id, $category['id']);
                }
                
                // Add new categories
                foreach ($categoryIds as $categoryId) {
                    $this->categoryModel->addEventCategory($id, $categoryId);
                }
            }
            
            $this->db->commit();
            
            if ($success) {
                $updatedEvent = $this->eventModel->getEvent($id);
                $updatedEvent['categories'] = $this->categoryModel->getEventCategories($id);
                return $this->jsonResponse($response, $updatedEvent);
            } else {
                return $this->jsonResponse($response, ['error' => 'Failed to update event'], 500);
            }
        } catch (Exception $e) {
            $this->db->rollBack();
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

    public function getEventTicketTypes(Request $request, Response $response, array $args): Response
    {
        try {
            $eventId = $args['id'];
            $ticketTypes = $this->eventModel->getTicketTypesForEvent($eventId);
            
            $response->getBody()->write(json_encode($ticketTypes));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $errorResponse = [
                'error' => 'Failed to fetch ticket types',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorResponse));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function getAllEvents(Request $request, Response $response): Response
    {
        try {
            $events = $this->eventModel->getAllEvents();
            return $this->respondWithJson($response, $events);
        } catch (\Exception $e) {
            // Log the error
            error_log($e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to fetch events'], 500);
        }
    }

    protected function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    private function respondWithJson(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
