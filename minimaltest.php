<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require("vendor/autoload.php");

use OpenApi\Annotations as OA;

$openapi = new OA\OpenApi([
    'info' => new OA\Info([
        'title' => "Osoyoos Event Ticketing API",
        'version' => "1.0.0",
        'description' => "API for managing events and tickets in Osoyoos."
    ]),
    'servers' => [
        new OA\Server([
            'url' => "http://localhost/osoyoos-events",
            'description' => "Local development server"
        ])
    ],
    'paths' => [
        new OA\PathItem([
            'path' => '/'
        ])
    ]
]);

header('Content-Type: application/json');
echo json_encode($openapi, JSON_PRETTY_PRINT);
