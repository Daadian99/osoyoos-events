<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use App\Models\User;

class RoleMiddleware {
    private $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function __invoke(Request $request, RequestHandler $handler, $roles) {
        error_log("RoleMiddleware: Starting role verification");
        $userId = $request->getAttribute('userId');
        error_log("RoleMiddleware: Received user ID: " . ($userId ?? 'null'));
        
        if ($userId === null) {
            error_log("RoleMiddleware: User ID is null. JWT might not be decoded correctly.");
            $response = new Response();
            return $this->jsonResponse($response, ['error' => 'User not authenticated'], 401);
        }

        $userRole = $this->user->getUserRole($userId);
        error_log("RoleMiddleware: User role from database: " . ($userRole ?: 'not found'));
        error_log("RoleMiddleware: Allowed roles: " . implode(', ', $roles));

        if (!$userRole || !in_array($userRole, $roles)) {
            $response = new Response();
            return $this->jsonResponse($response, ['error' => 'Access denied. User role: ' . ($userRole ?: 'not found')], 403);
        }

        error_log("RoleMiddleware: Access granted for user ID: $userId with role: $userRole");
        return $handler->handle($request);
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
