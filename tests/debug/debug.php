<?php
// File: debug.php - Quick diagnostic script
require_once __DIR__ . '/lib/config_helper.php';
require_once __DIR__ . '/lib/logger.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>S-Cape Travel Configuration Debug</h1>";

try {
    echo "<h2>1. Environment Loading Test</h2>";
    $config = get_app_config();
    echo "✅ Configuration loaded successfully<br>";
    
    echo "<h2>2. Configuration Values</h2>";
    echo "<strong>B2C Configuration:</strong><br>";
    echo "Client ID: " . substr($config['b2c']['client_id'], 0, 8) . "...<br>";
    echo "Tenant ID: " . substr($config['b2c']['tenant_id'], 0, 8) . "...<br>";
    echo "Domain: " . htmlspecialchars($config['b2c']['domain']) . "<br>";
    
    echo "<br><strong>B2B Configuration:</strong><br>";
    echo "Client ID: " . substr($config['b2b']['client_id'], 0, 8) . "...<br>";
    echo "Tenant ID: " . substr($config['b2b']['tenant_id'], 0, 8) . "...<br>";
    
    echo "<br><strong>App Configuration:</strong><br>";
    echo "Redirect URI: " . htmlspecialchars($config['app']['redirect_uri']) . "<br>";
    echo "Debug Mode: " . ($config['app']['debug'] ? 'Enabled' : 'Disabled') . "<br>";
    
    echo "<h2>3. B2C URLs</h2>";
    $tenantName = 'scapecustomers';
    $policy = 'B2C_1_signupsignin';
    $b2cUrl = "https://$tenantName.b2clogin.com/$tenantName.onmicrosoft.com/$policy/v2.0";
    echo "B2C Authority URL: " . htmlspecialchars($b2cUrl) . "<br>";
    
    echo "<h2>4. Session Test</h2>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started successfully<br>";
    } else {
        echo "✅ Session already active<br>";
    }
    echo "Session ID: " . session_id() . "<br>";
    
    echo "<h2>5. Logging Test</h2>";
    $logger = ScapeLogger::getInstance();
    $logger->info('Debug page accessed', ['timestamp' => date('Y-m-d H:i:s')]);
    echo "✅ Logger working<br>";
    
    echo "<h2>6. File Permissions</h2>";
    $vendorPath = __DIR__ . '/vendor';
    echo "Vendor directory exists: " . (is_dir($vendorPath) ? '✅ Yes' : '❌ No') . "<br>";
    echo "Vendor readable: " . (is_readable($vendorPath) ? '✅ Yes' : '❌ No') . "<br>";
    
    $autoloadPath = $vendorPath . '/autoload.php';
    echo "Autoload exists: " . (file_exists($autoloadPath) ? '✅ Yes' : '❌ No') . "<br>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h2>❌ Error Detected</h2>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<h2>7. Quick Test Links</h2>";
echo "<a href='index.php'>Main Page</a> | ";
echo "<a href='index.php?login=1&type=customer'>Test Customer Login</a> | ";
echo "<a href='index.php?login=1&type=agent'>Test Agent Login</a><br>";

echo "<h2>8. Recommendations</h2>";
echo "<ol>";
echo "<li><strong>Check Azure B2C:</strong> Ensure you have a user flow named 'B2C_1_signupsignin' in your scapecustomers tenant</li>";
echo "<li><strong>Check Redirect URI:</strong> Ensure your app registrations have the exact redirect URI configured</li>";
echo "<li><strong>Check Permissions:</strong> Ensure API permissions are granted with admin consent</li>";
echo "<li><strong>Check Logs:</strong> Look at /home/LogFiles/ in Azure Web Apps for detailed error logs</li>";
echo "</ol>";

phpinfo();
?>
