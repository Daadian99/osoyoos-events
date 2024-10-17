<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start(); // Start output buffering
require __DIR__ . '/vendor/autoload.php';

use OpenApi\Annotations as OA;

try {
    $openapi = \OpenApi\Generator::scan([
        __DIR__ . '/src',
    ]);

    header('Content-Type: application/json');
    echo json_encode([
        'openapi' => json_decode($openapi->toJson(), true),
        'annotations' => array_map(function($annotation) {
            return get_class($annotation);
        }, iterator_to_array($openapi->_analysis->annotations)),
        'php_version' => PHP_VERSION,
        'swagger_php_version' => \OpenApi\Generator::VERSION ?? 'Unknown',
        'scanned_directory' => __DIR__ . '/src',
    ], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}

ob_end_flush(); // End output buffering and send output
