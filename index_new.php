<?php
// File: index.php
require_once __DIR__ . '/lib/oidc_simple.php';
require_once __DIR__ . '/lib/logger.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$logger = ScapeLogger::getInstance();
$logger->info('Application started', ['page' => 'index.php']);

start_azure_safe_session();

// Log user visit
$logger->debug('User accessing index page', [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'session_id' => session_id()
]);

// If already logged in, redirect to dashboard
if (!empty($_SESSION['email'])) {
    $logger->info('User already authenticated, redirecting to dashboard', [
        'email' => $_SESSION['email'],
        'user_type' => $_SESSION['user_type'] ?? 'unknown'
    ]);
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// Handle login request with tenant selection
if (isset($_GET['login']) && isset($_GET['type'])) {
    $userType = $_GET['type']; // 'customer' or 'agent'
    
    $logger->info('Login request received', [
        'user_type' => $userType,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    if (in_array($userType, ['customer', 'agent'])) {
        try {
            start_authentication($userType);
        } catch (Exception $e) {
            $logger->error('Login failed', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            $error_message = "Login failed: " . $e->getMessage();
        }
    } else {
        $logger->warning('Invalid user type requested', ['user_type' => $userType]);
        $error_message = "Invalid user type specified.";
    }
}

// Handle registration redirect
if (isset($_GET['register'])) {
    header('Location: register_customer.php');
    exit;
}

// Handle agent application redirect  
if (isset($_GET['apply'])) {
    header('Location: apply_agent.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>S-Cape Travel Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome to S-Cape Travel Portal</h1>
        
        <?php if (isset($_GET['error']) || !empty($error_message)): ?>
            <div class="error-message">
                <?php 
                if (!empty($error_message)) {
                    echo htmlspecialchars($error_message);
                } else {
                    $error = $_GET['error'];
                    $logger->warning('Error displayed to user', ['error' => $error]);
                    
                    switch($error) {
                        case 'auth_failed':
                            echo 'Authentication failed. Please try again or contact support.';
                            break;
                        case 'access_denied':
                            echo 'Access denied. You do not have permission to access this resource.';
                            break;
                        default:
                            echo 'An error occurred: ' . htmlspecialchars($error);
                    }
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="login-section">
            <h2>Customer Login (B2C External Tenant)</h2>
            <p>For customers accessing travel documents and services</p>
            <a href="index.php?login=1&type=customer" class="btn btn-primary">Customer Login</a>
        </div>
        
        <div class="login-section">
            <h2>Agent/Employee Login (Internal Tenant)</h2>
            <p>For S-Cape employees and invited B2B partner agents</p>
            <a href="index.php?login=1&type=agent" class="btn btn-secondary">Agent/Employee Login</a>
        </div>
        
        <div class="register-section">
            <a href="index.php?register=1" class="btn btn-register">Register as Customer</a>
            <a href="index.php?apply=1" class="btn btn-apply">Apply as Partner Agent</a>
        </div>
        
        <div class="footer">
            <p>Secured by Microsoft Identity Platform | Following Woodgrove Security Patterns</p>
        </div>
    </div>
</body>
</html>
