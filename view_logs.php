<?php
// Simple log viewer for Azure Web Apps
require_once __DIR__ . '/lib/logger.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Azure Web App Logs</h1>";
echo "<style>body{font-family:monospace;} .error{color:red;} .info{color:blue;} .debug{color:gray;}</style>";

$logFile = __DIR__ . '/data/app.log';

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    $recentLines = array_slice($lines, -100); // Last 100 lines
    
    echo "<h2>Last 100 log entries:</h2>";
    echo "<div style='background:#f0f0f0; padding:10px; overflow:auto; height:600px;'>";
    
    foreach ($recentLines as $line) {
        $class = '';
        if (strpos($line, 'ERROR') !== false) $class = 'error';
        elseif (strpos($line, 'INFO') !== false) $class = 'info';
        elseif (strpos($line, 'DEBUG') !== false) $class = 'debug';
        
        echo "<div class='$class'>" . htmlspecialchars($line) . "</div>";
    }
    echo "</div>";
    
    echo "<h2>Recent Authentication Failures:</h2>";
    $authLines = array_filter($lines, function($line) {
        return strpos($line, 'auth_callback_failed') !== false || 
               strpos($line, 'OIDC authenticate returned') !== false ||
               strpos($line, 'Authentication callback failed') !== false;
    });
    
    foreach (array_slice($authLines, -10) as $line) {
        echo "<div class='error'>" . htmlspecialchars($line) . "</div>";
    }
    
} else {
    echo "<p>Log file not found at: $logFile</p>";
}

echo "<br><a href='index.php'>Back to Index</a>";
?>
