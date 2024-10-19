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

        if (empty($email) || empty($password)) {
            $response->getBody()->write(json_encode(['error' => 'Missing email or password']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user = $this->userModel->login($email, $password);

        if ($user) {
            $token = $this->generateToken($user);
            error_log("UserController: Login successful for user ID: " . $user['id']);
            error_log("UserController: Generated token: " . $token);
            $response->getBody()->write(json_encode(['token' => $token, 'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
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
