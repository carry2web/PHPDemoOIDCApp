<?php
/**
 * Authentication Test Runner
 * Runs automated tests and provides detailed reporting
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AuthenticationTestSuite.php';

// Set up proper error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OIDC Authentication Test Suite</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-actions {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .test-actions button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        .test-actions button:hover {
            background: #0056b3;
        }
        .status-indicators {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .status-card {
            flex: 1;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .status-working { background: #d4edda; color: #155724; }
        .status-testing { background: #fff3cd; color: #856404; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-unknown { background: #d1ecf1; color: #0c5460; }
        
        .checklist {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
        .checklist h3 {
            margin-top: 0;
            color: #007bff;
        }
        .checklist ul {
            margin: 0;
            padding-left: 20px;
        }
        .checklist li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 OIDC Authentication Test Suite</h1>
        <p>Comprehensive testing for PHP OIDC authentication against Microsoft Entra</p>
        
        <!-- Status Indicators -->
        <div class="status-indicators">
            <div class="status-card status-working">
                <h4>✅ Known Working</h4>
                <p>Customer authentication with Gmail<br>Dashboard display<br>Session variables</p>
            </div>
            <div class="status-card status-testing">
                <h4>🧪 Needs Testing</h4>
                <p>Agent authentication<br>Different email providers<br>Error handling</p>
            </div>
            <div class="status-card status-unknown">
                <h4>❓ Not Implemented</h4>
                <p>AWS S3 integration<br>PDF document access<br>Full user journey</p>
            </div>
        </div>
        
        <!-- Test Actions -->
        <div class="test-actions">
            <h3>Test Actions</h3>
            <button onclick="runAutomatedTests()">🤖 Run Automated Tests</button>
            <button onclick="testCustomerAuth()">👤 Test Customer Auth</button>
            <button onclick="testAgentAuth()">🏢 Test Agent Auth</button>
            <button onclick="testErrorHandling()">⚠️ Test Error Scenarios</button>
            <button onclick="clearSessions()">🧹 Clear Sessions</button>
            <button onclick="viewLogs()">📄 View Logs</button>
        </div>
        
        <!-- Test Checklist -->
        <div class="checklist">
            <h3>📋 Manual Test Checklist</h3>
            <p>Before declaring the authentication system production-ready, manually test:</p>
            
            <h4>Customer Authentication (External ID)</h4>
            <ul>
                <li><strong>✅ Gmail accounts</strong> - Already tested and working</li>
                <li><strong>❓ Outlook/Hotmail accounts</strong> - Test different Microsoft personal accounts</li>
                <li><strong>❓ Other email providers</strong> - Yahoo, custom domains, etc.</li>
                <li><strong>❓ Social providers</strong> - Facebook, Google (if configured)</li>
                <li><strong>❓ New user registration</strong> - First-time sign-up flow</li>
                <li><strong>❓ Account verification</strong> - Email verification process</li>
            </ul>
            
            <h4>Agent Authentication (B2B)</h4>
            <ul>
                <li><strong>❓ Internal employees</strong> - @s-capepartners.eu accounts</li>
                <li><strong>❓ Guest users</strong> - External collaborators invited to tenant</li>
                <li><strong>❓ Admin roles</strong> - Users with elevated permissions</li>
                <li><strong>❓ Multi-factor authentication</strong> - MFA prompts and handling</li>
            </ul>
            
            <h4>Session & Security</h4>
            <ul>
                <li><strong>❓ Session persistence</strong> - Stays logged in across browser sessions</li>
                <li><strong>❓ Session timeout</strong> - Expires after reasonable time</li>
                <li><strong>❓ Logout functionality</strong> - Clears all session data</li>
                <li><strong>❓ Cross-tenant isolation</strong> - Customer can't access agent areas</li>
                <li><strong>❓ CSRF protection</strong> - No cross-site request forgery vulnerabilities</li>
            </ul>
            
            <h4>Error Handling</h4>
            <ul>
                <li><strong>❓ Invalid redirect</strong> - Malformed callback parameters</li>
                <li><strong>❓ Network timeouts</strong> - Microsoft services unavailable</li>
                <li><strong>❓ Permission denied</strong> - User denies consent</li>
                <li><strong>❓ Domain restrictions</strong> - Users from blocked domains</li>
                <li><strong>❓ Concurrent sessions</strong> - Multiple browser tabs/windows</li>
            </ul>
            
            <h4>Integration Points</h4>
            <ul>
                <li><strong>❌ AWS S3 access</strong> - Document retrieval for authenticated users</li>
                <li><strong>❌ PDF generation</strong> - Dynamic document creation</li>
                <li><strong>❓ Admin functions</strong> - Agent management, user oversight</li>
                <li><strong>❓ Audit logging</strong> - Security events tracked properly</li>
            </ul>
        </div>
        
        <!-- Test Results Container -->
        <div id="test-results">
            <h3>🔬 Test Results</h3>
            <p>Click "Run Automated Tests" to see detailed test results here.</p>
        </div>
        
        <!-- Quick Access Links -->
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h3>🔗 Quick Access</h3>
            <a href="../index.php" style="margin-right: 15px;">🏠 Home Page</a>
            <a href="../callback.php" style="margin-right: 15px;">🔄 Callback (Direct)</a>
            <a href="../dashboard.php" style="margin-right: 15px;">📊 Dashboard</a>
            <a href="../logout.php" style="margin-right: 15px;">🚪 Logout</a>
            <a href="../view_logs.php" style="margin-right: 15px;">📄 View Logs</a>
            <a href="../debug.php" style="margin-right: 15px;">🐛 Debug Info</a>
        </div>
    </div>
    
    <script>
        function runAutomatedTests() {
            document.getElementById('test-results').innerHTML = '<p>🔄 Running automated tests...</p>';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=run_tests')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('test-results').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('test-results').innerHTML = '<p style="color: red;">❌ Error running tests: ' + error + '</p>';
                });
        }
        
        function testCustomerAuth() {
            window.open('../index.php?user_type=customer', '_blank');
        }
        
        function testAgentAuth() {
            window.open('../index.php?user_type=agent', '_blank');
        }
        
        function testErrorHandling() {
            window.open('../callback.php?error=test_error&error_description=Test+error+scenario', '_blank');
        }
        
        function clearSessions() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=clear_session')
                .then(response => response.text())
                .then(data => {
                    alert('Sessions cleared: ' + data);
                    location.reload();
                });
        }
        
        function viewLogs() {
            window.open('../view_logs.php', '_blank');
        }
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'run_tests':
            ob_start();
            $testSuite = new AuthenticationTestSuite();
            $testSuite->runAllTests();
            $output = ob_get_clean();
            echo $output;
            exit;
            
        case 'clear_session':
            session_destroy();
            session_start();
            echo "Session cleared successfully";
            exit;
    }
}
?>
