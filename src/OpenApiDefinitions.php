<?php
namespace App;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Osoyoos Event Ticketing API",
 *         version="1.0.0",
 *         description="API for managing events and tickets in Osoyoos."
 *     ),
 *     @OA\Server(
 *         url="http://localhost/osoyoos-events",
 *         description="Local development server"
 *     )
 * )
 * @OA\PathItem(path="/")
 */
class OpenApiDefinitions {}
