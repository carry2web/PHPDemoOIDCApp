<?php
// Simple test to check if Monolog is working
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Logger Test</h1>";

try {
    require_once __DIR__ . '/lib/logger.php';
    echo "<p>✅ Logger file loaded successfully</p>";
    
    $logger = ScapeLogger::getInstance();
    echo "<p>✅ Logger instance created</p>";
    
    $logger->info('Test log message from logger test');
    echo "<p>✅ Test log message sent</p>";
    
    $logger->error('Test error message from logger test');
    echo "<p>✅ Test error message sent</p>";
    
    // Check if files were created
    $dataDir = __DIR__ . '/data';
    echo "<h2>Data Directory Check:</h2>";
    echo "<p>Data dir: $dataDir</p>";
    echo "<p>Exists: " . (is_dir($dataDir) ? 'YES' : 'NO') . "</p>";
    
    if (is_dir($dataDir)) {
        $files = scandir($dataDir);
        echo "<p>Files in data dir: " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>❌ Trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

echo "<br><a href='index.php'>Back to Index</a>";
?>
