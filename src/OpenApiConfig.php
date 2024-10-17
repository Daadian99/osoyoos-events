<?php

namespace App;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Osoyoos Events API",
 *         description="API for Osoyoos Events ticketing system"
 *     ),
 *     @OA\Server(
 *         url="/osoyoos-events/public",
 *         description="Local development server"
 *     )
 * )
 */
class OpenApiConfig
{
    public function __construct()
    {
        error_log("OpenApiConfig class instantiated");
    }
}
