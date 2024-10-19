<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Location;

class LocationController
{
    private $locationModel;

    public function __construct(Location $locationModel)
    {
        $this->locationModel = $locationModel;
    }

    public function getLocations(Request $request, Response $response): Response
    {
        $locations = $this->locationModel->getAllLocations();
        $response->getBody()->write(json_encode($locations));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

