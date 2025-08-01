<?php
// Generic Log Viewer for Azure Web Apps
require_once __DIR__ . '/lib/logger.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Generic Log Viewer - Azure Web App</h1>";
echo "<style>
    body { font-family: monospace; margin: 20px; }
    .error { color: red; background: #ffe6e6; padding: 2px; }
    .warning { color: orange; background: #fff3e6; padding: 2px; }
    .info { color: blue; background: #e6f3ff; padding: 2px; }
    .debug { color: gray; background: #f0f0f0; padding: 2px; }
    .success { color: green; background: #e6ffe6; padding: 2px; }
    .log-container { background: #f8f8f8; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .log-entry { margin: 2px 0; padding: 5px; border-left: 3px solid #ccc; }
    .controls { margin: 20px 0; }
    .controls button { margin: 5px; padding: 10px; }
    h2 { color: #333; border-bottom: 2px solid #ccc; }
</style>";

// Get parameters
$selectedLog = $_GET['log'] ?? 'all';
$lines = intval($_GET['lines'] ?? 100);
$level = $_GET['level'] ?? 'all';

// Define log sources
$logSources = [
    'app' => [
        'name' => 'Application Log',
        'path' => __DIR__ . '/data/app.log',
        'description' => 'Main application logging'
    ],
    'error' => [
        'name' => 'PHP Error Log',
        'path' => __DIR__ . '/data/error.log',
        'description' => 'PHP errors and exceptions'
    ],
    'debug' => [
        'name' => 'Debug Log',
        'path' => __DIR__ . '/data/debug.log',
        'description' => 'Detailed debug information'
    ],
    'php_errors' => [
        'name' => 'PHP System Errors',
        'path' => error_get_last() ? ini_get('error_log') : '/tmp/php_errors.log',
        'description' => 'System PHP error log'
    ]
];

echo "<div class='controls'>";
echo "<h2>Log Controls</h2>";
echo "<form method='GET'>";
echo "<select name='log'>";
echo "<option value='all'" . ($selectedLog === 'all' ? ' selected' : '') . ">All Logs</option>";
foreach ($logSources as $key => $source) {
    $selected = ($selectedLog === $key) ? ' selected' : '';
    echo "<option value='$key'$selected>{$source['name']}</option>";
}
echo "</select>";

echo "<select name='lines'>";
foreach ([50, 100, 200, 500] as $lineCount) {
    $selected = ($lines === $lineCount) ? ' selected' : '';
    echo "<option value='$lineCount'$selected>Last $lineCount lines</option>";
}
echo "</select>";

echo "<select name='level'>";
$levels = ['all', 'ERROR', 'WARNING', 'INFO', 'DEBUG'];
foreach ($levels as $lvl) {
    $selected = ($level === $lvl) ? ' selected' : '';
    echo "<option value='$lvl'$selected>$lvl</option>";
}
echo "</select>";

echo "<button type='submit'>View Logs</button>";
echo "<a href='?refresh=1' style='margin-left: 10px;'><button type='button'>Refresh</button></a>";
echo "</form>";
echo "</div>";

// System Information
echo "<div class='log-container'>";
echo "<h2>System Information</h2>";
echo "<div>Current directory: " . __DIR__ . "</div>";
echo "<div>System temp dir: " . sys_get_temp_dir() . "</div>";
echo "<div>PHP user: " . get_current_user() . "</div>";
echo "<div>PHP version: " . PHP_VERSION . "</div>";
echo "<div>Server time: " . date('Y-m-d H:i:s T') . "</div>";
echo "<div>Error reporting level: " . error_reporting() . "</div>";
echo "<div>Display errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</div>";
echo "<div>Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "</div>";
echo "<div>Error log path: " . ini_get('error_log') . "</div>";
echo "</div>";

// Log file status
echo "<div class='log-container'>";
echo "<h2>Log File Status</h2>";
foreach ($logSources as $key => $source) {
    $path = $source['path'];
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $size = $exists ? filesize($path) : 0;
    $modified = $exists ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A';
    
    $statusClass = $exists ? 'success' : 'error';
    echo "<div class='$statusClass'>";
    echo "<strong>{$source['name']}</strong> - {$source['description']}<br>";
    echo "Path: $path<br>";
    echo "Status: " . ($exists ? '✅ EXISTS' : '❌ NOT FOUND');
    if ($exists) {
        echo " | Size: " . number_format($size) . " bytes | Modified: $modified";
    }
    echo "</div><br>";
}
echo "</div>";

// Function to parse and format log entries
function parseLogEntry($line) {
    // Try to parse our custom log format
    if (preg_match('/^\[(.*?)\]\s+(ERROR|WARNING|INFO|DEBUG|CRITICAL)\s+(.*?):\s+(.*)/', $line, $matches)) {
        return [
            'timestamp' => $matches[1],
            'level' => $matches[2],
            'component' => $matches[3],
            'message' => $matches[4],
            'formatted' => true
        ];
    }
    
    // Fallback for other log formats
    return [
        'timestamp' => '',
        'level' => 'UNKNOWN',
        'component' => '',
        'message' => $line,
        'formatted' => false
    ];
}

function displayLogEntries($logPath, $logName, $maxLines, $levelFilter) {
    if (!file_exists($logPath)) {
        echo "<div class='error'>Log file not found: $logPath</div>";
        return;
    }
    
    $lines = file($logPath, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        echo "<div class='error'>Could not read log file: $logPath</div>";
        return;
    }
    
    $lines = array_slice($lines, -$maxLines);
    
    echo "<div class='log-container'>";
    echo "<h2>$logName (" . count($lines) . " entries)</h2>";
    
    if (empty($lines)) {
        echo "<div class='info'>No log entries found</div>";
    } else {
        foreach ($lines as $line) {
            $entry = parseLogEntry($line);
            
            // Filter by level
            if ($levelFilter !== 'all' && $entry['level'] !== $levelFilter) {
                continue;
            }
            
            $levelClass = strtolower($entry['level']);
            if ($levelClass === 'critical') $levelClass = 'error';
            
            echo "<div class='log-entry $levelClass'>";
            if ($entry['formatted']) {
                echo "<strong>[{$entry['timestamp']}] {$entry['level']}</strong> ";
                if ($entry['component']) echo "<em>{$entry['component']}:</em> ";
                echo htmlspecialchars($entry['message']);
            } else {
                echo htmlspecialchars($line);
            }
            echo "</div>";
        }
    }
    echo "</div>";
}

// Display selected logs
if ($selectedLog === 'all') {
    foreach ($logSources as $key => $source) {
        if (file_exists($source['path'])) {
            displayLogEntries($source['path'], $source['name'], $lines, $level);
        }
    }
} else {
    if (isset($logSources[$selectedLog])) {
        $source = $logSources[$selectedLog];
        displayLogEntries($source['path'], $source['name'], $lines, $level);
    }
}

// Test logging functionality
echo "<div class='log-container'>";
echo "<h2>Test Logging</h2>";
if (isset($_GET['test_log'])) {
    try {
        $logger = ScapeLogger::getInstance();
        $testMessage = "Test log entry from view_logs.php at " . date('Y-m-d H:i:s');
        
        $logger->debug("Debug test: $testMessage");
        $logger->info("Info test: $testMessage");
        $logger->warning("Warning test: $testMessage");
        $logger->error("Error test: $testMessage");
        
        // Test PHP error logging
        trigger_error("Test PHP notice for logging", E_USER_NOTICE);
        
        echo "<div class='success'>✅ Test log entries created successfully</div>";
        echo "<div class='info'>Check the logs above for the test entries</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Logger test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<a href='?test_log=1'><button>Create Test Log Entries</button></a>";
}
echo "</div>";

echo "<br><div class='controls'>";
echo "<a href='index.php'><button>Back to Index</button></a> ";
echo "<a href='debug_azure.php'><button>Azure Debug</button></a>";
echo "</div>";
?>
