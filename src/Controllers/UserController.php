<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management operations"
 * )
 * @OA\PathItem(
 *     path="/users"
 * )
 */
class UserController {
    private $user;
    private $jwtSecret;

    public function __construct(User $user, string $jwtSecret) {
        $this->user = $user;
        $this->jwtSecret = $jwtSecret;
        error_log("UserController class instantiated");
    }

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
    public function register(Request $request, Response $response) {
        $data = $request->getParsedBody();
        
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            return $this->jsonResponse($response, ['error' => 'Missing required fields'], 400);
        }

        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];

        if ($this->user->getUserByEmail($email)) {
            return $this->jsonResponse($response, ['error' => 'Email already exists'], 400);
        }

        $userId = $this->user->createUser($username, $email, $password);
        if ($userId) {
            return $this->jsonResponse($response, ['message' => 'User registered successfully', 'userId' => $userId]);
        } else {
            return $this->jsonResponse($response, ['error' => 'Failed to register user'], 500);
        }
    }

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
    public function login(Request $request, Response $response) {
        $data = $request->getParsedBody();
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->jsonResponse($response, ['error' => 'Missing email or password'], 400);
        }

        $email = $data['email'];
        $password = $data['password'];

        $user = $this->user->getUserByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->jsonResponse($response, ['error' => 'Invalid email or password'], 401);
        }

        $token = $this->generateToken($user);
        return $this->jsonResponse($response, ['token' => $token, 'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]]);
    }

    private function generateToken(array $user): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;  // valid for 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Logout a user",
     *     tags={"Users"},
     *     @OA\Response(response="200", description="Logout successful")
     * )
     */
    public function logout(Request $request, Response $response) {
        // In a real-world scenario, you might want to invalidate the token here
        // For now, we'll just return a success message
        return $this->jsonResponse($response, ['message' => 'Logout successful']);
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     @OA\Response(response="200", description="List of users"),
     *     @OA\Response(response="500", description="Server error")
     * )
     */
    public function getAllUsers(Request $request, Response $response) {
        $users = $this->user->getAllUsers();
        if ($users === false) {
            $response->getBody()->write(json_encode(['error' => 'Failed to retrieve users']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Add more methods as needed
}
