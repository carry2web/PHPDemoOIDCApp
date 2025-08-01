<?php
// Simple test version of dashboard.php
echo "<h1>Dashboard Test</h1>";
echo "<p>PHP is working!</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test if .env exists
if (file_exists(__DIR__ . '/.env')) {
    echo "<p>✅ .env file found</p>";
    $env = parse_ini_file(__DIR__ . '/.env');
    echo "<p>Environment loaded: " . count($env) . " variables</p>";
} else {
    echo "<p>❌ .env file missing</p>";
}

// Test if lib/oidc.php exists
if (file_exists(__DIR__ . '/lib/oidc.php')) {
    echo "<p>✅ lib/oidc.php found</p>";
} else {
    echo "<p>❌ lib/oidc.php missing</p>";
}

// Original dashboard content (commented out for testing)
/*
require_once __DIR__ . '/lib/oidc.php';
start_azure_safe_session();
ensure_authenticated();

$env = parse_ini_file(__DIR__ . '/.env');
$email = $_SESSION['email'] ?? 'onbekend';
$name = $_SESSION['name'] ?? 'gebruiker';
$roles = $_SESSION['roles'] ?? [];

echo "<h1>Welkom, " . htmlspecialchars($name) . "</h1>";
echo "<p>E-mail: " . htmlspecialchars($email) . "</p>";
echo "<p>Rollen: <pre>" . print_r($roles, true) . "</pre></p>";
echo "<p><a href='/download.php?file=test.pdf'>Test download</a></p>";
echo "<p><a href='/debug.php'>Debug info</a></p>";
*/
?>
