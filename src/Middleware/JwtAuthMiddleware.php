<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtAuthMiddleware {
    private $jwtSecret;

    public function __construct(string $jwtSecret) {
        $this->jwtSecret = $jwtSecret;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        error_log("JwtAuthMiddleware: Middleware invoked for path: " . $request->getUri()->getPath());
        error_log("JwtAuthMiddleware: Starting token verification");
        error_log("Authorization header: " . $request->getHeaderLine('Authorization'));
        $token = $this->getTokenFromHeader($request);

        if (!$token) {
            error_log("JwtAuthMiddleware: No token provided");
            $response = new Response();
            return $this->jsonResponse($response, ['error' => 'No token provided'], 401);
        }

        try {
            error_log("JwtAuthMiddleware: Attempting to decode token");
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            error_log("JwtAuthMiddleware: Decoded token: " . json_encode($decoded));
            
            if (!isset($decoded->id)) {
                error_log("JwtAuthMiddleware: Token does not contain id");
                $response = new Response();
                return $this->jsonResponse($response, ['error' => 'Invalid token structure'], 401);
            }
            
            $request = $request->withAttribute('user', [
                'id' => $decoded->id,
                'username' => $decoded->username ?? '',
                'role' => $decoded->role ?? ''
            ]);

            error_log("JwtAuthMiddleware: Set user attribute - " . json_encode($request->getAttribute('user')));
            
            $response = $handler->handle($request);
            error_log("JwtAuthMiddleware: Handler processed request. Response status: " . $response->getStatusCode());
            return $response;
        } catch (Exception $e) {
            error_log("JwtAuthMiddleware: Token decode error: " . $e->getMessage());
            $response = new Response();
            return $this->jsonResponse($response, ['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
    }

    private function getTokenFromHeader(Request $request): ?string {
        $authHeader = $request->getHeaderLine('Authorization');
        error_log("JwtAuthMiddleware: Authorization header: " . $authHeader);
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        error_log("JwtAuthMiddleware: No token found in Authorization header");
        return null;
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
