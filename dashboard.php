<?php
require_once __DIR__ . '/../lib/oidc.php';
start_azure_safe_session();
ensure_authenticated();

$env = parse_ini_file(__DIR__ . '/../.env');
$email = $_SESSION['email'] ?? 'onbekend';
$name = $_SESSION['name'] ?? 'gebruiker';
$roles = $_SESSION['roles'] ?? [];

echo "<h1>Welkom, " . htmlspecialchars($name) . "</h1>";
echo "<p>E-mail: " . htmlspecialchars($email) . "</p>";
echo "<p>Rollen: <pre>" . print_r($roles, true) . "</pre></p>";
echo "<p><a href='/download.php?file=test.pdf'>Test download</a></p>";
echo "<p><a href='/debug.php'>Debug info</a></p>";
