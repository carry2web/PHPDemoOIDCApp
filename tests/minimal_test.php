<?php
// Minimal OIDC test
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing jumbojett library...<br>";

try {
    $oidc = new Jumbojett\OpenIDConnectClient(
        'https://login.microsoftonline.com/37a2c2da-5eec-4680-b380-2c0a72013f67/v2.0',
        'dummy_client_id',
        'dummy_secret'
    );
    echo "✅ OpenIDConnectClient created successfully<br>";
    echo "Provider URL: " . $oidc->getProviderURL() . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}
?>
