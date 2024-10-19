<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class RoleMiddleware
{
    private $userModel;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    public function __invoke(Request $request, RequestHandler $handler, array $roles): Response
    {
        error_log('RoleMiddleware: Invoked');
        $user = $request->getAttribute('user');
        error_log('User in RoleMiddleware: ' . json_encode($user));

        if (!$user || !isset($user['role'])) {
            $response = new Response();
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        if (in_array($user['role'], $roles)) {
            return $handler->handle($request);
        }

        $response = new Response();
        return $this->jsonResponse($response, ['error' => 'Insufficient permissions'], 403);
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
