<?php
// File: callback.php
session_start();
require 'vendor/autoload.php';

$env = parse_ini_file(__DIR__ . '/.env');

$provider_url = 'https://login.microsoftonline.com/' . $env['TENANT_ID'] . '/v2.0';
$oidc = new Jumbojett\OpenIDConnectClient(
    $provider_url,
    $env['CLIENT_ID'],
    $env['CLIENT_SECRET']
);
$oidc->setRedirectURL($env['REDIRECT_URI']);

try {
    $oidc->authenticate();
    $_SESSION['user'] = [
        'claims' => $oidc->getVerifiedClaims(),
        'id_token' => $oidc->getIdToken()
    ];
    header('Location: dashboard.php');
    exit;
} catch (Exception $e) {
    echo 'Authentication failed: ' . $e->getMessage();
}