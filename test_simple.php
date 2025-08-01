<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing basic functionality...\n";

try {
    echo "1. Testing autoloader...\n";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Autoloader OK\n";
    
    echo "2. Testing logger...\n";
    require_once __DIR__ . '/lib/logger.php';
    echo "✅ Logger OK\n";
    
    echo "3. Testing config helper...\n";
    require_once __DIR__ . '/lib/config_helper.php';
    echo "✅ Config helper OK\n";
    
    echo "4. Testing OIDC file...\n";
    require_once __DIR__ . '/lib/oidc.php';
    echo "✅ OIDC file OK\n";
    
    echo "5. Testing start_azure_safe_session function...\n";
    start_azure_safe_session();
    echo "✅ Session started OK\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
