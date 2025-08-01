<?php
// Emergency debug page to see what's breaking
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🚨 Emergency Debug</h1>";

echo "<h2>PHP Version Check</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "</p>";

echo "<h2>Autoloader Test</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p>✅ Autoloader loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ Autoloader failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Monolog Test</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $logger = new Monolog\Logger('test');
    $logger->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/data/emergency.log', Monolog\Logger::DEBUG));
    $logger->info('Emergency debug test');
    echo "<p>✅ Monolog logger created and logged</p>";
} catch (Exception $e) {
    echo "<p>❌ Monolog failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<h2>Current Logger Test</h2>";
try {
    require_once __DIR__ . '/lib/logger.php';
    echo "<p>✅ Logger file loaded</p>";
    
    $logger = ScapeLogger::getInstance();
    echo "<p>✅ ScapeLogger instance created</p>";
} catch (Exception $e) {
    echo "<p>❌ ScapeLogger failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<h2>Config Test</h2>";
try {
    require_once __DIR__ . '/lib/config_helper.php';
    echo "<p>✅ Config helper loaded</p>";
    
    $config = get_app_config();
    echo "<p>✅ Config loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ Config failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<h2>OIDC Test</h2>";
try {
    require_once __DIR__ . '/lib/oidc.php';
    echo "<p>✅ OIDC loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ OIDC failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<h2>Index Page Test</h2>";
try {
    ob_start();
    include __DIR__ . '/index.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    if (empty($output)) {
        echo "<p>❌ Index.php produces no output (white page)</p>";
    } else {
        echo "<p>✅ Index.php produces output (" . strlen($output) . " characters)</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Index.php failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}
?>
