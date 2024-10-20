<?php
// At the top of the file
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use App\Middleware\JwtAuthMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

// Configure database connection
$container->set(PDO::class, function () {
    $host = 'localhost';
    $dbname = 'osoyoos_events';
    $username = 'root';
    $password = 'Keighos01!';
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
});

AppFactory::setContainer($container);

$app = AppFactory::create();

// Add JWT authentication middleware
$jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key';
$jwtMiddleware = new JwtAuthMiddleware($jwtSecret);

// Define routes
$app->post('/register', [\App\Controllers\UserController::class, 'register']);
$app->post('/login', [\App\Controllers\UserController::class, 'login']);

// Public routes (no authentication required)
$app->get('/events', [\App\Controllers\EventController::class, 'getEvents']);
$app->get('/events/{id}', [\App\Controllers\EventController::class, 'getEvent']);

// Protected routes (authentication required)
$app->group('', function ($app) {
    $app->get('/users', [\App\Controllers\UserController::class, 'getAllUsers']);
    $app->post('/events', [\App\Controllers\EventController::class, 'createEvent']);
    $app->put('/events/{id}', [\App\Controllers\EventController::class, 'updateEvent']);
    $app->delete('/events/{id}', [\App\Controllers\EventController::class, 'deleteEvent']);
    // Add other protected routes here
})->add($jwtMiddleware);

// Add any other routes your application needs

// Right before $app->run();
$app->addErrorMiddleware(true, true, true);

$app->run();
