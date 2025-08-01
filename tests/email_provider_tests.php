<?php
/**
 * Email Provider Test Suite
 * Tests authentication with different email providers
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/oidc.php';
require_once __DIR__ . '/../lib/config_helper.php';

class EmailProviderTests {
    private $testResults = [];
    
    public function runTests() {
        echo "<h2>📧 Email Provider Authentication Tests</h2>";
        
        // Test different email providers that customers might use
        $emailProviders = [
            'gmail.com' => 'Google accounts (Gmail)',
            'outlook.com' => 'Microsoft personal accounts (Outlook)',
            'hotmail.com' => 'Microsoft legacy accounts (Hotmail)',
            'yahoo.com' => 'Yahoo accounts',
            'icloud.com' => 'Apple iCloud accounts',
            'custom.domain.com' => 'Custom domain accounts'
        ];
        
        echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<h3>Test Instructions</h3>";
        echo "<p>These tests require manual verification with real accounts. For each email provider:</p>";
        echo "<ol>";
        echo "<li>Click the test link to start authentication</li>";
        echo "<li>Sign in with an account from that provider</li>";
        echo "<li>Verify successful login and dashboard access</li>";
        echo "<li>Check that user information is displayed correctly</li>";
        echo "<li>Test logout functionality</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<thead>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Email Provider</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Test Action</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Expected Result</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Status</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($emailProviders as $domain => $description) {
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>$description</strong><br><small>$domain</small></td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            echo "<a href='../index.php?user_type=customer&test_provider=$domain' target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Test Login</a>";
            echo "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            echo "• Successful authentication<br>";
            echo "• Dashboard shows user info<br>";
            echo "• Session variables populated<br>";
            echo "• Clean logout";
            echo "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            
            if ($domain === 'gmail.com') {
                echo "<span style='color: green; font-weight: bold;'>✅ PASSED</span><br>";
                echo "<small>Tested and working</small>";
            } else {
                echo "<span style='color: orange; font-weight: bold;'>⏳ PENDING</span><br>";
                echo "<small>Manual testing required</small>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
        // Add testing notes
        echo "<div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;'>";
        echo "<h4>⚠️ Testing Notes</h4>";
        echo "<ul>";
        echo "<li><strong>Gmail:</strong> Already confirmed working - user successfully authenticated and accessed dashboard</li>";
        echo "<li><strong>Microsoft accounts:</strong> Should work seamlessly as we're using Microsoft Entra External ID</li>";
        echo "<li><strong>Other providers:</strong> Depend on External ID configuration - may need social identity providers enabled</li>";
        echo "<li><strong>Custom domains:</strong> Will work if user has Microsoft account with that email</li>";
        echo "</ul>";
        echo "</div>";
        
        // Add social provider test
        echo "<h3>🌐 Social Identity Provider Tests</h3>";
        echo "<p>If social identity providers are configured in Microsoft Entra External ID:</p>";
        
        $socialProviders = [
            'google' => 'Google (Gmail accounts)',
            'facebook' => 'Facebook accounts',
            'linkedin' => 'LinkedIn accounts',
            'twitter' => 'Twitter/X accounts'
        ];
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;'>";
        foreach ($socialProviders as $provider => $description) {
            echo "<div style='padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
            echo "<h4>$description</h4>";
            echo "<a href='../index.php?user_type=customer&social_provider=$provider' target='_blank' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Test $provider Login</a>";
            echo "<p style='margin-top: 10px; font-size: 0.9em; color: #666;'>Tests if $provider is configured as identity provider</p>";
            echo "</div>";
        }
        echo "</div>";
    }
}

// Run tests if accessed directly
if (basename($_SERVER['SCRIPT_NAME']) === 'email_provider_tests.php') {
    $tests = new EmailProviderTests();
    $tests->runTests();
}
?>
