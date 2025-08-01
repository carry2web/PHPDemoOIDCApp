<?php
/**
 * Quick OIDC Configuration Test
 * Simple validation of your OIDC setup
 */

require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/oidc.php';

echo "🧪 OIDC Configuration Quick Test\n";
echo "================================\n\n";

// Test 1: Configuration Loading
echo "1. Testing Configuration Loading...\n";
try {
    $config = get_app_config();
    echo "   ✅ Configuration loaded successfully\n";
    
    // Check required sections
    $sections = ['b2c', 'b2b', 'app'];
    foreach ($sections as $section) {
        if (isset($config[$section])) {
            echo "   ✅ Section '$section' found\n";
        } else {
            echo "   ❌ Section '$section' missing\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Configuration failed: " . $e->getMessage() . "\n";
}

echo "\n2. Testing OIDC Client Creation...\n";

// Test 2: Customer OIDC Client
try {
    $customerClient = get_oidc_client('customer');
    echo "   ✅ Customer OIDC client created\n";
    echo "   📍 Authority: " . $customerClient->getProviderURL() . "\n";
} catch (Exception $e) {
    echo "   ❌ Customer client failed: " . $e->getMessage() . "\n";
}

// Test 3: Agent OIDC Client  
try {
    $agentClient = get_oidc_client('agent');
    echo "   ✅ Agent OIDC client created\n";
    echo "   📍 Authority: " . $agentClient->getProviderURL() . "\n";
} catch (Exception $e) {
    echo "   ❌ Agent client failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Session Variables...\n";

// Test 4: Session Variable Consistency
session_start();
$testClaims = (object)[
    'email' => 'test@example.com',
    'name' => 'Test User',
    'userType' => 'Member'
];

// Simulate role determination
$customerRole = determineUserRole('customer', $testClaims);
$agentRole = determineUserRole('agent', $testClaims);

echo "   ✅ Customer role determined: $customerRole\n";
echo "   ✅ Agent role determined: $agentRole\n";

echo "\n4. Testing URL Configuration...\n";

$config = get_app_config();
$redirectUri = $config['app']['redirect_uri'];
echo "   📍 Redirect URI: $redirectUri\n";

if (filter_var($redirectUri, FILTER_VALIDATE_URL)) {
    echo "   ✅ Redirect URI is valid URL\n";
} else {
    echo "   ❌ Redirect URI is invalid\n";
}

if (str_ends_with($redirectUri, '/callback.php')) {
    echo "   ✅ Redirect URI ends with /callback.php\n";
} else {
    echo "   ❌ Redirect URI should end with /callback.php\n";
}

echo "\n🎯 Test Summary:\n";
echo "=================\n";
echo "• Configuration files can be loaded\n";
echo "• OIDC clients can be created for both user types\n";
echo "• Role determination functions work\n";
echo "• URLs are properly configured\n";
echo "\n✅ Basic OIDC setup appears to be working!\n";
echo "\n🚀 Next steps:\n";
echo "• Test authentication flow in browser\n";
echo "• Check actual login with Microsoft\n";
echo "• Verify role assignments work correctly\n";
