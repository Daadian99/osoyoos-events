<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');


header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}



// Add this line to test direct file writing
file_put_contents(__DIR__ . '/../logs/direct-log.txt', "Script started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\UserController;
use App\Controllers\EventController;
use App\Controllers\TicketController;
use App\Controllers\CategoryController;
use App\Models\Category;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Middleware\JwtAuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\EmailService;
use App\OpenApiConfig;
use DI\Container;
use DI\ContainerBuilder;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;


require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
// After line 31, add:
// Database connection
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// JWT secret
$jwtSecret = $_ENV['JWT_SECRET'];

// Set up the dependency injection container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
 
        PDO::class => function () use ($dsn, $username, $password) {
            return new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        },
    User::class => function (Container $c) {
        return new User($c->get(PDO::class));
    },
    Event::class => function (Container $c) {
        return new Event($c->get(PDO::class));
    },
    Ticket::class => function (Container $c) {
        return new Ticket($c->get(PDO::class));
    },
    App\Models\Location::class => function (Container $c) {
        return new App\Models\Location($c->get(PDO::class));
    },
    App\Services\EmailService::class => function () {
        return new App\Services\EmailService();
    },
    UserController::class => function (Container $c) use ($jwtSecret) {
        return new UserController($c->get(User::class), $jwtSecret);
    },
    EventController::class => function (Container $c) {
        return new EventController(
            $c->get(Event::class),
            $c->get(App\Models\Location::class),
            $c->get(App\Models\Category::class),
            $c->get(PDO::class)
        );
    },
    TicketController::class => function (Container $c) {
        return new TicketController(
            $c->get(Ticket::class),
            $c->get(Event::class),
            $c->get(App\Models\Location::class),
            $c->get(EmailService::class)
        );
    },
    App\Controllers\LocationController::class => function (Container $c) {
        return new App\Controllers\LocationController($c->get(App\Models\Location::class));
    },
]);

$container = $containerBuilder->build();

// Create the app with the container
$app = AppFactory::create();

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $payload = ['error' => $exception->getMessage()];
    
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(500);
};

// Set the custom error handler
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Set the base path
$app->setBasePath('');


// Create middleware instances
$jwtMiddleware = new JwtAuthMiddleware($jwtSecret);
$roleMiddleware = new RoleMiddleware($container->get(User::class));



$app->add(function ($request, $handler) {
    error_log("Incoming request: " . $request->getMethod() . " " . $request->getUri()->getPath());
    error_log("Headers: " . json_encode($request->getHeaders()));
    return $handler->handle($request);
});

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
$app->post('/register', [$container->get(UserController::class), 'register']);

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
$app->post('/login', [$container->get(UserController::class), 'login']);

/**
 * @OA\Get(
 *     path="/users",
 *     summary="Get all users",
 *     tags={"Users"},
 *     @OA\Response(response="200", description="List of users"),
 *     @OA\Response(response="500", description="Server error")
 * )
 */
$app->get('/users', [$container->get(UserController::class), 'getAllUsers']);

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
$app->post('/events', [$container->get(EventController::class), 'createEvent'])->add($jwtMiddleware);

/**
 * @OA\Get(
 *     path="/events",
 *     summary="Get all events",
 *     tags={"Events"},
 *     @OA\Response(response="200", description="List of events"),
 *     @OA\Response(response="500", description="Server error")
 * )
 */
$app->get('/events', [$container->get(EventController::class), 'getEvents']);
$app->get('/{location}/events', [$container->get(EventController::class), 'getEvents']);

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
$app->get('/events/{id}', [$container->get(EventController::class), 'getEvent']);

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
$app->put('/events/{id}', [$container->get(EventController::class), 'updateEvent'])
    ->add($jwtMiddleware);

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
$app->delete('/events/{id}', [$container->get(EventController::class), 'deleteEvent'])->add($jwtMiddleware);

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
$app->post('/events/{eventId}/tickets', [$container->get(TicketController::class), 'createTicket'])
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    })
    ->add($jwtMiddleware);

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
$app->get('/events/{eventId}/tickets', [$container->get(TicketController::class), 'getTicketsByEvent']);
  
/**
 * @OA\Get(
 *     path="/events/{eventId}/tickets/{ticketId}",
 *     summary="Get a ticket by ID",
 *     tags={"Tickets"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="eventId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="ticketId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response="200", description="Ticket details"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="404", description="Ticket not found")
 * )
 */
$app->get('/events/{eventId}/tickets/{ticketId}', [$container->get(TicketController::class), 'getTicket']);

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
$app->put('/tickets/{ticketId}', [$container->get(TicketController::class), 'updateTicket'])
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
$app->delete('/tickets/{ticketId}', [$container->get(TicketController::class), 'deleteTicket'])
    ->add($jwtMiddleware)
    ->add(function($request, $handler) use ($roleMiddleware) {
        return $roleMiddleware($request, $handler, ['organizer', 'admin']);
    });

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    $existingContent = (string) $response->getBody();
    error_log("Response body: " . $existingContent);
    return $response;
});

$app->put('/events/{eventId}/tickets/{ticketId}', [$container->get(TicketController::class), 'updateTicket']);
$app->delete('/events/{eventId}/tickets/{ticketId}', [$container->get(TicketController::class), 'deleteTicket']);

$app->post('/events/{eventId}/tickets/{ticketId}/purchase', [$container->get(TicketController::class), 'purchaseTicket'])
    ->add($jwtMiddleware);

$app->get('/user/tickets', [$container->get(TicketController::class), 'getUserTickets'])
    ->add($jwtMiddleware);

$app->post('/user/tickets/{purchaseId}/cancel', [$container->get(TicketController::class), 'cancelTicket'])
    ->add($jwtMiddleware);

$app->get('/user/ticket-history', [$container->get(TicketController::class), 'getTicketHistory'])
    ->add($jwtMiddleware);

$app->get('/locations', [$container->get(App\Controllers\LocationController::class), 'getLocations']);

$app->get('/user/tickets/{purchaseId}', [$container->get(TicketController::class), 'getTicketDetails']);

$app->delete('/user/tickets/{purchaseId}', [$container->get(TicketController::class), 'cancelTicketPurchase'])
    ->add($jwtMiddleware);

// Add these lines to your routes
$app->post('/groups', [App\Controllers\GroupController::class, 'createGroup'])
    ->add($jwtMiddleware);
$app->get('/groups/suggestions', [App\Controllers\GroupController::class, 'getGroupSuggestions'])
    ->add($jwtMiddleware);

$app->get('/events/{id}/ticket-types', [$container->get(EventController::class), 'getEventTicketTypes']);

$app->post('/events/{id}/purchase', [$container->get(TicketController::class), 'purchaseTickets'])
    ->add($jwtMiddleware);

// Add this to your container definitions
$container->set(App\Models\Category::class, function (Container $c) {
    return new App\Models\Category($c->get(PDO::class));
});

$container->set(CategoryController::class, function (Container $c) {
    return new CategoryController($c->get(App\Models\Category::class));
});

// Add these routes
$app->get('/categories', [$container->get(App\Controllers\CategoryController::class), 'getAllCategories']);
$app->post('/categories', [$container->get(App\Controllers\CategoryController::class), 'createCategory'])
    ->add($jwtMiddleware)
    ->add(new RoleMiddleware(['admin']));

$app->any('{route:.*}', function (Request $request, Response $response) {
    $route = $request->getAttribute('route');
    error_log("Catch-all route hit. Requested path: " . $route);
    $response->getBody()->write(json_encode(['error' => 'Route not found', 'path' => $route]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
});

$routes = $app->getRouteCollector()->getRoutes();
$routeInfo = [];
foreach ($routes as $route) {
    $routeInfo[] = [
        'method' => $route->getMethods(),
        'pattern' => $route->getPattern(),
    ];
}
error_log("Routes defined: " . json_encode($routeInfo));

$app->run();






















