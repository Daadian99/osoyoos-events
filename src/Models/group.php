<?php

namespace App\Models;

use PDO;

class Group {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function createGroup($name, $creatorId, $isPrivate = false) {
        $sql = "INSERT INTO groups (name, creator_id, is_private) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name, $creatorId, $isPrivate]);
        return $this->db->lastInsertId();
    }

    public function getGroupSuggestions($partialName) {
        $sql = "SELECT id, name FROM groups WHERE name LIKE ? LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$partialName . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add more methods as needed (e.g., addMemberToGroup, removeMemberFromGroup, etc.)
}

