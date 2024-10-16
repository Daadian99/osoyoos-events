<?php
namespace App\Models;

use PDO;
use PDOException;

class User {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function register($username, $email, $password, $role = 'user') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$username, $email, $password_hash, $role]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password) {
        $sql = "SELECT id, username, password_hash, role FROM users WHERE email = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                return [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            return false;
        }
    }

    public function getUserRole($userId) {
        if (empty($userId)) {
            error_log("User model: Attempted to get role for empty user ID");
            return false;
        }

        $sql = "SELECT role FROM users WHERE id = ?";
        try {
            error_log("User model: Attempting to get role for user ID: $userId");
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("User model: SQL query result: " . json_encode($result));
            if ($result) {
                error_log("User model: Role found: " . $result['role']);
                return $result['role'];
            } else {
                error_log("User model: No role found for user ID: $userId");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Database error in getUserRole: " . $e->getMessage());
            return false;
        }
    }

    // Add more methods as needed
}
