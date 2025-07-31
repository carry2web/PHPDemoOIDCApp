<?php
// File: index.php
session_start();
require 'vendor/autoload.php'; // Composer autoload for OIDC library

$env = parse_ini_file(__DIR__ . '/.env');

$provider_url = 'https://login.microsoftonline.com/' . $env['TENANT_ID'] . '/v2.0';
$oidc = new Jumbojett\OpenIDConnectClient(
    $provider_url,
    $env['CLIENT_ID'],
    $env['CLIENT_SECRET']
);
$oidc->setRedirectURL($env['REDIRECT_URI']);

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['login'])) {
    $oidc->authenticate();
    $_SESSION['user'] = [
        'claims' => $oidc->getVerifiedClaims(),
        'id_token' => $oidc->getIdToken()
    ];
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Welcome to S-Cape Travel Portal</h2>
    <div class="link-block">
        <a class="button" href="?login=1">Login with Microsoft</a><br>
        <a class="button" href="register_customer.php">Register as Customer</a><br>
        <a class="button" href="register_agent.php">Register as Agent</a>
    </div>
</body>
</html>