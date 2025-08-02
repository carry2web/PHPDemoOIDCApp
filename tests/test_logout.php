<?php
/**
 * Logout Test Script
 * Test the enhanced logout functionality
 */

require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/oidc.php';

echo "<h2>🚪 Logout Functionality Test</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>🧪 Test Scenarios</h3>";

echo "<h4>1. Current Session Status</h4>";
start_azure_safe_session();

if (!empty($_SESSION)) {
    echo "<p>📊 <strong>Session Data:</strong></p>";
    echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
} else {
    echo "<p>✅ No active session found</p>";
}

echo "<h4>2. Logout Options</h4>";
echo "<p>Test different logout scenarios:</p>";

echo "<div style='margin: 10px 0;'>";
echo "<a href='../logout.php' style='padding: 10px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🚪 Standard Logout</a>";
echo "<a href='../logout.php?azure_logout=1' style='padding: 10px 15px; background: #6f42c1; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🌐 Logout + Azure Signout</a>";
echo "</div>";

echo "<h4>3. Session Cookie Information</h4>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";

if (isset($_COOKIE)) {
    echo "<p><strong>Current Cookies:</strong></p>";
    echo "<ul>";
    foreach ($_COOKIE as $name => $value) {
        echo "<li><code>$name</code>: " . substr($value, 0, 20) . "...</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No cookies found</p>";
}

echo "<h4>4. Test Steps</h4>";
echo "<ol>";
echo "<li>Login as a user (Customer or Agent)</li>";
echo "<li>Navigate to this test page to see session data</li>";
echo "<li>Click one of the logout buttons above</li>";
echo "<li>Verify you're redirected to index.php with confirmation</li>";
echo "<li>Try clicking 'Login as Agent' - should redirect to Microsoft login</li>";
echo "</ol>";

echo "<h4>5. Expected Behavior After Logout</h4>";
echo "<ul>";
echo "<li>✅ All session variables cleared</li>";
echo "<li>✅ Session cookie removed</li>";
echo "<li>✅ Any authentication cookies cleared</li>";
echo "<li>✅ Redirect to index.php with success message</li>";
echo "<li>✅ Fresh login required for access</li>";
echo "</ul>";

echo "</div>";

// If we have session data, show some sample actions
if (!empty($_SESSION['email'])) {
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>🔐 Authenticated User Actions</h3>";
    echo "<p>You are currently logged in as: <strong>" . htmlspecialchars($_SESSION['email']) . "</strong></p>";
    echo "<p>User Type: <strong>" . ($_SESSION['user_type'] ?? 'unknown') . "</strong></p>";
    echo "<p>Role: <strong>" . ($_SESSION['user_role'] ?? 'unknown') . "</strong></p>";
    
    echo "<div style='margin: 10px 0;'>";
    echo "<a href='../dashboard.php' style='padding: 8px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📊 Dashboard</a>";
    echo "<a href='../documents.php' style='padding: 8px 12px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📄 Documents</a>";
    echo "</div>";
    echo "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>🏠 Back to Home</a>";
echo "</div>";

?>
