<?php
require __DIR__ . '/vendor/autoload.php';

use OpenApi\Annotations as OA;
use OpenApi\Context;

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
try {
    $context = new Context([]);
    $analysis = new \OpenApi\Analysis([], $context);

    $openApi = \OpenApi\Generator::scan([
        __DIR__ . '/src/OpenApiDefinitions.php',
        __DIR__ . '/src/Controllers/UserController.php',
        __DIR__ . '/src/Controllers/EventController.php',
        __DIR__ . '/src/Controllers/TicketController.php'
    ], [
        'debug' => true,
        'analysis' => $analysis,
    ]);
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

    echo '<pre>';
    print_r($result);
    echo '</pre>';
} catch (Exception $e) {
    ob_end_clean();
    echo 'Error: ' . $e->getMessage();
}
?>
