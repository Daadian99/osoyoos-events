<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use Firebase\JWT\JWT;
use Exception;

class UserController
{
    private $userModel;
    private $jwtSecret;

    public function __construct(User $userModel, string $jwtSecret)
    {
        $this->userModel = $userModel;
        $this->jwtSecret = $jwtSecret;
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $this->userModel->register($username, $email, $password, $role);
            $response->getBody()->write(json_encode(['message' => 'User registered successfully']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to register user: ' . $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        error_log("Login attempt for email: $email"); // Add this line for debugging

        try {
            $user = $this->userModel->getUserByEmail($email);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                error_log("Login failed: Invalid credentials for email: $email"); // Add this line for debugging
                return $this->jsonResponse($response, ['error' => 'Invalid credentials'], 401);
            }

            $token = $this->generateJwtToken($user);

            error_log("Login successful for email: $email"); // Add this line for debugging

            return $this->jsonResponse($response, [
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage()); // Add this line for debugging
            return $this->jsonResponse($response, ['error' => 'An error occurred during login'], 500);
        }
    }

    public function getAllUsers(Request $request, Response $response): Response
    {
        $users = $this->userModel->getAllUsers();

        if ($users !== false) {
            $response->getBody()->write(json_encode($users));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'Failed to fetch users']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    private function generateToken($user): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // valid for 1 hour

        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'iat' => $issuedAt,
            'exp' => $expirationTime
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}
