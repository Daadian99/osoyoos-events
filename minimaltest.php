<?php

require("vendor/autoload.php");

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Osoyoos Events API",
 *     version="1.0.0",
 *     description="API for managing events in Osoyoos"
 * )
 * @OA\Server(
 *     url="http://localhost/osoyoos-events",
 *     description="Local development server"
 * )
 */

class OpenApiConfig {}

$openapi = \OpenApi\Generator::scan([
    __DIR__ . '/src/Controllers',
]);
// Capture the output instead of sending it directly
ob_start();
echo $openapi->toJson();
$output = ob_get_clean();
// Now we can safely set headers
header('Content-Type: application/json');
echo $output;
