<?php

namespace App\Models;

use PDO;

class EventModel
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAllEvents($page = 1, $eventsPerPage = 10)
    {
        $offset = ($page - 1) * $eventsPerPage;
        $stmt = $this->db->prepare("SELECT * FROM events LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $eventsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventsByLocation($locationId, $page = 1, $eventsPerPage = 10)
    {
        $offset = ($page - 1) * $eventsPerPage;
        $stmt = $this->db->prepare("SELECT * FROM events WHERE location_id = :locationId LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':locationId', $locationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $eventsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
