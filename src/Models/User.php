<?php

namespace App\Models;

use PDO;
use Exception;

class User
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function register($username, $email, $password, $role = 'user')
    {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("User with this email already exists");
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$username, $email, $hashedPassword, $role]);

            if (!$result) {
                throw new Exception("Database error: " . implode(", ", $stmt->errorInfo()));
            }

            return true;
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            throw $e;
        }
    }

    public function login($email, $password)
    {
        $stmt = $this->db->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
        }

        return false;
    }

    public function getAllUsers()
    {
        $stmt = $this->db->query("SELECT id, username, email, role, created_at FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add any other methods you need for user management here
}

