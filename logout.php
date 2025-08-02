<?php
// File: logout.php
// Enhanced logout with complete session and cookie cleanup

require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/logger.php';

$logger = ScapeLogger::getInstance();

// Start session to access session data
start_azure_safe_session();

// Log the logout attempt
$logger->info('Logout initiated', [
    'email' => $_SESSION['email'] ?? 'unknown',
    'user_type' => $_SESSION['user_type'] ?? 'unknown',
    'session_id' => session_id(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Store user info for Azure logout URL and Graph API (before clearing session)
$userType = $_SESSION['user_type'] ?? null;
$email = $_SESSION['email'] ?? null;
$access_token = $_SESSION['access_token'] ?? null;
$user_oid = $_SESSION['userinfo']['oid'] ?? $_SESSION['userinfo']['sub'] ?? null;

$logger->info('Stored logout data', [
    'user_type' => $userType,
    'email' => $email,
    'has_access_token' => $access_token ? 'yes' : 'no',
    'user_oid' => $user_oid ? 'yes' : 'no'
]);

// Step 1: Clear all session variables
$_SESSION = array();

// Step 2: Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Step 3: Clear any custom application cookies
if (isset($_COOKIE)) {
    foreach ($_COOKIE as $name => $value) {
        // Clear any cookies that might store authentication state
        if (strpos($name, 'PHPSESSID') !== false || 
            strpos($name, 'session') !== false ||
            strpos($name, 'auth') !== false ||
            strpos($name, 'login') !== false) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
}

// Step 4: Destroy the session
session_destroy();

// Step 5: Log successful logout
$logger->info('Logout completed', [
    'email' => $email,
    'user_type' => $userType,
    'cleared_session' => true,
    'cleared_cookies' => true
]);

// Step 5.5: Microsoft Graph API Token Revocation (Enhanced Logout)
$performGraphLogout = $_GET['graph_logout'] ?? $_GET['complete_logout'] ?? false;
$graph_logout_success = false;

if ($performGraphLogout && $access_token && $user_oid) {
    $logger->info('Attempting Microsoft Graph API logout', [
        'user_oid' => $user_oid,
        'user_type' => $userType
    ]);
    
    $graph_logout_success = revokeUserTokensViaGraph($access_token, $user_oid, $logger);
}

// Step 6: Optional Azure/Microsoft logout
// For complete logout from Microsoft, we could redirect to Azure logout endpoint
// but this might be too aggressive for the user experience
$performAzureLogout = $_GET['azure_logout'] ?? false;

if ($performAzureLogout && $userType) {
    try {
        $config = get_app_config();
        
        if ($userType === 'agent') {
            // B2B tenant logout
            $logoutUrl = "https://login.microsoftonline.com/{$config['b2b']['tenant_id']}/oauth2/v2.0/logout?post_logout_redirect_uri=" . urlencode($config['app']['redirect_uri']);
        } else {
            // B2C tenant logout  
            $logoutUrl = "https://{$config['b2c']['tenant_name']}.ciamlogin.com/{$config['b2c']['tenant_name']}.onmicrosoft.com/oauth2/v2.0/logout?post_logout_redirect_uri=" . urlencode($config['app']['redirect_uri']);
        }
        
        $logger->info('Redirecting to Azure logout', [
            'user_type' => $userType,
            'logout_url' => $logoutUrl
        ]);
        
        header("Location: $logoutUrl");
        exit;
    } catch (Exception $e) {
        $logger->error('Azure logout failed', [
            'error' => $e->getMessage(),
            'user_type' => $userType
        ]);
        // Continue with local logout
    }
}

// Step 7: Redirect to index with logout confirmation
$logout_params = ['logged_out' => '1'];
if ($graph_logout_success) {
    $logout_params['graph_logout'] = '1';
}
if ($performAzureLogout) {
    $logout_params['azure_logout'] = '1';
}

$redirect_url = 'index.php?' . http_build_query($logout_params);
header("Location: $redirect_url");
exit;

/**
 * Revoke user tokens via Microsoft Graph API
 * Implements both revokeSignInSessions and invalidateAllRefreshTokens
 */
function revokeUserTokensViaGraph($access_token, $user_oid, $logger) {
    try {
        $logger->info("Attempting Graph API token revocation for user: $user_oid");
        
        // Method 1: revokeSignInSessions (preferred method)
        $revoke_url = "https://graph.microsoft.com/v1.0/users/$user_oid/revokeSignInSessions";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $revoke_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PHPDemoOIDCApp/1.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $logger->error("Graph API CURL error: $curl_error");
            return false;
        }
        
        $logger->info("Graph API revokeSignInSessions response", [
            'http_code' => $http_code,
            'response' => $response
        ]);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if (isset($result['value']) && $result['value'] === true) {
                $logger->info("✅ Graph API revokeSignInSessions successful");
                return true;
            }
        }
        
        // Method 2: invalidateAllRefreshTokens (fallback)
        $logger->info("Trying fallback method: invalidateAllRefreshTokens");
        
        $invalidate_url = "https://graph.microsoft.com/v1.0/users/$user_oid/invalidateAllRefreshTokens";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $invalidate_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PHPDemoOIDCApp/1.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $logger->info("Graph API invalidateAllRefreshTokens response", [
            'http_code' => $http_code,
            'response' => $response
        ]);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if (isset($result['value']) && $result['value'] === true) {
                $logger->info("✅ Graph API invalidateAllRefreshTokens successful");
                return true;
            }
        }
        
        // Log specific error responses
        if ($http_code === 401) {
            $logger->warning("Graph API authentication failed - access token may be expired");
        } elseif ($http_code === 403) {
            $logger->warning("Graph API access denied - insufficient permissions (User.RevokeSessions.All required)");
        } else {
            $logger->error("Both Graph API methods failed", [
                'final_http_code' => $http_code,
                'final_response' => $response
            ]);
        }
        
        return false;
        
    } catch (Exception $e) {
        $logger->error("Exception during Graph API logout", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}
?>
