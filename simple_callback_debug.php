<?php
// Simple callback debug - bypassing complex logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Basic includes
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/config_helper.php';

use Jumbojett\OpenIDConnectClient;

// Start session FIRST before any output
session_start();

echo "<h1>Callback Debug</h1>";

echo "<h2>1. Session Data</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>2. URL Parameters</h2>";
echo "<pre>" . print_r($_GET, true) . "</pre>";

echo "<h2>3. User Type</h2>";
$userType = $_SESSION['auth_user_type'] ?? 'customer';
echo "User Type: $userType<br>";

try {
    echo "<h2>4. Config Loading</h2>";
    $config = get_app_config();
    echo "✅ Config loaded<br>";
    
    if ($userType === 'customer') {
        $clientConfig = $config['b2c'];
        $authority = "https://login.microsoftonline.com/{$clientConfig['tenant_id']}/v2.0";
    } else {
        $clientConfig = $config['b2b'];
        $authority = "https://login.microsoftonline.com/{$clientConfig['tenant_id']}/v2.0";
    }
    
    echo "<h2>5. OIDC Config</h2>";
    echo "Authority: $authority<br>";
    echo "Client ID: " . $clientConfig['client_id'] . "<br>";
    echo "Redirect URI: " . $config['app']['redirect_uri'] . "<br>";
    
    echo "<h2>6. Creating OIDC Client</h2>";
    $oidc = new OpenIDConnectClient(
        $authority,
        $clientConfig['client_id'],
        $clientConfig['client_secret']
    );
    echo "✅ OIDC Client created<br>";
    
    $oidc->setRedirectURL($config['app']['redirect_uri']);
    $oidc->addScope(["openid", "profile", "email"]);
    echo "✅ Redirect URL and scopes set<br>";
    
    echo "<h2>7. Calling authenticate()</h2>";
    $authResult = $oidc->authenticate();
    echo "Authenticate result: " . ($authResult ? 'TRUE' : 'FALSE') . "<br>";
    
    if ($authResult) {
        echo "<h2>8. SUCCESS - Getting Claims</h2>";
        $claims = $oidc->getVerifiedClaims();
        echo "<pre>" . print_r($claims, true) . "</pre>";
        
        $_SESSION['email'] = $claims->email ?? '';
        $_SESSION['name'] = $claims->name ?? '';
        $_SESSION['user_type'] = $userType;
        $_SESSION['authenticated_at'] = time();
        
        echo "<h2>SUCCESS! User authenticated</h2>";
        echo "<a href='dashboard.php'>Go to Dashboard</a>";
    } else {
        echo "<h2>8. FAILURE - Authentication Failed</h2>";
        echo "The OIDC authenticate() method returned false<br>";
        
        // Check if we have required parameters
        if (isset($_GET['code'])) {
            echo "✅ Authorization code present: " . substr($_GET['code'], 0, 20) . "...<br>";
        } else {
            echo "❌ No authorization code in URL<br>";
        }
        
        if (isset($_GET['state'])) {
            echo "✅ State parameter present<br>";
        } else {
            echo "⚠️ No state parameter<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>ERROR</h2>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
