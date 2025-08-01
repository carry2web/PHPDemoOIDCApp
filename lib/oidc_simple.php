<?php
// File: /lib/oidc_simple.php
// Simplified OIDC handling for debugging

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config_helper.php';
require_once __DIR__ . '/logger.php';

use Jumbojett\OpenIDConnectClient;

/**
 * Safe session start for Azure Web Apps
 */
function start_azure_safe_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $sessionPath = sys_get_temp_dir() . '/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0777, true);
        }
        
        ini_set('session.save_path', $sessionPath);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Lax');
        
        session_start();
    }
}

/**
 * Ensure user is authenticated
 */
function ensure_authenticated() {
    if (empty($_SESSION['email'])) {
        header('Location: /index.php');
        exit;
    }
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
        
        if ($userType === 'customer') {
            // Microsoft Entra External ID configuration
            $tenantId = $config['b2c']['tenant_id'];
            // External ID uses standard Microsoft identity platform v2.0 endpoint
            $authority = "https://login.microsoftonline.com/$tenantId/v2.0";
            $clientId = $config['b2c']['client_id'];
            $clientSecret = $config['b2c']['client_secret'];
            
            $logger->debug('External ID configuration', [
                'authority' => $authority,
                'client_id' => $clientId,
                'tenant_id' => $tenantId
            ]);
        } else {
            // B2B Configuration for agents/employees
            $authority = "https://login.microsoftonline.com/{$config['b2b']['tenant_id']}/v2.0";
            $clientId = $config['b2b']['client_id'];
            $clientSecret = $config['b2b']['client_secret'];
            
            $logger->debug('B2B configuration', [
                'authority' => $authority,
                'client_id' => $clientId,
                'tenant_id' => $config['b2b']['tenant_id']
            ]);
        }

        $oidc = new OpenIDConnectClient(
            $authority,
            $clientId,
            $clientSecret
        );

        $oidc->setRedirectURL($config['app']['redirect_uri']);
        $oidc->addScope(["openid", "profile", "email"]);
        
        if ($userType === 'agent') {
            $oidc->addScope(["https://graph.microsoft.com/User.Read"]);
        }
        
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
        
        $config = get_app_config();
        
        if ($userType === 'customer') {
            $tenantId = $config['b2c']['tenant_id'];
            // External ID uses standard Microsoft identity platform v2.0 endpoint
            $authority = "https://login.microsoftonline.com/$tenantId/v2.0";
            $clientId = $config['b2c']['client_id'];
            $clientSecret = $config['b2c']['client_secret'];
        } else {
            $authority = "https://login.microsoftonline.com/{$config['b2b']['tenant_id']}/v2.0";
            $clientId = $config['b2b']['client_id'];
            $clientSecret = $config['b2b']['client_secret'];
        }
        
        $oidc = new OpenIDConnectClient(
            $authority,
            $clientId,
            $clientSecret
        );
        
        $oidc->setRedirectURL($config['app']['redirect_uri']);
        $oidc->addScope(["openid", "profile", "email"]);
        
        if ($userType === 'agent') {
            $oidc->addScope(["https://graph.microsoft.com/User.Read"]);
        }
        
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
            
            // Determine user role
            $role = get_user_role($claims, $userType);
            $_SESSION['role'] = $role;
            
            $logger->info('User session established', [
                'email' => $_SESSION['email'],
                'role' => $role,
                'user_type' => $userType
            ]);
            
            return true;
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

/**
 * Determine user role
 */
function get_user_role($claims, $userType) {
    if ($userType === 'agent') {
        // Check if user has admin role
        $roles = $claims->roles ?? [];
        if (in_array('Admin', $roles)) {
            return 'admin';
        }
        
        // Check user type for B2B guests vs employees
        $userTypeInClaims = $claims->userType ?? 'Member';
        
        if ($userTypeInClaims === 'Guest') {
            return 'guest_agent';
        } else {
            return 'employee_agent';
        }
    } else {
        return 'customer';
    }
}
