<?php
// File: lib/graph_helper.php
require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Get Microsoft Graph access token using client credentials
 */
function get_graph_token() {
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    $client = new Client();
    
    try {
        $response = $client->post("https://login.microsoftonline.com/{$env['GRAPH_TENANT_ID']}/oauth2/v2.0/token", [
            'form_params' => [
                'client_id' => $env['GRAPH_CLIENT_ID'],
                'client_secret' => $env['GRAPH_CLIENT_SECRET'],
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials'
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
        
    } catch (RequestException $e) {
        error_log("Graph token error: " . $e->getMessage());
        return null;
    }
}

/**
 * Create customer account in External (B2C) tenant via Graph API
 * Following Woodgrove pattern for B2C user creation
 */
function create_customer_account($email, $displayName = '') {
    $token = get_graph_token();
    if (!$token) return ['success' => false, 'error' => 'Failed to get Graph token'];
    
    $client = new Client();
    
    // Generate secure temporary password (Woodgrove pattern)
    $tempPassword = generate_secure_password();
    
    // Prepare user data following B2C best practices
    $userData = [
        'accountEnabled' => true,
        'displayName' => $displayName ?: extract_name_from_email($email),
        'mailNickname' => extract_mailnickname($email),
        'userPrincipalName' => $email,
        'passwordProfile' => [
            'password' => $tempPassword,
            'forceChangePasswordNextSignIn' => true
        ],
        'identities' => [
            [
                'signInType' => 'emailAddress',
                'issuer' => get_b2c_domain(),
                'issuerAssignedId' => $email
            ]
        ],
        // Add custom attributes (Woodgrove pattern)
        'extension_' . get_b2c_extension_app_id() . '_CustomerType' => 'B2C',
        'extension_' . get_b2c_extension_app_id() . '_RegistrationSource' => 'WebPortal'
    ];
    
    try {
        $response = $client->post('https://graph.microsoft.com/v1.0/users', [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json'
            ],
            'json' => $userData
        ]);
        
        $user = json_decode($response->getBody(), true);
        
        // Send welcome email with reset link (Woodgrove pattern)
        send_customer_welcome_email_with_reset($email, $displayName);
        
        return [
            'success' => true,
            'userId' => $user['id'],
            'email' => $email,
            'requiresPasswordReset' => true
        ];
        
    } catch (RequestException $e) {
        return handle_graph_error($e, 'create_customer');
    }
}

/**
 * Generate secure password following security best practices
 */
function generate_secure_password() {
    $length = 16;
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($characters), 0, $length) . rand(10, 99);
}

/**
 * Extract display name from email (Woodgrove helper pattern)
 */
function extract_name_from_email($email) {
    $localPart = explode('@', $email)[0];
    return ucwords(str_replace(['.', '_', '-'], ' ', $localPart));
}

/**
 * Extract mail nickname (Woodgrove pattern)
 */
function extract_mailnickname($email) {
    return preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]);
}

/**
 * Get B2C extension app ID for custom attributes
 */
function get_b2c_extension_app_id() {
    // In Woodgrove, this is retrieved from configuration
    return str_replace('-', '', get_b2c_tenant_id());
}

/**
 * Get B2C tenant ID
 */
function get_b2c_tenant_id() {
    $env = parse_ini_file(__DIR__ . '/../.env');
    return $env['EXTERNAL_TENANT_ID'];
}

/**
 * Handle Graph API errors consistently (Woodgrove pattern)
 */
function handle_graph_error($exception, $operation) {
    $errorBody = $exception->getResponse() ? $exception->getResponse()->getBody()->getContents() : '';
    $errorData = json_decode($errorBody, true);
    
    error_log("Graph API error in $operation: " . $exception->getMessage() . " - " . $errorBody);
    
    // Parse specific error codes (Woodgrove pattern)
    if (isset($errorData['error']['code'])) {
        switch ($errorData['error']['code']) {
            case 'Request_ResourceNotFound':
                return ['success' => false, 'error' => 'User not found'];
            case 'Request_MultipleObjectsWithSameKeyValue':
                return ['success' => false, 'error' => 'Email already registered'];
            case 'Authorization_RequestDenied':
                return ['success' => false, 'error' => 'Insufficient permissions'];
            default:
                return ['success' => false, 'error' => 'Registration failed'];
        }
    }
    
    return ['success' => false, 'error' => 'Service temporarily unavailable'];
}

/**
 * Send welcome email with password reset link (Woodgrove pattern)
 */
function send_customer_welcome_email_with_reset($email, $name) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    // In Woodgrove, they generate a password reset link
    $resetUrl = "https://" . get_b2c_domain() . "/b2c_1_susi/oauth2/v2.0/authorize?p=B2C_1_PasswordReset&response_type=code&client_id=" . $env['EXTERNAL_CLIENT_ID'];
    
    $subject = "Welcome to S-Cape Travel - Complete Your Registration";
    $message = "
        Welcome $name!
        
        Your S-Cape Travel account has been created successfully.
        
        To complete your registration and set your password, please click:
        $resetUrl
        
        Best regards,
        The S-Cape Travel Team
    ";
    
    // Log for now - in production integrate with email service
    error_log("Welcome email sent to: $email");
}

/**
 * Invite B2B agent to Internal tenant as guest user
 */
function invite_agent_to_internal_tenant($email, $displayName, $companyName) {
    $token = get_graph_token();
    if (!$token) return ['success' => false, 'error' => 'Failed to get Graph token'];
    
    $env = parse_ini_file(__DIR__ . '/../.env');
    $client = new Client();
    
    $inviteData = [
        'invitedUserEmailAddress' => $email,
        'invitedUserDisplayName' => $displayName,
        'inviteRedirectUrl' => $env['REDIRECT_URI'],
        'sendInvitationMessage' => true,
        'invitedUserMessageInfo' => [
            'customizedMessageBody' => "Welcome to S-Cape Travel! You have been approved as a partner agent from {$companyName}. Please accept this invitation to access our agent portal."
        ]
    ];
    
    try {
        $response = $client->post('https://graph.microsoft.com/v1.0/invitations', [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json'
            ],
            'json' => $inviteData
        ]);
        
        $invitation = json_decode($response->getBody(), true);
        
        return [
            'success' => true,
            'invitationId' => $invitation['id'],
            'inviteRedeemUrl' => $invitation['inviteRedeemUrl'],
            'email' => $email
        ];
        
    } catch (RequestException $e) {
        $errorBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
        error_log("Invite agent error: " . $e->getMessage() . " - " . $errorBody);
        
        return ['success' => false, 'error' => 'Invitation failed'];
    }
}

/**
 * Get B2C domain from environment
 */
function get_b2c_domain() {
    $env = parse_ini_file(__DIR__ . '/../.env');
    // Extract domain from tenant ID or use default
    return $env['EXTERNAL_TENANT_ID'] . '.onmicrosoft.com';
}

/**
 * Send welcome email to new customer
 */
function send_customer_welcome_email($email, $name, $tempPassword) {
    // This would integrate with your email service (SendGrid, etc.)
    // For now, just log it
    error_log("Welcome email sent to: $email with temp password: $tempPassword");
}

/**
 * Send notification to admin about new agent application
 */
function notify_admin_agent_application($name, $email, $company, $reason) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $adminEmail = $env['ADMIN_EMAIL'];
    
    $subject = "New Agent Application: $name from $company";
    $message = "
        New agent application received:
        
        Name: $name
        Email: $email
        Company: $company
        Reason: $reason
        
        Review and approve at: " . $env['REDIRECT_URI'] . "/admin/agents.php
    ";
    
    // This would integrate with your email service
    // For now, just log it
    error_log("Admin notification: $subject - $message");
}
?>
