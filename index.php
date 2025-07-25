<?php
session_start();
require 'vendor/autoload.php'; // Composer autoload for OIDC library

$env = [];
// Load .env variables
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
} else {
    die('Missing .env file');
}

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
<head><title>Login</title></head>
<body>
    <h2>PHP OIDC Demo</h2>
    <a href="?login=1">Login with OIDC</a>
</body>
</html>
