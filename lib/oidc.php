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
    
    // Add scopes one by one (jumbojett expects individual calls)
    $oidc->addScope("openid");
    $oidc->addScope("profile");
    $oidc->addScope("email");
    
    // Add additional scopes for B2B (Woodgrove pattern)
    if ($userType === 'agent') {
        $oidc->addScope("https://graph.microsoft.com/User.Read");
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
 * Start authentication flow - SIMPLE VERSION
 */
function start_authentication($userType) {
    $logger = ScapeLogger::getInstance();
    
    try {
        $logger->info('Starting authentication', ['user_type' => $userType]);
        
        $_SESSION['auth_user_type'] = $userType;
        
        $oidc = get_oidc_client($userType);
        
        // Log the OIDC configuration for debugging
        $logger->debug('OIDC client configured', [
            'user_type' => $userType,
            'redirect_url' => $oidc->getRedirectURL(),
            'provider_url' => $oidc->getProviderURL()
        ]);
        
        // This should redirect to Microsoft
        $logger->info('Calling OIDC authenticate - should redirect');
        $oidc->authenticate();
        
        // If we reach here, something went wrong
        $logger->error('OIDC authenticate returned without redirect');
        
    } catch (Exception $e) {
        $logger->error('Authentication failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        header('Location: /index.php?error=auth_failed');
        exit;
    }
}

/**
 * Handle authentication callback - SIMPLE WORKING VERSION
 */
function handle_authentication_callback() {
    $logger = ScapeLogger::getInstance();
    
    try {
        // Check if we have the authorization code
        if (!isset($_GET['code'])) {
            $logger->error('No authorization code in callback');
            return false;
        }

        $userType = $_SESSION['auth_user_type'] ?? 'customer';
        $logger->info('Processing callback', [
            'user_type' => $userType,
            'has_code' => isset($_GET['code']),
            'has_state' => isset($_GET['state'])
        ]);
        
        $oidc = get_oidc_client($userType);
        
        // Simple direct authentication
        if ($oidc->authenticate()) {
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
            
            // Simple role determination
            $role = ($userType === 'agent') ? 'agent' : 'customer';
            $_SESSION['role'] = $role;
            
            $logger->info('User session created', [
                'email' => $_SESSION['email'],
                'role' => $role
            ]);
            
            return true;
        }
        
        $logger->error('OIDC authentication failed');
        return false;
        
    } catch (Exception $e) {
        $logger->error('Callback failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        return false;
    }
}
