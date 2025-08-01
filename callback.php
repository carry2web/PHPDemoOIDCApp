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
    // Check for Microsoft authentication errors first
    if (isset($_GET['error'])) {
        echo "<h1>Authentication Error</h1>";
        echo "<div style='background-color: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px 0; border-radius: 4px;'>";
        echo "<h3>Error: " . htmlspecialchars($_GET['error']) . "</h3>";
        
        if (isset($_GET['error_description'])) {
            $description = $_GET['error_description'];
            echo "<p><strong>Description:</strong> " . htmlspecialchars($description) . "</p>";
            
            // Provide user-friendly explanations for common errors
            if (strpos($description, 'AADSTS500208') !== false) {
                echo "<div style='background-color: #e3f2fd; border: 1px solid #2196f3; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
                echo "<h4>What this means:</h4>";
                echo "<p>Your email domain is not allowed to register as a customer. This is expected behavior for security.</p>";
                echo "<p><strong>For customer registration, please use:</strong></p>";
                echo "<ul>";
                echo "<li>Personal Gmail accounts (@gmail.com)</li>";
                echo "<li>Yahoo accounts (@yahoo.com)</li>";
                echo "<li>Other personal email providers</li>";
                echo "</ul>";
                echo "<p><strong>Business/Organization emails are not allowed</strong> for customer registration.</p>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        echo "<p><a href='index.php' style='background-color: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Back to Login</a></p>";
        echo "<hr>";
        
        // Still show debug info
        echo "<h2>Debug Information:</h2>";
        echo "<h3>URL Parameters Received:</h3>";
        echo "<pre>" . print_r($_GET, true) . "</pre>";
        echo "<h3>Session Data:</h3>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        
        // Exit early - don't try to process authentication
        exit;
    }
    
    // Debug mode - show what we received (disable for production)
    $showDebug = isset($_GET['debug']) && $_GET['debug'] === '1';
    if ($showDebug) {
        echo "<h1>Callback Debug Information</h1>";
        echo "<h2>URL Parameters Received:</h2>";
        echo "<pre>" . print_r($_GET, true) . "</pre>";
        echo "<h2>Session Data:</h2>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        echo "<hr>";
    }
    
    if (handle_authentication_callback()) {
        $logger->info('Authentication callback successful, redirecting to dashboard');
        if ($showDebug) {
            echo "<h2>SUCCESS!</h2><p>Authentication successful. <a href='dashboard.php'>Go to Dashboard</a></p>";
        } else {
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $logger->error('Authentication callback failed');
        if ($showDebug) {
            echo "<h2>FAILED!</h2><p>Authentication callback failed. <a href='index.php'>Try Again</a></p>";
        } else {
            header('Location: index.php?error=auth_callback_failed');
            exit;
        }
    }
} catch (Exception $e) {
    $logger->error('Callback processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $showDebug = isset($_GET['debug']) && $_GET['debug'] === '1';
    if ($showDebug) {
        echo "<h2>ERROR!</h2>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='index.php'>Try Again</a>";
    } else {
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}
?>
