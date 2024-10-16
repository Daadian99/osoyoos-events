<?php
// Check PHP version
echo "PHP Version: " . phpversion() . "\n";

// Check if Composer is installed
exec('composer --version', $composerOutput, $composerReturnVar);
echo "Composer: " . ($composerReturnVar === 0 ? $composerOutput[0] : "Not found") . "\n";

// Check if Slim is installed
if (class_exists('Slim\App')) {
    echo "Slim Framework: Installed\n";
} else {
    echo "Slim Framework: Not found\n";
}

// Test database connection
$host = 'localhost';
$db   = 'osoyoos_events';
$user = 'root';
$pass = 'Keighos01!';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "Database connection: Successful\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Display a test message
echo "Hello, Osoyoos Events!";

