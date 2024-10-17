<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// At the beginning of the file
error_log("Index.php accessed");

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\UserController;
use App\Controllers\EventController;
use App\Controllers\TicketController;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Middleware\JwtAuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\OpenApiConfig;

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Set the base path
$app->setBasePath('/osoyoos-events/public');

// Database connection
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// JWT secret
$jwtSecret = $_ENV['JWT_SECRET'];

// Create model instances
$userModel = new User($pdo);
$eventModel = new Event($pdo);
$ticketModel = new Ticket($pdo);

// Create controller instances
$userController = new UserController($userModel, $jwtSecret);
$eventController = new EventController($eventModel);
$ticketController = new TicketController($ticketModel);

// Create middleware instances
$jwtMiddleware = new JwtAuthMiddleware($jwtSecret);
$roleMiddleware = new RoleMiddleware($userModel);

// User routes
/**
 * @OA\Post(
 *     path="/register",
 *     summary="Register a new user",
 *     tags={"Users"},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="username", type="string"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="password", type="string")
 *         )
 *     ),
 *     @OA\Response(response="200", description="User registered successfully"),
 *     @OA\Response(response="400", description="Invalid input")
 * )
 */
$app->post('/register', [$userController, 'register']);

/**
 * @OA\Post(
 *     path="/login",
 *     summary="Login a user",
 *     tags={"Users"},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="password", type="string")
 *         )
 *     ),
 *     @OA\Response(response="200", description="Login successful"),
 *     @OA\Response(response="401", description="Invalid credentials")
 * )
 */
$app->post('/login', [$userController, 'login']);

/**
 * @OA\Get(
 *     path="/users",
 *     summary="Get all users",
 *     tags={"Users"},
 *     @OA\Response(response="200", description="List of users"),
 *     @OA\Response(response="500", description="Server error")
 * )
 */
$app->get('/users', [$userController, 'getAllUsers']);

// Event routes
/**
 * @OA\Post(
 *     path="/events",
 *     summary="Create a new event",
 *     tags={"Events"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="date", type="string", format="date-time"),
 *             @OA\Property(property="location", type="string")
 *         )
 *     ),
 *     @OA\Response(response="201", description="Event created successfully"),
 *     @OA\Response(response="400", description="Invalid input"),
 *     @OA\Response(response="401", description="Unauthorized")
 * )
 */
$app->post('/events', [$eventController, 'createEvent'])
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    })
    ->add($jwtMiddleware);

/**
 * @OA\Get(
 *     path="/events",
 *     summary="Get all events",
 *     tags={"Events"},
 *     @OA\Response(response="200", description="List of events"),
 *     @OA\Response(response="500", description="Server error")
 * )
 */
$app->get('/events', [$eventController, 'getAllEvents']);

/**
 * @OA\Get(
 *     path="/events/{id}",
 *     summary="Get an event by ID",
 *     tags={"Events"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response="200", description="Event details"),
 *     @OA\Response(response="404", description="Event not found")
 * )
 */
$app->get('/events/{id}', [$eventController, 'getEvent']);

/**
 * @OA\Put(
 *     path="/events/{id}",
 *     summary="Update an event",
 *     tags={"Events"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="date", type="string", format="date-time"),
 *             @OA\Property(property="location", type="string")
 *         )
 *     ),
 *     @OA\Response(response="200", description="Event updated successfully"),
 *     @OA\Response(response="400", description="Invalid input"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="404", description="Event not found")
 * )
 */
$app->put('/events/{id}', [$eventController, 'updateEvent'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

/**
 * @OA\Delete(
 *     path="/events/{id}",
 *     summary="Delete an event",
 *     tags={"Events"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response="200", description="Event deleted successfully"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="404", description="Event not found")
 * )
 */
$app->delete('/events/{id}', [$eventController, 'deleteEvent'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

// Ticket routes
/**
 * @OA\Post(
 *     path="/events/{eventId}/tickets",
 *     summary="Create a new ticket for an event",
 *     tags={"Tickets"},
 *     security={{"bearerAuth":{}}},
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
$app->post('/events/{eventId}/tickets', [$ticketController, 'createTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

/**
 * @OA\Get(
 *     path="/events/{eventId}/tickets",
 *     summary="Get all tickets for an event",
 *     tags={"Tickets"},
 *     @OA\Parameter(
 *         name="eventId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response="200", description="List of tickets"),
 *     @OA\Response(response="404", description="Event not found")
 * )
 */
$app->get('/events/{eventId}/tickets', [$ticketController, 'getTicketsByEvent']);

/**
 * @OA\Put(
 *     path="/tickets/{ticketId}",
 *     summary="Update a ticket",
 *     tags={"Tickets"},
 *     security={{"bearerAuth":{}}},
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
$app->put('/tickets/{ticketId}', [$ticketController, 'updateTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

/**
 * @OA\Delete(
 *     path="/tickets/{ticketId}",
 *     summary="Delete a ticket",
 *     tags={"Tickets"},
 *     security={{"bearerAuth":{}}},
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
$app->delete('/tickets/{ticketId}', [$ticketController, 'deleteTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

// Debug route
/**
 * @OA\Get(
 *     path="/debug",
 *     summary="Debug endpoint",
 *     @OA\Response(response="200", description="Debug information")
 * )
 */
$app->get('/debug', function (Request $request, Response $response) {
    $userId = $request->getAttribute('userId');
    $username = $request->getAttribute('username');
    $role = $request->getAttribute('role');
    $data = [
        'userId' => $userId,
        'username' => $username,
        'role' => $role
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware);

// Serve Swagger UI
$app->get('/api-docs', function (Request $request, Response $response) {
    $swagger = file_get_contents(__DIR__ . '/swagger-ui.html');
    $response->getBody()->write($swagger);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/swagger.json', function (Request $request, Response $response) {
    $openapi = \OpenApi\Generator::scan([
        __DIR__ . '/../src/Controllers',
        __DIR__ . '/../src/OpenApiConfig.php'
    ]);
    $response->getBody()->write($openapi->toJson());
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/swagger', function ($request, $response) {
    $swagger = file_get_contents(__DIR__ . '/swagger.php');
    $response->getBody()->write($swagger);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("Welcome to Osoyoos Events API");
    return $response;
});

// Just before $app->run()
error_log("Routes registered, about to run the app");

$app->run();
