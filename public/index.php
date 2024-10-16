<?php
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

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Set the base path
$app->setBasePath('/osoyoos-events/public');

// Database connection
$host = 'localhost';
$dbname = 'osoyoos_events';
$username = 'root';
$password = 'Keighos01!';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// JWT secret
$jwtSecret = 'your-secret-key';

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
$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);

// Event routes
$app->post('/events', [$eventController, 'createEvent'])
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    })
    ->add($jwtMiddleware);

$app->get('/events', [$eventController, 'getAllEvents']);

$app->get('/events/{id}', [$eventController, 'getEvent']);

$app->put('/events/{id}', [$eventController, 'updateEvent'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

$app->delete('/events/{id}', [$eventController, 'deleteEvent'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

// Ticket routes
$app->post('/events/{eventId}/tickets', [$ticketController, 'createTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });
$app->get('/events/{eventId}/tickets', [$ticketController, 'getTicketsByEvent']);
$app->put('/tickets/{ticketId}', [$ticketController, 'updateTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });
$app->delete('/tickets/{ticketId}', [$ticketController, 'deleteTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

// Debug route
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

$app->run();
