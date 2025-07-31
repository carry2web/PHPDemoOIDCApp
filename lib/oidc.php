<?php
// File: /lib/oidc.php
// DEBUG logging configuratie
$env = parse_ini_file(__DIR__ . '/../.env');
$debug = isset($env['DEBUG']) && strtolower($env['DEBUG']) === 'true';

if ($debug) {
    ini_set('display_errors', '0'); // Geen errors naar browser
    ini_set('log_errors', '1');
    ini_set('error_reporting', E_ALL);
    error_reporting(E_ALL);

    $logFile = '/home/logs/php_errors.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    ini_set('error_log', $logFile);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '0');
    error_reporting(0);
}

require_once __DIR__ . '/../vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

/**
 * Veilige sessiestart voor Azure Web Apps (geen lokale sessies)
 */
function start_azure_safe_session() {
    $sessionPath = sys_get_temp_dir() . '/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    ini_set('session.save_path', $sessionPath);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
}

/**
 * Verplicht gebruiker om ingelogd te zijn
 */
function ensure_authenticated() {
    if (empty($_SESSION['email'])) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * (Optioneel) OpenID client aanmaken – handig in callback.php
 */
function get_oidc_client() {
    $env = parse_ini_file(__DIR__ . '/../.env');

    $oidc = new OpenIDConnectClient(
        "https://login.microsoftonline.com/{$env['TENANT_ID']}/v2.0",
        $env['CLIENT_ID'],
        $env['CLIENT_SECRET']
    );

    $oidc->setRedirectURL($env['REDIRECT_URI']);
    $oidc->addScope("openid profile email");

    return $oidc;
}
