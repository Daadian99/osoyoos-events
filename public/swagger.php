<?php
require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$directories = [
    __DIR__ . '/../src',
    __DIR__ . '/../src/api-docs.php',
    __DIR__ . '/index.php'
];

ob_start();
echo "Scanning directories:\n";
print_r($directories);

try {
    $openapi = \OpenApi\Generator::scan($directories);
    $jsonContent = $openapi->toJson();
    $debug = ob_get_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'debug' => $debug,
        'openapi' => json_decode($jsonContent, true)
    ], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    $debug = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'debug' => $debug,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
