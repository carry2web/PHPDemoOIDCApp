<?php
// File: /lib/oidc.php
// Following Woodgrove security and logging patterns

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config_helper.php';
require_once __DIR__ . '/logger.php';

use Jumbojett\OpenIDConnectClient;

// Initialize logging
setup_logging();

/**
 * Veilige sessiestart voor Azure Web Apps - Woodgrove pattern
 */
function start_azure_safe_session() {
    $config = get_app_config();
    
    $sessionPath = sys_get_temp_dir() . '/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    
    ini_set('session.save_path', $sessionPath);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Lax'); // Woodgrove security setting
    
    session_start();
    
    // Log session events in debug mode
    if ($config['app']['debug']) {
        log_security_event('session_started', ['session_id' => session_id()]);
    }
}

/**
 * Verplicht gebruiker om ingelogd te zijn - Woodgrove pattern with security logging
 */
function ensure_authenticated() {
    if (empty($_SESSION['email'])) {
        log_security_event('unauthorized_access_attempt', [
            'requested_url' => $_SERVER['REQUEST_URI'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ]);
        
        header('Location: /index.php');
        exit;
    }
}

/**
 * Get OIDC client based on user type - Woodgrove configuration pattern
 */
function get_oidc_client($userType = null) {
    $config = get_app_config();
    
    // Determine user type from session or parameter
    if (!$userType) {
        $userType = $_SESSION['user_type'] ?? ($_GET['type'] ?? 'customer');
    }
    
    if ($userType === 'agent') {
        // Internal tenant for agents (employees + B2B guests)
        $clientConfig = $config['b2b'];
        $authority = "https://login.microsoftonline.com/{$clientConfig['tenant_id']}/v2.0";
    } else {
        // External tenant for customers (Microsoft External ID)
        $clientConfig = $config['b2c'];
        // External ID uses standard Microsoft identity platform v2.0 endpoint
        $authority = "https://login.microsoftonline.com/{$clientConfig['tenant_id']}/v2.0";
    }

    $oidc = new OpenIDConnectClient(
        $authority,
        $clientConfig['client_id'],
        $clientConfig['client_secret']
    );

    $oidc->setRedirectURL($config['app']['redirect_uri']);
    $oidc->addScope(["openid", "profile", "email"]);
    
    // Add additional scopes for B2B (Woodgrove pattern)
    if ($userType === 'agent') {
        $oidc->addScope(["https://graph.microsoft.com/User.Read"]);
    }
    
    return $oidc;
}

/**
 * Determine user role based on tenant and user type
 */
function get_user_role($claims, $userType) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    if ($userType === 'agent') {
        // Internal tenant users
        $userTypeInClaims = $claims->userType ?? 'Member';
        
        if ($userTypeInClaims === 'Guest') {
            return 'guest_agent'; // B2B guest agent from external company
        } else {
            return 'employee_agent'; // S-Cape employee
        }
    } else {
        // External tenant users are always customers
        return 'customer';
    }
}

/**
 * Check if user is a guest agent (B2B invited user)
 */
function is_guest_agent($claims, $userType) {
    if ($userType !== 'agent') return false;
    
    $userTypeInClaims = $claims->userType ?? 'Member';
    return $userTypeInClaims === 'Guest';
}

/**
 * Check if user is S-Cape employee
 */
function is_scape_employee($claims, $userType) {
    if ($userType !== 'agent') return false;
    
    $userTypeInClaims = $claims->userType ?? 'Member';
    return $userTypeInClaims === 'Member';
}

/**
 * Start authentication flow
 */
function start_authentication($userType) {
    $logger = ScapeLogger::getInstance();
    
    try {
        $logger->info('Starting authentication flow', ['user_type' => $userType]);
        $config = get_app_config();
        
        $_SESSION['auth_user_type'] = $userType;
        
        $oidc = get_oidc_client($userType);
        
        $logger->info('Starting OIDC authentication');
        $oidc->authenticate();
        
    } catch (Exception $e) {
        $logger->error('Authentication start failed', [
            'user_type' => $userType,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        header('Location: /index.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

/**
 * Handle authentication callback
 */
function handle_authentication_callback() {
    $logger = ScapeLogger::getInstance();
    
    try {
        $userType = $_SESSION['auth_user_type'] ?? 'customer';
        $logger->info('Handling authentication callback', ['user_type' => $userType]);
        
        $oidc = get_oidc_client($userType);
        
        $logger->debug('About to call OIDC authenticate', [
            'user_type' => $userType
        ]);

        $authResult = $oidc->authenticate();
        $logger->debug('OIDC authenticate returned', ['result' => $authResult]);

        if ($authResult) {
            $claims = $oidc->getVerifiedClaims();
            
            $logger->info('Authentication successful', [
                'user_type' => $userType,
                'email' => $claims->email ?? 'unknown',
                'name' => $claims->name ?? 'unknown'
            ]);
            
            // Store user session data
            $_SESSION['email'] = $claims->email ?? '';
            $_SESSION['name'] = $claims->name ?? '';
            $_SESSION['user_type'] = $userType;
            $_SESSION['claims'] = json_encode($claims);
            $_SESSION['authenticated_at'] = time();
            
            // Determine user role
            $role = get_user_role($claims, $userType);
            $_SESSION['role'] = $role;
            
            $logger->info('User session established', [
                'email' => $_SESSION['email'],
                'role' => $role,
                'user_type' => $userType
            ]);
            
            return true;
        } else {
            $logger->error('OIDC authenticate returned false', [
                'user_type' => $userType,
                'GET_params' => $_GET,
                'POST_params' => $_POST,
                'session_auth_user_type' => $_SESSION['auth_user_type'] ?? 'not_set'
            ]);
        }
        
        return false;
        
    } catch (Exception $e) {
        $logger->error('Authentication callback failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}
