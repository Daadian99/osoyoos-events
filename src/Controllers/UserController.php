<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController {
    private $user;
    private $jwtSecret;

    public function __construct(User $user, string $jwtSecret) {
        $this->user = $user;
        $this->jwtSecret = $jwtSecret;
    }

    public function register(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['error' => 'Missing required fields'], 400);
        }

        if ($this->user->register($username, $email, $password, $role)) {
            return $this->jsonResponse($response, ['message' => 'User registered successfully'], 201);
        } else {
            return $this->jsonResponse($response, ['error' => 'Registration failed'], 500);
        }
    }

    public function login(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['error' => 'Email and password are required'], 400);
        }

        $user = $this->user->login($email, $password);
        if ($user) {
            $token = $this->generateToken($user);
            error_log("UserController: Login successful for user: " . json_encode($user));
            return $this->jsonResponse($response, ['message' => 'Login successful', 'token' => $token, 'user' => $user]);
        } else {
            error_log("UserController: Login failed for email: $email");
            return $this->jsonResponse($response, ['error' => 'Invalid email or password'], 401);
        }
    }

    private function generateToken(array $user): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token valid for 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        error_log("UserController: Generated token payload: " . json_encode($payload));
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function logout(Request $request, Response $response) {
        // In a real-world scenario, you might want to invalidate the token here
        // For now, we'll just return a success message
        return $this->jsonResponse($response, ['message' => 'Logout successful']);
    }

    // Add more methods as needed
}
