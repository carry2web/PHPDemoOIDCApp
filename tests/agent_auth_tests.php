<?php
/**
 * Agent Authentication Test Suite
 * Specific tests for B2B agent authentication flows
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/oidc.php';
require_once __DIR__ . '/../lib/config_helper.php';

class AgentAuthTests {
    public function runTests() {
        echo "<h2>🏢 Agent Authentication Tests (B2B)</h2>";
        
        // Get B2B configuration
        $config = get_app_config();
        $b2bTenantId = $config['b2b']['tenant_id'];
        
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<h3>🔍 B2B Tenant Information</h3>";
        echo "<p><strong>Tenant ID:</strong> $b2bTenantId</p>";
        echo "<p><strong>Authority:</strong> https://login.microsoftonline.com/$b2bTenantId/v2.0</p>";
        echo "<p><strong>Expected Users:</strong> Employees and invited guests</p>";
        echo "</div>";
        
        // Test scenarios for agents
        $agentTestScenarios = [
            [
                'title' => 'Internal Employee Authentication',
                'description' => 'Test with @s-capepartners.eu email addresses',
                'test_url' => '../index.php?user_type=agent',
                'expected' => [
                    'Should redirect to Microsoft organizational login',
                    'Employee should see familiar company login page',
                    'MFA prompt if enabled',
                    'Successful authentication to dashboard',
                    'User role should be "agent" or "employee"',
                    'Access to admin functions if authorized'
                ],
                'status' => 'pending'
            ],
            [
                'title' => 'Guest User Authentication',
                'description' => 'Test with external users invited to B2B tenant',
                'test_url' => '../index.php?user_type=agent&guest=true',
                'expected' => [
                    'Guest user can authenticate with their external email',
                    'Appropriate permissions and access levels',
                    'Limited access compared to internal employees',
                    'Proper user type identification'
                ],
                'status' => 'pending'
            ],
            [
                'title' => 'Admin Role Authentication',
                'description' => 'Test with users having admin permissions',
                'test_url' => '../index.php?user_type=agent&admin=true',
                'expected' => [
                    'Admin users get elevated permissions',
                    'Access to user management functions',
                    'Cross-tenant visibility if configured',
                    'Audit trail for admin actions'
                ],
                'status' => 'pending'
            ],
            [
                'title' => 'Multi-Factor Authentication',
                'description' => 'Test MFA flow if enabled in B2B tenant',
                'test_url' => '../index.php?user_type=agent&force_mfa=true',
                'expected' => [
                    'MFA challenge presented after password',
                    'Support for various MFA methods (SMS, app, etc.)',
                    'Proper handling of MFA failures',
                    'Session security after MFA completion'
                ],
                'status' => 'pending'
            ]
        ];
        
        echo "<div style='margin-bottom: 20px;'>";
        foreach ($agentTestScenarios as $index => $scenario) {
            echo "<div style='border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; overflow: hidden;'>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd;'>";
            echo "<h4 style='margin: 0;'>{$scenario['title']}</h4>";
            echo "<p style='margin: 5px 0 0 0; color: #666;'>{$scenario['description']}</p>";
            echo "</div>";
            echo "<div style='padding: 15px;'>";
            
            echo "<div style='margin-bottom: 15px;'>";
            echo "<strong>Expected Results:</strong>";
            echo "<ul>";
            foreach ($scenario['expected'] as $expectation) {
                echo "<li>$expectation</li>";
            }
            echo "</ul>";
            echo "</div>";
            
            echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
            echo "<a href='{$scenario['test_url']}' target='_blank' style='background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>🧪 Run Test</a>";
            
            $statusColor = $scenario['status'] === 'pending' ? 'orange' : 'green';
            echo "<span style='color: $statusColor; font-weight: bold;'>⏳ {$scenario['status']}</span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        
        // Configuration validation
        echo "<h3>⚙️ B2B Configuration Validation</h3>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        
        try {
            $agentClient = get_oidc_client('agent');
            echo "<div style='color: green;'>✅ Agent OIDC client created successfully</div>";
            
            $authority = $agentClient->getProviderURL();
            echo "<div style='color: green;'>✅ Authority URL: $authority</div>";
            
            // Test if we can reach the B2B tenant's metadata
            $metadataUrl = $authority . '/.well-known/openid_configuration';
            echo "<div>📋 Metadata URL: <a href='$metadataUrl' target='_blank'>$metadataUrl</a></div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Error creating agent client: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
        
        // Manual testing checklist
        echo "<h3>📋 Manual Testing Checklist</h3>";
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<h4>Required Test Accounts</h4>";
        echo "<ul>";
        echo "<li><strong>Internal Employee:</strong> An active @s-capepartners.eu account</li>";
        echo "<li><strong>Guest User:</strong> External user invited to the B2B tenant</li>";
        echo "<li><strong>Admin User:</strong> Account with administrative privileges</li>";
        echo "<li><strong>Standard User:</strong> Regular employee without admin rights</li>";
        echo "</ul>";
        
        echo "<h4>Test Steps for Each Account</h4>";
        echo "<ol>";
        echo "<li>Click 'Run Test' for each scenario above</li>";
        echo "<li>Sign in with the appropriate test account</li>";
        echo "<li>Complete any MFA challenges</li>";
        echo "<li>Verify successful redirect to dashboard</li>";
        echo "<li>Check user information and permissions</li>";
        echo "<li>Test access to admin functions (if applicable)</li>";
        echo "<li>Verify clean logout</li>";
        echo "</ol>";
        
        echo "<h4>⚠️ Important Notes</h4>";
        echo "<ul>";
        echo "<li>B2B tenant users must exist in the tenant or be invited as guests</li>";
        echo "<li>MFA settings are controlled by the B2B tenant's conditional access policies</li>";
        echo "<li>Admin permissions are managed through Azure AD roles and groups</li>";
        echo "<li>Guest users may have different consent prompts</li>";
        echo "</ul>";
        echo "</div>";
    }
}

// Run tests if accessed directly
if (basename($_SERVER['SCRIPT_NAME']) === 'agent_auth_tests.php') {
    $tests = new AgentAuthTests();
    $tests->runTests();
}
?>
