<?php
// File: callback.php
require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/logger.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$logger = ScapeLogger::getInstance();
$logger->info('Callback processing started', ['session_id' => session_id()]);

start_azure_safe_session();

try {
    // Debug mode - show what we received
    if (isset($_GET['debug']) || true) { // Always debug for now
        echo "<h1>Callback Debug Information</h1>";
        echo "<h2>URL Parameters Received:</h2>";
        echo "<pre>" . print_r($_GET, true) . "</pre>";
        echo "<h2>Session Data:</h2>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        echo "<hr>";
    }
    
    if (handle_authentication_callback()) {
        $logger->info('Authentication callback successful, redirecting to dashboard');
        echo "<h2>SUCCESS!</h2><p>Authentication successful. <a href='dashboard.php'>Go to Dashboard</a></p>";
        // header('Location: dashboard.php');
        // exit;
    } else {
        $logger->error('Authentication callback failed');
        echo "<h2>FAILED!</h2><p>Authentication callback failed. <a href='index.php'>Try Again</a></p>";
        // header('Location: index.php?error=auth_callback_failed');
        // exit;
    }
} catch (Exception $e) {
    $logger->error('Callback processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    echo "<h2>ERROR!</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='index.php'>Try Again</a>";
    // header('Location: index.php?error=' . urlencode($e->getMessage()));
    // exit;
}
?>
