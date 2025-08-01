<?php
// Debug callback to see detailed error information
require_once __DIR__ . '/lib/oidc_simple.php';
require_once __DIR__ . '/lib/logger.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 OIDC Callback Debug</h1>";
echo "<pre>";

$logger = ScapeLogger::getInstance();
$logger->info('Debug callback processing started', ['session_id' => session_id()]);

start_azure_safe_session();

echo "=== SESSION DATA ===\n";
print_r($_SESSION);

echo "\n=== GET PARAMETERS ===\n";
print_r($_GET);

echo "\n=== POST PARAMETERS ===\n"; 
print_r($_POST);

echo "\n=== SERVER INFO ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";

try {
    $userType = $_SESSION['auth_user_type'] ?? 'customer';
    echo "\n=== AUTHENTICATION ATTEMPT ===\n";
    echo "User Type: $userType\n";
    
    $config = get_app_config();
    echo "Config loaded successfully\n";
    
    if ($userType === 'customer') {
        $tenantName = $config['b2c']['tenant_name'];
        $authority = "https://login.microsoftonline.com/$tenantName.onmicrosoft.com/v2.0";
        $clientId = $config['b2c']['client_id'];
        $clientSecret = $config['b2c']['client_secret'];
    } else {
        $authority = "https://login.microsoftonline.com/{$config['b2b']['tenant_id']}/v2.0";
        $clientId = $config['b2b']['client_id'];
        $clientSecret = $config['b2b']['client_secret'];
    }
    
    echo "Authority: $authority\n";
    echo "Client ID: $clientId\n";
    echo "Client Secret: " . (empty($clientSecret) ? 'NOT SET' : 'SET (length: ' . strlen($clientSecret) . ')') . "\n";
    
    $oidc = new OpenIDConnectClient(
        $authority,
        $clientId,
        $clientSecret
    );
    
    $oidc->setRedirectURL($config['app']['redirect_uri']);
    echo "Redirect URI: " . $config['app']['redirect_uri'] . "\n";
    
    $oidc->addScope(["openid", "profile", "email"]);
    
    if ($userType === 'agent') {
        $oidc->addScope(["https://graph.microsoft.com/User.Read"]);
    }
    
    echo "\n=== ATTEMPTING AUTHENTICATION ===\n";
    
    if ($oidc->authenticate()) {
        echo "✅ Authentication successful!\n";
        $claims = $oidc->getVerifiedClaims();
        echo "Claims received:\n";
        print_r($claims);
    } else {
        echo "❌ Authentication failed\n";
        echo "OIDC Errors:\n";
        // Try to get more error info from the OIDC client
        if (method_exists($oidc, 'getLastError')) {
            echo $oidc->getLastError() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION OCCURRED:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
