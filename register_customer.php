<?php
// File: register_customer.php
require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/logger.php';

$logger = ScapeLogger::getInstance();
$logger->info('Customer registration page accessed');

start_azure_safe_session();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration - S-Cape Travel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Customer Registration</h1>
        
        <div class="info-box">
            <h3>Create Your S-Cape Travel Account</h3>
            <p>To register as a customer:</p>
            <ol>
                <li>Click "Customer Login" on the main page</li>
                <li>Select "Sign up now" during the login process</li>
                <li>Complete your profile information</li>
                <li>Start booking your travel experiences</li>
            </ol>
        </div>
        
        <div class="navigation">
            <a href="index.php?login=1&type=customer" class="btn btn-primary">Start Customer Registration</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>