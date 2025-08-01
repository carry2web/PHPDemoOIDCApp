<?php
// Simple authentication test - minimal approach
require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/logger.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

start_azure_safe_session();
$logger = ScapeLogger::getInstance();

// Test the OIDC configuration
echo "<h1>Simple Authentication Test</h1>";

echo "<h2>1. Configuration Test</h2>";
try {
    $config = get_app_config();
    echo "✅ Configuration loaded successfully<br>";
    echo "Customer tenant: " . $config['b2c']['tenant_id'] . "<br>";
    echo "Agent tenant: " . $config['b2b']['tenant_id'] . "<br>";
    echo "Redirect URI: " . $config['app']['redirect_uri'] . "<br>";
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "<br>";
}

echo "<h2>2. OIDC Client Test</h2>";
try {
    $oidcCustomer = get_oidc_client('customer');
    echo "✅ Customer OIDC client created<br>";
    echo "Customer Provider URL: " . $oidcCustomer->getProviderURL() . "<br>";
    echo "Customer Redirect URL: " . $oidcCustomer->getRedirectURL() . "<br>";
    
    $oidcAgent = get_oidc_client('agent');
    echo "✅ Agent OIDC client created<br>";
    echo "Agent Provider URL: " . $oidcAgent->getProviderURL() . "<br>";
    echo "Agent Redirect URL: " . $oidcAgent->getRedirectURL() . "<br>";
} catch (Exception $e) {
    echo "❌ OIDC client error: " . $e->getMessage() . "<br>";
    echo "Error details: " . $e->getFile() . " line " . $e->getLine() . "<br>";
}

echo "<h2>3. Test Authentication</h2>";
echo "<a href='?auth=customer' style='padding: 10px; background: blue; color: white; text-decoration: none; margin: 5px;'>Test Customer Login</a>";
echo "<a href='?auth=agent' style='padding: 10px; background: green; color: white; text-decoration: none; margin: 5px;'>Test Agent Login</a>";

// Handle authentication test
if (isset($_GET['auth'])) {
    $userType = $_GET['auth'];
    echo "<br><br>Starting authentication for: $userType<br>";
    
    // Show the OIDC configuration before authentication
    try {
        $oidc = get_oidc_client($userType);
        echo "OIDC Provider URL: " . $oidc->getProviderURL() . "<br>";
        echo "Redirect URL: " . $oidc->getRedirectURL() . "<br>";
        echo "Client ID: " . (strlen($oidc->getClientID()) > 10 ? substr($oidc->getClientID(), 0, 10) . "..." : $oidc->getClientID()) . "<br>";
        echo "About to call authenticate()...<br>";
        flush(); // Force output before redirect
    } catch (Exception $e) {
        echo "Error creating OIDC client: " . $e->getMessage() . "<br>";
    }
    
    start_authentication($userType);
    echo "If you see this, authenticate() didn't redirect properly<br>";
}

// Show callback info if we're in callback
if (isset($_GET['code'])) {
    echo "<h2>4. Callback Processing</h2>";
    echo "Authorization code received: " . substr($_GET['code'], 0, 20) . "...<br>";
    
    if (handle_authentication_callback()) {
        echo "✅ Authentication successful!<br>";
        echo "User: " . ($_SESSION['email'] ?? 'unknown') . "<br>";
        echo "Role: " . ($_SESSION['role'] ?? 'unknown') . "<br>";
        echo "<a href='dashboard.php'>Go to Dashboard</a>";
    } else {
        echo "❌ Authentication failed<br>";
    }
}

echo "<h2>5. Session Info</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session data: " . json_encode($_SESSION) . "<br>";
?>
