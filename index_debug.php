<?php
// File: index_debug.php
// Enhanced debug version of index.php

// Include debug wrapper first
require_once __DIR__ . '/debug_wrapper.php';

try {
    // Start with comprehensive error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    echo "<!-- Debug: Starting index_debug.php -->\n";
    
    // Check if main files exist
    if (!file_exists(__DIR__ . '/lib/oidc_simple.php')) {
        throw new Exception('Critical file missing: lib/oidc_simple.php');
    }
    
    if (!file_exists(__DIR__ . '/lib/logger.php')) {
        throw new Exception('Critical file missing: lib/logger.php');
    }
    
    echo "<!-- Debug: Including core libraries -->\n";
    require_once __DIR__ . '/lib/oidc_simple.php';
    require_once __DIR__ . '/lib/logger.php';
    
    echo "<!-- Debug: Initializing logger -->\n";
    $logger = ScapeLogger::getInstance();
    $logger->info('Debug version - Application started', ['page' => 'index_debug.php']);
    
    echo "<!-- Debug: Starting session -->\n";
    start_azure_safe_session();
    
    // Log user visit
    $logger->debug('User accessing debug index page', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'session_id' => session_id()
    ]);
    
    echo "<!-- Debug: Checking if user already logged in -->\n";
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
    
    echo "<!-- Debug: Processing login requests -->\n";
    // Handle login request with tenant selection
    if (isset($_GET['login']) && isset($_GET['type'])) {
        $userType = $_GET['type']; // 'customer' or 'agent'
        
        $logger->info('Login request received', [
            'user_type' => $userType,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        if (in_array($userType, ['customer', 'agent'])) {
            try {
                echo "<!-- Debug: Starting authentication for $userType -->\n";
                start_authentication($userType);
            } catch (Exception $e) {
                $logger->error('Login failed', [
                    'user_type' => $userType,
                    'error' => $e->getMessage()
                ]);
                $error_message = 'Login failed: ' . $e->getMessage();
            }
        } else {
            $error_message = 'Invalid user type specified.';
            $logger->warning('Invalid user type requested', ['user_type' => $userType]);
        }
    }
    
    echo "<!-- Debug: Rendering HTML -->\n";
    
} catch (Exception $e) {
    // This will be caught by our debug wrapper
    throw $e;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S-Cape Travel - Login (Debug)</title>
    <link href="style.css" rel="stylesheet">
    <style>
        .debug-info {
            background: #1e1e1e;
            color: #0f0;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .debug-success {
            background: #388e3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="debug-success">✅ Debug Version - Page loaded successfully!</div>
    <div class="debug-info">
        Debug Info: <?= date('Y-m-d H:i:s') ?> | Session: <?= session_id() ?> | 
        User: <?= !empty($_SESSION['email']) ? $_SESSION['email'] : 'Not logged in' ?>
    </div>

    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h1>🌍 S-Cape Travel</h1>
                <p class="subtitle">Cross-Tenant Authentication Platform (Debug Mode)</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <div class="tenant-selection">
                <h2>Choose Your Login Type</h2>
                
                <div class="tenant-card customer-card">
                    <div class="tenant-info">
                        <h3>🛒 Customer Portal</h3>
                        <p>Personal travel document management</p>
                        <div class="tenant-details">
                            <span class="tenant-type">B2C Tenant</span>
                            <span class="tenant-name">scapecustomers</span>
                        </div>
                    </div>
                    <a href="?login=1&type=customer" class="btn btn-primary">
                        Customer Login
                    </a>
                </div>
                
                <div class="tenant-card agent-card">
                    <div class="tenant-info">
                        <h3>🏢 Agent Portal</h3>
                        <p>Partner and employee access</p>
                        <div class="tenant-details">
                            <span class="tenant-type">B2B Tenant</span>
                            <span class="tenant-name">S-Cape Partners</span>
                        </div>
                    </div>
                    <a href="?login=1&type=agent" class="btn btn-primary">
                        Agent Login
                    </a>
                </div>
            </div>
            
            <div class="features">
                <h3>Platform Features</h3>
                <ul>
                    <li>🔐 Cross-tenant authentication</li>
                    <li>📁 AWS S3 document management</li>
                    <li>✉️ Microsoft Graph email integration</li>
                    <li>👥 Multi-role access control</li>
                </ul>
            </div>
            
            <div class="debug-actions">
                <h3>🛠️ Debug Actions</h3>
                <div class="debug-links">
                    <a href="debug_azure.php" class="btn btn-secondary">Full Debug Console</a>
                    <a href="admin/cross_tenant_check.php" class="btn btn-secondary">Cross-Tenant Check</a>
                    <a href="?clear_session=1" class="btn btn-secondary">Clear Session</a>
                    <a href="index.php" class="btn btn-secondary">Normal Version</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="debug-info">
        ✅ Page rendered successfully | Memory: <?= round(memory_get_usage(true) / 1024 / 1024, 2) ?>MB | 
        Peak: <?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?>MB
    </div>
</body>
</html>
