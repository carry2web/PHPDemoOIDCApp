<?php
// Emergency Debug Page for Azure Web Apps
// This page will help identify white screen issues

// Start output buffering to catch any errors
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>🚨 Azure Debug Console</title>";
echo "<style>";
echo "body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }";
echo ".section { background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 5px; }";
echo ".error { background: #d32f2f; color: white; padding: 10px; border-radius: 3px; }";
echo ".success { background: #388e3c; color: white; padding: 10px; border-radius: 3px; }";
echo ".warning { background: #f57c00; color: white; padding: 10px; border-radius: 3px; }";
echo ".info { background: #1976d2; color: white; padding: 10px; border-radius: 3px; }";
echo "pre { background: #000; color: #0f0; padding: 10px; overflow-x: auto; }";
echo "</style></head><body>";

echo "<h1>🚨 Azure Web Apps Debug Console</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "</p>";

// Test 1: Basic PHP Information
echo "<div class='section'>";
echo "<h2>📋 PHP Environment</h2>";
try {
    echo "<div class='success'>✅ PHP Version: " . PHP_VERSION . "</div>";
    echo "<div class='info'>📁 Current Directory: " . getcwd() . "</div>";
    echo "<div class='info'>🌐 Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
    echo "<div class='info'>🏠 Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ PHP Info Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: File System Check
echo "<div class='section'>";
echo "<h2>📂 File System</h2>";
$checkFiles = [
    'index.php',
    'callback.php', 
    'dashboard.php',
    'lib/config_helper.php',
    'lib/oidc_simple.php',
    'lib/logger.php',
    '.env'
];

foreach ($checkFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<div class='success'>✅ $file (Size: $size bytes, Perms: $perms)</div>";
    } else {
        echo "<div class='error'>❌ Missing: $file</div>";
    }
}
echo "</div>";

// Test 3: Environment Variables
echo "<div class='section'>";
echo "<h2>🔧 Environment Variables</h2>";
$envVars = [
    'EXTERNAL_CLIENT_ID',
    'EXTERNAL_CLIENT_SECRET', 
    'EXTERNAL_TENANT_ID',
    'INTERNAL_CLIENT_ID',
    'INTERNAL_CLIENT_SECRET',
    'INTERNAL_TENANT_ID',
    'GRAPH_CLIENT_ID',
    'GRAPH_CLIENT_SECRET',
    'AWS_ACCESS_KEY_ID',
    'AWS_SECRET_ACCESS_KEY'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? getenv($var) ?? null;
    if ($value) {
        $display = ($var === 'EXTERNAL_CLIENT_SECRET' || $var === 'INTERNAL_CLIENT_SECRET' || $var === 'GRAPH_CLIENT_SECRET' || $var === 'AWS_SECRET_ACCESS_KEY') 
            ? substr($value, 0, 8) . '...' 
            : $value;
        echo "<div class='success'>✅ $var: $display</div>";
    } else {
        echo "<div class='error'>❌ Missing: $var</div>";
    }
}
echo "</div>";

// Test 4: Try loading config helper
echo "<div class='section'>";
echo "<h2>⚙️ Configuration Loading</h2>";
try {
    if (file_exists('lib/config_helper.php')) {
        require_once 'lib/config_helper.php';
        $config = get_app_config();
        echo "<div class='success'>✅ Config helper loaded successfully</div>";
        echo "<div class='info'>📊 Config sections: " . implode(', ', array_keys($config)) . "</div>";
        
        // Check specific config values
        if (isset($config['b2c']['client_id'])) {
            echo "<div class='success'>✅ B2C Client ID: " . $config['b2c']['client_id'] . "</div>";
        } else {
            echo "<div class='error'>❌ B2C Client ID not configured</div>";
        }
        
        if (isset($config['b2b']['client_id'])) {
            echo "<div class='success'>✅ B2B Client ID: " . $config['b2b']['client_id'] . "</div>";
        } else {
            echo "<div class='error'>❌ B2B Client ID not configured</div>";
        }
        
    } else {
        echo "<div class='error'>❌ lib/config_helper.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Config Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// Test 5: Session functionality
echo "<div class='section'>";
echo "<h2>🍪 Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<div class='success'>✅ Session started successfully</div>";
    echo "<div class='info'>📊 Session ID: " . session_id() . "</div>";
    echo "<div class='info'>🔧 Session Status: " . session_status() . "</div>";
    
    $_SESSION['debug_test'] = 'Session working at ' . date('H:i:s');
    echo "<div class='success'>✅ Session write test passed</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Session Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 6: Composer autoload
echo "<div class='section'>";
echo "<h2>📦 Composer Dependencies</h2>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "<div class='success'>✅ Composer autoload successful</div>";
        
        // Test specific classes
        $classes = [
            'Jumbojett\\OpenIDConnectClient',
            'Aws\\S3\\S3Client'
        ];
        
        foreach ($classes as $class) {
            if (class_exists($class)) {
                echo "<div class='success'>✅ Class available: $class</div>";
            } else {
                echo "<div class='warning'>⚠️ Class not found: $class</div>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ vendor/autoload.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Autoload Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 7: Error logs
echo "<div class='section'>";
echo "<h2>📝 Error Logs</h2>";
$logFile = ini_get('error_log');
echo "<div class='info'>📄 Error Log File: " . ($logFile ?: 'Default system log') . "</div>";

// Try to find Azure-specific log locations
$azureLogs = [
    '/home/LogFiles/php_errors.log',
    '/home/LogFiles/application.log',
    '/tmp/php_errors.log',
    'D:\\home\\LogFiles\\php_errors.log'
];

foreach ($azureLogs as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        echo "<div class='success'>✅ Found log: $logPath</div>";
        $logContent = file_get_contents($logPath);
        if ($logContent) {
            $lines = explode("\n", $logContent);
            $recentLines = array_slice($lines, -10); // Last 10 lines
            echo "<pre>" . implode("\n", $recentLines) . "</pre>";
        }
        break;
    }
}
echo "</div>";

// Test 8: Network connectivity
echo "<div class='section'>";
echo "<h2>🌐 Network Connectivity</h2>";

// Test basic endpoints with HEAD requests
$basicUrls = [
    'https://login.microsoftonline.com' => [200, 301, 302],
    'https://sts.windows.net' => [200, 301, 302]
];

foreach ($basicUrls as $url => $validCodes) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (in_array($httpCode, $validCodes)) {
        echo "<div class='success'>✅ $url (HTTP $httpCode)</div>";
    } else {
        echo "<div class='error'>❌ $url (HTTP $httpCode)</div>";
    }
}

// Test Graph API with a proper GET request to a valid endpoint
$graphUrl = 'https://graph.microsoft.com/v1.0/$metadata';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $graphUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/xml',
    'User-Agent: Azure-Debug-Script'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    echo "<div class='success'>✅ $graphUrl (HTTP $httpCode)</div>";
} else {
    echo "<div class='error'>❌ $graphUrl (HTTP $httpCode)</div>";
    if (!empty($curlError)) {
        echo "<div class='info'>🔍 cURL Error: $curlError</div>";
    }
}

// Add note about Graph API connectivity
echo "<div class='info'>";
echo "<strong>ℹ️ Note:</strong> Graph API \$metadata endpoint should return HTTP 200. If you see 405, this might indicate connectivity issues or Azure firewall restrictions.";
echo "</div>";
echo "</div>";

// Test 9: Memory and performance
echo "<div class='section'>";
echo "<h2>⚡ Performance Info</h2>";
echo "<div class='info'>💾 Memory Limit: " . ini_get('memory_limit') . "</div>";
echo "<div class='info'>💾 Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB</div>";
echo "<div class='info'>⏱️ Max Execution Time: " . ini_get('max_execution_time') . " seconds</div>";
echo "<div class='info'>📁 Upload Max Filesize: " . ini_get('upload_max_filesize') . "</div>";
echo "</div>";

// Test 10: Quick fix suggestions
echo "<div class='section'>";
echo "<h2>🔧 Quick Fixes</h2>";
echo "<div class='info'>";
echo "<strong>Common Azure Web Apps Issues:</strong><br>";
echo "1. Check Application Settings in Azure Portal for environment variables<br>";
echo "2. Verify .env file exists and has correct values<br>";
echo "3. Check if composer install ran properly during deployment<br>";
echo "4. Verify PHP version compatibility (current: " . PHP_VERSION . ")<br>";
echo "5. Check Azure App Service logs in Azure Portal > Log stream<br>";
echo "6. Ensure file permissions are correct<br>";
echo "7. Check if any required PHP extensions are missing<br>";
echo "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>🚀 Next Steps</h2>";
echo "<div class='info'>";
echo "1. <a href='?' style='color: #64b5f6;'>Refresh this debug page</a><br>";
echo "2. <a href='index.php' style='color: #64b5f6;'>Try main application</a><br>";
echo "3. <a href='admin/cross_tenant_check.php' style='color: #64b5f6;'>Run cross-tenant diagnostics</a><br>";
echo "4. Check Azure Portal > App Service > Log stream for real-time logs<br>";
echo "</div>";
echo "</div>";

echo "</body></html>";

// Flush output buffer
ob_end_flush();
?>
