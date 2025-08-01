<?php
// File: /lib/oidc.php
// Following Woodgrove security and logging patterns

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config_helper.php';

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
        // External tenant for customers (B2C)
        $clientConfig = $config['b2c'];
        $authority = "https://login.microsoftonline.com/{$clientConfig['tenant_id']}/v2.0";
    }

    $oidc = new OpenIDConnectClient(
        $authority,
        $clientConfig['client_id'],
        $clientConfig['client_secret']
    );

    $oidc->setRedirectURL($config['app']['redirect_uri']);
    $oidc->addScope("openid profile email");
    
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
