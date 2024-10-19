<?php

namespace App\Models;

class Location
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllLocations()
    {
        $stmt = $this->db->query("SELECT * FROM locations");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLocationById($id)
    {
        $sql = "SELECT * FROM locations WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getLocationBySlug($slug)
    {
        $sql = "SELECT * FROM locations WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createLocation($name, $slug)
    {
        $sql = "INSERT INTO locations (name, slug) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name, $slug]);
        return $this->db->lastInsertId();
    }
}
