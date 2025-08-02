<?php
/**
 * Simple Authentication Test
 */

require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/oidc.php';

session_start();

$companyConfig = get_company_config();

echo "<h2>🔐 Simple Authentication Test</h2>";

// Test config loading
try {
    $config = get_app_config();
    echo "<p>✅ Configuration loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Configuration error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test simple authentication scenarios
echo "<h3>Testing Authentication Scenarios</h3>";

// Scenario 1: Customer login
echo "<h4>Customer Scenario</h4>";
echo "<p>Customer email: test@external.com</p>";
echo "<p>Expected: customer role</p>";

// Scenario 2: Employee login  
echo "<h4>Employee Scenario</h4>";
echo "<p>Employee email: {$companyConfig['test_emails']['employee']}</p>";
echo "<p>Expected: agent role</p>";

// Scenario 3: Admin login
echo "<h4>Admin Scenario</h4>";
echo "<p>Admin email: {$companyConfig['admin_email']}</p>";
echo "<p>Expected: admin role</p>";

echo "<hr>";
echo "<p><strong>Domain Configuration:</strong> {$companyConfig['domain']}</p>";
echo "<p><strong>Company:</strong> {$companyConfig['company_name']}</p>";

?>
