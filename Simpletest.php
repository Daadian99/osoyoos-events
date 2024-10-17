<?php
require __DIR__ . '/vendor/autoload.php';

use OpenApi\Annotations as OA;

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
$openApi = \OpenApi\Generator::scan([
    __DIR__ . '/src/OpenApiDefinitions.php',
    __DIR__ . '/src/Controllers/UserController.php',
    __DIR__ . '/src/Controllers/EventController.php',
    __DIR__ . '/src/Controllers/TicketController.php'
], ['debug' => true]);
$output = ob_get_clean();

$result = [
    'openapi_json' => json_decode($openApi->toJson()),
    'scanned_files' => array_keys($openApi->_analysis->classes),
    'annotations' => [],
    'php_version' => PHP_VERSION,
    'swagger_php_version' => defined('OpenApi\Generator::VERSION') ? OpenApi\Generator::VERSION : 'Unknown',
    'debug' => property_exists($openApi->_analysis, 'debug') ? $openApi->_analysis->debug : null,
];

foreach ($openApi->_analysis->annotations as $annotation) {
    $result['annotations'][] = [
        'class' => get_class($annotation),
        'context' => $annotation->_context ? [
            'filename' => $annotation->_context->filename,
            'line' => $annotation->_context->line,
        ] : null,
    ];
}

$result['debug_output'] = $output;
$result['errors'] = error_get_last();

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
