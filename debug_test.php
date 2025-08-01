<?php
// Simple debug page to test what's working
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Test Page</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

try {
    echo "<h2>Testing Autoloader</h2>";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Autoloader loaded successfully<br>";
    
    echo "<h2>Testing Monolog Classes</h2>";
    echo "Monolog\\Logger exists: " . (class_exists('Monolog\\Logger') ? '✅ YES' : '❌ NO') . "<br>";
    echo "Monolog\\Handler\\RotatingFileHandler exists: " . (class_exists('Monolog\\Handler\\RotatingFileHandler') ? '✅ YES' : '❌ NO') . "<br>";
    
    echo "<h2>Testing Basic Monolog</h2>";
    $logger = new \Monolog\Logger('test');
    $handler = new \Monolog\Handler\StreamHandler(__DIR__ . '/data/test.log', \Monolog\Logger::DEBUG);
    $logger->pushHandler($handler);
    $logger->info('Test log entry');
    echo "✅ Basic Monolog test successful<br>";
    
    echo "<h2>Testing Our Logger</h2>";
    require_once __DIR__ . '/lib/logger.php';
    echo "✅ Logger file loaded<br>";
    
    $scapeLogger = ScapeLogger::getInstance();
    echo "✅ ScapeLogger instantiated<br>";
    
    $scapeLogger->info('Test message from debug page');
    echo "✅ ScapeLogger test message sent<br>";
    
    echo "<h2>Testing OIDC Simple</h2>";
    require_once __DIR__ . '/lib/oidc_simple.php';
    echo "✅ OIDC Simple loaded<br>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<h2>❌ FATAL ERROR</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<p><a href='index.php'>Back to Index</a></p>";
?>
