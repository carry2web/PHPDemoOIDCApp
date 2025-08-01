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
    if (handle_authentication_callback()) {
        $logger->info('Authentication callback successful, redirecting to dashboard');
        header('Location: dashboard.php');
        exit;
    } else {
        $logger->error('Authentication callback failed');
        header('Location: index.php?error=auth_callback_failed');
        exit;
    }
} catch (Exception $e) {
    $logger->error('Callback processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>
