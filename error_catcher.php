<?php
// Error catching wrapper for index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start output buffering to catch any issues
ob_start();

try {
    // Try to include the main index.php file
    if (file_exists('index.php')) {
        include 'index.php';
    } else {
        throw new Exception('index.php file not found');
    }
} catch (Throwable $e) {
    // Clear any partial output
    ob_clean();
    
    // Display error information
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Application Error</title>";
    echo "<style>body{font-family:monospace;background:#1e1e1e;color:#fff;padding:20px;}</style>";
    echo "</head><body>";
    echo "<h1>🚨 Application Error Detected</h1>";
    echo "<div style='background:#d32f2f;color:white;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
    echo "<h2>Stack Trace:</h2>";
    echo "<pre style='background:#000;color:#0f0;padding:15px;overflow-x:auto;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "<div style='background:#1976d2;color:white;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<strong>Quick Fixes:</strong><br>";
    echo "1. <a href='debug_azure.php' style='color:#64b5f6;'>Run full diagnostics</a><br>";
    echo "2. Check Azure Application Settings for missing environment variables<br>";
    echo "3. Verify composer dependencies are installed<br>";
    echo "4. Check file permissions and paths<br>";
    echo "</div>";
    echo "</body></html>";
}

// End output buffering
ob_end_flush();
?>
