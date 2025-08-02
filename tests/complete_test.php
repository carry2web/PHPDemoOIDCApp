<?php
/**
 * Complete OIDC Test Suite
 * Comprehensive testing for your authentication system
 */

require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/oidc.php';

echo "\n🔐 Complete OIDC Authentication Test Suite\n";
echo "==========================================\n";

$passed = 0;
$failed = 0;

function test($name, $condition, $message = '') {
    global $passed, $failed;
    if ($condition) {
        echo "✅ \033[32m$name\033[0m";  // Green color in terminal
        if ($message) echo "\n   💡 $message";
        echo "\n";
        $passed++;
    } else {
        echo "❌ \033[31m$name\033[0m";  // Red color in terminal
        if ($message) echo "\n   💥 $message";
        echo "\n";
        $failed++;
    }
}

// Start testing
session_start();

echo "\n1. Configuration Tests\n";
echo "----------------------\n";

try {
    $config = get_app_config();
    test("Configuration Loading", !empty($config), "Configuration loaded successfully");
    test("B2C Config", isset($config['b2c']) && !empty($config['b2c']['client_id']), "B2C tenant configured");
    test("B2B Config", isset($config['b2b']) && !empty($config['b2b']['client_id']), "B2B tenant configured");
    test("App Config", isset($config['app']) && !empty($config['app']['redirect_uri']), "App configuration present");
} catch (Exception $e) {
    test("Configuration", false, "Failed: " . $e->getMessage());
}

echo "\n2. OIDC Client Tests\n";
echo "-------------------\n";

try {
    $customerClient = get_oidc_client('customer');
    test("Customer Client", $customerClient instanceof \Jumbojett\OpenIDConnectClient, "Customer OIDC client created");
    
    $agentClient = get_oidc_client('agent');
    test("Agent Client", $agentClient instanceof \Jumbojett\OpenIDConnectClient, "Agent OIDC client created");
    
    $customerUrl = $customerClient->getProviderURL();
    test("Customer Authority", str_contains($customerUrl, 'ciamlogin.com'), "External ID authority: $customerUrl");
    
    $agentUrl = $agentClient->getProviderURL();
    test("Agent Authority", str_contains($agentUrl, 'login.microsoftonline.com'), "Organizational authority: $agentUrl");
    
} catch (Exception $e) {
    test("OIDC Clients", false, "Failed: " . $e->getMessage());
}

echo "\n3. Role Determination Tests\n";
echo "---------------------------\n";

// Test role scenarios
$testCases = [
    ['customer', (object)['email' => 'test@external.com'], 'customer'],
    ['agent', (object)['email' => 'emp@s-capepartners.eu', 'userType' => 'Member'], 'agent'],
    ['agent', (object)['email' => 'guest@external.com', 'userType' => 'Guest'], 'agent'],
    ['agent', (object)['email' => 'ictsupport@s-capepartners.eu', 'roles' => ['Admin']], 'admin']
];

foreach ($testCases as [$userType, $claims, $expectedRole]) {
    $actualRole = determineUserRole($userType, $claims);
    test("Role: $userType -> $expectedRole", $actualRole === $expectedRole, "Got: $actualRole");
}

echo "\n4. URL and Security Tests\n";
echo "-------------------------\n";

$config = get_app_config();
$redirectUri = $config['app']['redirect_uri'];

test("Redirect URI Valid", filter_var($redirectUri, FILTER_VALIDATE_URL), "URI: $redirectUri");
test("Callback Endpoint", str_ends_with($redirectUri, '/callback.php'), "Ends with /callback.php");

// Test session security settings
test("Session Path Configurable", function_exists('start_azure_safe_session'), "Azure-safe session available");

echo "\n5. Authentication Flow Tests\n";
echo "----------------------------\n";

echo "📋 Manual Test URLs:\n";
echo "   Customer: http://localhost/index.php?user_type=customer\n";
echo "   Agent:    http://localhost/index.php?user_type=agent\n";

try {
    // Generate auth URLs to verify they work
    $_SESSION['auth_user_type'] = 'customer';
    $customerClient = get_oidc_client('customer');
    $customerAuthUrl = $customerClient->getAuthorizationURL();
    test("Customer Auth URL", !empty($customerAuthUrl), "URL generated successfully");
    
    $_SESSION['auth_user_type'] = 'agent';
    $agentClient = get_oidc_client('agent');
    $agentAuthUrl = $agentClient->getAuthorizationURL();
    test("Agent Auth URL", !empty($agentAuthUrl), "URL generated successfully");
    
} catch (Exception $e) {
    test("Auth URLs", false, "Failed: " . $e->getMessage());
}

echo "\n📊 Test Summary\n";
echo "===============\n";
$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "Total Tests: $total\n";
echo "Passed: ✅ \033[32m$passed\033[0m\n";
echo "Failed: ❌ \033[31m$failed\033[0m\n";
echo "Success Rate: \033[33m$percentage%\033[0m\n";

if ($percentage === 100.0) {
    echo "\n🎉 \033[32mALL TESTS PASSED!\033[0m\n";
    echo "Your OIDC authentication system is ready for testing.\n";
} elseif ($percentage >= 80.0) {
    echo "\n⚠️  \033[33mMOSTLY WORKING\033[0m\n";
    echo "Most tests passed. Review the failures above.\n";
} else {
    echo "\n🚨 \033[31mNEEDS ATTENTION\033[0m\n";
    echo "Several tests failed. Check configuration and setup.\n";
}

echo "\n🚀 Next Steps:\n";
echo "=============\n";
echo "1. Open http://localhost/ in your browser\n";
echo "2. Test customer login (External ID)\n";
echo "3. Test agent login (Organizational)\n";
echo "4. Verify dashboard shows correct user info\n";
echo "5. Test role-based access controls\n";
echo "6. Test logout functionality\n";

if ($failed === 0) {
    echo "\n✅ System appears ready for deployment!\n";
}
