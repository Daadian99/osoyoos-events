<?php

namespace App\Models;

use PDO;

class Category
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAllCategories()
    {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCategory($name)
    {
        $stmt = $this->db->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->db->lastInsertId();
    }

    public function updateCategory($id, $name)
    {
        $stmt = $this->db->prepare("UPDATE categories SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }

    public function deleteCategory($id)
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getEventCategories($eventId)
    {
        $stmt = $this->db->prepare("
            SELECT c.* FROM categories c
            JOIN event_categories ec ON c.id = ec.category_id
            WHERE ec.event_id = ?
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addEventCategory($eventId, $categoryId)
    {
        $stmt = $this->db->prepare("INSERT INTO event_categories (event_id, category_id) VALUES (?, ?)");
        return $stmt->execute([$eventId, $categoryId]);
    }

    public function removeEventCategory($eventId, $categoryId)
    {
        $stmt = $this->db->prepare("DELETE FROM event_categories WHERE event_id = ? AND category_id = ?");
        return $stmt->execute([$eventId, $categoryId]);
    }

    // Add methods for category-related operations
}
