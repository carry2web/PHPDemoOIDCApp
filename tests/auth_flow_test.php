<?php
/**
 * Authentication Flow Test Helper
 * Test the actual authentication URLs and flows
 */

require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/oidc.php';

echo "🔐 Authentication Flow Test\n";
echo "===========================\n\n";

$config = get_app_config();

echo "1. Customer Authentication Flow (B2C/External ID)\n";
echo "------------------------------------------------\n";
echo "🌐 Test URL: http://localhost/index.php?user_type=customer\n";
echo "📍 Authority: https://{$config['b2c']['tenant_name']}.ciamlogin.com/{$config['b2c']['tenant_name']}.onmicrosoft.com/v2.0\n";
echo "🔑 Client ID: {$config['b2c']['client_id']}\n";
echo "🔒 Redirect: {$config['app']['redirect_uri']}\n";
echo "📋 Scopes: openid, profile, email\n";
echo "Expected: Microsoft External ID login page\n\n";

echo "2. Agent Authentication Flow (B2B/Internal)\n";
echo "-------------------------------------------\n";
echo "🌐 Test URL: http://localhost/index.php?user_type=agent\n";
echo "📍 Authority: https://login.microsoftonline.com/{$config['b2b']['tenant_id']}/v2.0\n";
echo "🔑 Client ID: {$config['b2b']['client_id']}\n";
echo "🔒 Redirect: {$config['app']['redirect_uri']}\n";
echo "📋 Scopes: openid, profile, email, https://graph.microsoft.com/User.Read\n";
echo "Expected: Microsoft organizational login page\n\n";

echo "3. Test Authentication URLs\n";
echo "---------------------------\n";

// Generate test URLs (without actually redirecting)
session_start();

try {
    // Customer auth URL
    $customerClient = get_oidc_client('customer');
    $customerAuthUrl = $customerClient->getAuthorizationURL();
    echo "✅ Customer Auth URL Generated:\n";
    echo "   $customerAuthUrl\n\n";
    
    // Agent auth URL  
    $agentClient = get_oidc_client('agent');
    $agentAuthUrl = $agentClient->getAuthorizationURL();
    echo "✅ Agent Auth URL Generated:\n";
    echo "   $agentAuthUrl\n\n";
    
} catch (Exception $e) {
    echo "❌ URL generation failed: " . $e->getMessage() . "\n";
}

echo "4. Testing Role Determination\n";
echo "-----------------------------\n";

// Test different claim scenarios
$testScenarios = [
    [
        'name' => 'Customer from External ID',
        'userType' => 'customer',
        'claims' => (object)['email' => 'customer@external.com', 'name' => 'External Customer']
    ],
    [
        'name' => 'Employee Agent',
        'userType' => 'agent',
        'claims' => (object)['email' => 'employee@scape.com.au', 'name' => 'S-Cape Employee', 'userType' => 'Member']
    ],
    [
        'name' => 'Guest Agent (B2B)',
        'userType' => 'agent', 
        'claims' => (object)['email' => 'partner@external.com', 'name' => 'B2B Guest', 'userType' => 'Guest']
    ],
    [
        'name' => 'Admin User',
        'userType' => 'agent',
        'claims' => (object)['email' => 'admin@scape.com.au', 'name' => 'Admin User', 'roles' => ['Admin']]
    ]
];

foreach ($testScenarios as $scenario) {
    $role = determineUserRole($scenario['userType'], $scenario['claims']);
    echo "✅ {$scenario['name']}: $role\n";
}

echo "\n🧪 Manual Testing Steps:\n";
echo "========================\n";
echo "1. Open: http://localhost/index.php\n";
echo "2. Click 'Login as Customer' - should redirect to External ID\n";
echo "3. Click 'Login as Agent' - should redirect to Organizational login\n";
echo "4. Complete authentication and check dashboard shows correct role\n";
echo "5. Test logout clears session properly\n";
echo "\n📋 Test Checklist:\n";
echo "==================\n";
echo "☐ Customer login redirects to External ID tenant\n";
echo "☐ Agent login redirects to organizational tenant\n";
echo "☐ Authentication completes successfully\n";
echo "☐ Dashboard shows correct user information\n";
echo "☐ Role-based access control works\n";
echo "☐ Session variables are set correctly\n";
echo "☐ Logout clears session and redirects properly\n";
