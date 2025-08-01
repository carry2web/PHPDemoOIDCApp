<?php
// File: debug_wrapper.php
// Emergency debugging wrapper for any page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any issues
ob_start();

// Function to display debug information
function show_debug_info($error = null) {
    ob_clean(); // Clear any partial output
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>🚨 Debug Information</title>";
    echo "<style>";
    echo "body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }";
    echo ".error { background: #d32f2f; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }";
    echo ".success { background: #388e3c; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }";
    echo ".info { background: #1976d2; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }";
    echo ".section { background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 5px; }";
    echo "pre { background: #000; color: #0f0; padding: 10px; overflow-x: auto; }";
    echo "</style></head><body>";
    
    echo "<h1>🚨 S-Cape Travel Debug Information</h1>";
    echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s T') . "</p>";
    echo "<p><strong>Page:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>";
    
    if ($error) {
        echo "<div class='error'>";
        echo "<h2>❌ Error Detected:</h2>";
        echo "<strong>Message:</strong> " . htmlspecialchars($error->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($error->getFile()) . "<br>";
        echo "<strong>Line:</strong> " . $error->getLine() . "<br>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($error->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
    
    // Environment check
    echo "<div class='section'>";
    echo "<h2>🔧 Environment Check</h2>";
    
    // Check critical environment variables
    $criticalVars = [
        'EXTERNAL_CLIENT_ID',
        'INTERNAL_CLIENT_ID', 
        'GRAPH_CLIENT_ID',
        'AWS_ACCESS_KEY_ID'
    ];
    
    foreach ($criticalVars as $var) {
        $value = $_ENV[$var] ?? getenv($var);
        if ($value) {
            echo "<div class='success'>✅ $var: Configured</div>";
        } else {
            echo "<div class='error'>❌ $var: Missing</div>";
        }
    }
    echo "</div>";
    
    // File check
    echo "<div class='section'>";
    echo "<h2>📁 File Check</h2>";
    $criticalFiles = [
        'lib/config_helper.php',
        'lib/oidc.php',
        'lib/logger.php',
        'vendor/autoload.php'
    ];
    
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ $file: Found</div>";
        } else {
            echo "<div class='error'>❌ $file: Missing</div>";
        }
    }
    echo "</div>";
    
    // Session check
    echo "<div class='section'>";
    echo "<h2>🍪 Session Check</h2>";
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        echo "<div class='success'>✅ Session started: " . session_id() . "</div>";
        
        if (!empty($_SESSION)) {
            echo "<div class='info'>📊 Session Data:</div>";
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        } else {
            echo "<div class='info'>📊 Session is empty (user not logged in)</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Quick actions
    echo "<div class='section'>";
    echo "<h2>🚀 Quick Actions</h2>";
    echo "<div class='info'>";
    echo "<a href='/debug_azure.php' style='color: #64b5f6;'>Full Azure Debug Console</a><br>";
    echo "<a href='/admin/cross_tenant_check.php' style='color: #64b5f6;'>Cross-Tenant Check</a><br>";
    echo "<a href='/?clear_session=1' style='color: #64b5f6;'>Clear Session & Retry</a><br>";
    echo "<a href='/error_catcher.php' style='color: #64b5f6;'>Error Catcher Test</a><br>";
    echo "</div>";
    echo "</div>";
    
    echo "</body></html>";
    exit;
}

// Set error handler
set_error_handler(function($severity, $message, $file, $line) {
    $error = new ErrorException($message, 0, $severity, $file, $line);
    show_debug_info($error);
});

// Set exception handler
set_exception_handler(function($exception) {
    show_debug_info($exception);
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        show_debug_info($exception);
    }
});

// Clear session if requested
if (isset($_GET['clear_session'])) {
    session_start();
    session_destroy();
    header('Location: /');
    exit;
}

// Success message if we get here without errors
function debug_success() {
    echo "<div class='success'>✅ No errors detected in debug wrapper</div>";
}
?>
