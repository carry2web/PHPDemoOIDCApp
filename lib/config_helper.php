<?php
// File: lib/config_helper.php
// Following Woodgrove configuration patterns

require_once __DIR__ . '/logger.php';

/**
 * Get configuration array - Woodgrove pattern for centralized config
 */
function get_app_config() {
    static $config = null;
    
    if ($config === null) {
        $logger = ScapeLogger::getInstance();
        $logger->debug('Loading application configuration');
        
        // Load environment variables manually to handle quotes properly
        $env = [];
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1], "'\"");
                    $env[$key] = $value;
                }
            }
        }
        
        $config = [
            'b2c' => [
                'tenant_id' => $env['EXTERNAL_TENANT_ID'],
                'client_id' => $env['EXTERNAL_CLIENT_ID'],
                'client_secret' => $env['EXTERNAL_CLIENT_SECRET'],
                'tenant_name' => $env['B2C_TENANT_NAME'] ?? 'scapecustomers',
                'domain' => ($env['B2C_TENANT_NAME'] ?? 'scapecustomers') . '.onmicrosoft.com',
                'policy_signup_signin' => $env['B2C_POLICY_SIGNUP_SIGNIN'] ?? 'B2C_1_signupsignin',
                'policy_password_reset' => 'B2C_1_passwordreset',
                'policy_profile_edit' => 'B2C_1_profileedit'
            ],
            'b2b' => [
                'tenant_id' => $env['INTERNAL_TENANT_ID'],
                'client_id' => $env['INTERNAL_CLIENT_ID'],
                'client_secret' => $env['INTERNAL_CLIENT_SECRET'],
                'domain' => $env['INTERNAL_TENANT_DOMAIN'] ?? 'scapetravel.onmicrosoft.com'
            ],
            'graph' => [
                'client_id' => $env['GRAPH_CLIENT_ID'],
                'client_secret' => $env['GRAPH_CLIENT_SECRET'],
                'tenant_id' => $env['GRAPH_TENANT_ID'],
                'scopes' => [
                    'https://graph.microsoft.com/User.ReadWrite.All',
                    'https://graph.microsoft.com/Directory.ReadWrite.All'
                ]
            ],
            'aws' => [
                'region' => $env['AWS_REGION'],
                'bucket' => $env['AWS_S3_BUCKET'],
                'roles' => [
                    'customer' => $env['AWS_ROLE_CUSTOMER'],
                    'agent' => $env['AWS_ROLE_AGENT']
                ]
            ],
            'app' => [
                'redirect_uri' => $env['REDIRECT_URI'],
                'debug' => $env['DEBUG'] === 'true',
                'admin_email' => $env['ADMIN_EMAIL'],
                'admin_role' => $env['ADMIN_ROLE'] ?? 'Admin'
            ],
            'email' => [
                'smtp_host' => $env['SMTP_HOST'] ?? 'smtp.office365.com',
                'smtp_port' => $env['SMTP_PORT'] ?? '587',
                'smtp_username' => $env['SMTP_USERNAME'] ?? '',
                'smtp_password' => $env['SMTP_PASSWORD'] ?? '',
                'from_email' => $env['SMTP_FROM_EMAIL'] ?? '',
            ]
        ];
        
        $logger->debug('Configuration loaded successfully', [
            'has_b2c_config' => !empty($config['b2c']['client_id']),
            'has_b2b_config' => !empty($config['b2b']['client_id']),
            'has_graph_config' => !empty($config['graph']['client_id']),
            'has_aws_config' => !empty($config['aws']['bucket']),
            'debug_mode' => $config['app']['debug']
        ]);
    }
    
    return $config;
}

/**
 * Get B2C policy URLs - Woodgrove pattern
 */
function get_b2c_policy_urls() {
    $config = get_app_config();
    $tenantName = explode('.', $config['b2c']['domain'])[0];
    
    // Following Woodgrove External ID pattern - use standard onmicrosoft.com endpoint
    return [
        'signup_signin' => "https://login.microsoftonline.com/$tenantName.onmicrosoft.com/oauth2/v2.0/authorize",
        'password_reset' => "https://login.microsoftonline.com/$tenantName.onmicrosoft.com/oauth2/v2.0/authorize",
        'profile_edit' => "https://login.microsoftonline.com/$tenantName.onmicrosoft.com/oauth2/v2.0/authorize"
    ];
}

/**
 * Validate environment configuration - Woodgrove pattern
 */
function validate_configuration() {
    $logger = ScapeLogger::getInstance();
    $logger->debug('Starting configuration validation');
    
    $config = get_app_config();
    $errors = [];
    
    // Check required B2C settings
    if (empty($config['b2c']['client_id']) || strpos($config['b2c']['client_id'], 'here') !== false) {
        $errors[] = 'B2C Client ID not configured';
    }
    
    // Check required B2B settings
    if (empty($config['b2b']['client_id']) || strpos($config['b2b']['client_id'], 'here') !== false) {
        $errors[] = 'B2B Client ID not configured';
    }
    
    // Check Graph settings
    if (empty($config['graph']['client_id']) || strpos($config['graph']['client_id'], 'here') !== false) {
        $errors[] = 'Graph API Client ID not configured';
    }
    
    // Check AWS settings
    if (strpos($config['aws']['roles']['customer'], 'ACCOUNT') !== false) {
        $errors[] = 'AWS IAM roles not configured';
    }
    
    // Check admin settings
    if (empty($config['app']['admin_email'])) {
        $errors[] = 'Admin email not configured';
    }
    
    if (!empty($errors)) {
        $logger->error('Configuration validation failed', ['errors' => $errors]);
    } else {
        $logger->info('Configuration validation passed');
    }
    
    return $errors;
}

/**
 * Get logging configuration - Woodgrove pattern
 */
function setup_logging() {
    $logger = ScapeLogger::getInstance();
    $config = get_app_config();
    
    if ($config['app']['debug']) {
        $logger->info('Debug mode enabled, configuring PHP error reporting');
        ini_set('display_errors', '0'); // Never show errors to user
        ini_set('log_errors', '1');
        ini_set('error_reporting', E_ALL);
        
        $logFile = '/tmp/scape_travel.log';
        if (!file_exists($logFile)) {
            touch($logFile);
        }
        ini_set('error_log', $logFile);
        
        $logger->debug('PHP error logging configured', ['log_file' => $logFile]);
    }
}

/**
 * Log security events - Woodgrove pattern
 */
function log_security_event($event, $details = []) {
    $logger = ScapeLogger::getInstance();
    $logger->warning($event, array_merge($details, [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'session_id' => session_id()
    ]));
}

// Legacy function names for backward compatibility
function log_app_info($message, $context = []) {
    $logger = ScapeLogger::getInstance();
    $logger->info($message, $context);
}

function log_app_error($message, $context = []) {
    $logger = ScapeLogger::getInstance();
    $logger->error($message, $context);
}

function log_app_debug($message, $context = []) {
    $logger = ScapeLogger::getInstance();
    $logger->debug($message, $context);
}
?>
