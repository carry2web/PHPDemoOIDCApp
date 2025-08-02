<?php
/**
 * Comprehensive Test Dashboard
 * Central hub for all authentication testing
 */

require_once __DIR__ . '/../vendor/autoload.php';

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OIDC Authentication Test Dashboard - Organized Test Suite</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        .nav-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }
        .nav-tab.active {
            background: white;
            border-bottom: 3px solid #007bff;
            color: #007bff;
            font-weight: bold;
        }
        .nav-tab:hover {
            background: #e9ecef;
        }
        .tab-content {
            display: none;
            padding: 30px;
        }
        .tab-content.active {
            display: block;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .test-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .test-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .test-card-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .test-card-header h3 {
            margin: 0;
            color: #333;
        }
        .test-card-body {
            padding: 15px;
        }
        .test-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 5px 5px 0;
            transition: background 0.2s;
        }
        .test-button:hover {
            background: #0056b3;
        }
        .test-button.success { background: #28a745; }
        .test-button.warning { background: #ffc107; color: #212529; }
        .test-button.danger { background: #dc3545; }
        .test-button.info { background: #17a2b8; }
        
        .status-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .status-item {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            color: white;
        }
        .status-working { background: linear-gradient(135deg, #28a745, #20c997); }
        .status-testing { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .status-failed { background: linear-gradient(135deg, #dc3545, #e83e8c); }
        .status-pending { background: linear-gradient(135deg, #6c757d, #495057); }
        
        .quick-actions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .quick-actions h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 OIDC Authentication Test Dashboard</h1>
            <p>Comprehensive testing suite for PHP OIDC authentication against Microsoft Entra</p>
        </div>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('overview')">📊 Overview</button>
            <button class="nav-tab" onclick="showTab('automated')">🤖 Automated Tests</button>
            <button class="nav-tab" onclick="showTab('customer')">👤 Customer Tests</button>
            <button class="nav-tab" onclick="showTab('agent')">🏢 Agent Tests</button>
            <button class="nav-tab" onclick="showTab('integration')">🔗 Integration Tests</button>
            <button class="nav-tab" onclick="showTab('logs')">📄 Logs & Debug</button>
        </div>
        
        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="status-summary">
                <div class="status-item status-working">
                    <h3>✅ Working</h3>
                    <p>Customer auth with Gmail<br>Dashboard display<br>Session management</p>
                </div>
                <div class="status-item status-testing">
                    <h3>🧪 Testing</h3>
                    <p>Agent authentication<br>Email providers<br>Error scenarios</p>
                </div>
                <div class="status-item status-pending">
                    <h3>⏳ Pending</h3>
                    <p>AWS S3 integration<br>PDF access<br>Full user journey</p>
                </div>
                <div class="status-item status-failed">
                    <h3>❌ Issues</h3>
                    <p>None identified<br>System stable<br>Ready for testing</p>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3>🚀 Quick Actions</h3>
                <a href="../index.php?user_type=customer" class="test-button success" target="_blank">Test Customer Login</a>
                <a href="../index.php?user_type=agent" class="test-button info" target="_blank">Test Agent Login</a>
                <a href="../dashboard.php" class="test-button" target="_blank">View Dashboard</a>
                <a href="../logout.php" class="test-button warning">Logout</a>
                <button class="test-button danger" onclick="clearAllSessions()">Clear Sessions</button>
            </div>
            
            <div class="test-grid">
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>🎯 Current Status</h3>
                    </div>
                    <div class="test-card-body">
                        <p><strong>Last Success:</strong> Customer authentication with Gmail</p>
                        <p><strong>Dashboard:</strong> Displaying user profile correctly</p>
                        <p><strong>Session:</strong> All variables populated consistently</p>
                        <p><strong>Next Steps:</strong> Comprehensive testing across providers</p>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>📋 Test Priority</h3>
                    </div>
                    <div class="test-card-body">
                        <p><strong>High:</strong> Agent B2B authentication</p>
                        <p><strong>Medium:</strong> Multiple email providers</p>
                        <p><strong>Low:</strong> Edge case error handling</p>
                        <p><strong>Future:</strong> AWS S3 integration</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Automated Tests Tab -->
        <div id="automated" class="tab-content">
            <iframe src="test_runner.php" width="100%" height="600" style="border: 1px solid #ddd; border-radius: 5px;"></iframe>
        </div>
        
        <!-- Customer Tests Tab -->
        <div id="customer" class="tab-content">
            <iframe src="email_provider_tests.php" width="100%" height="600" style="border: 1px solid #ddd; border-radius: 5px;"></iframe>
        </div>
        
        <!-- Agent Tests Tab -->
        <div id="agent" class="tab-content">
            <iframe src="agent_auth_tests.php" width="100%" height="600" style="border: 1px solid #ddd; border-radius: 5px;"></iframe>
        </div>
        
        <!-- Integration Tests Tab -->
        <div id="integration" class="tab-content">
            <h2>🔗 Integration & End-to-End Tests</h2>
            
            <div class="test-grid">
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>📄 Document Access Flow</h3>
                    </div>
                    <div class="test-card-body">
                        <p>Test the complete user journey from authentication to document access:</p>
                        <ol>
                            <li>User authenticates successfully</li>
                            <li>User accesses document listing</li>
                            <li>User downloads PDF from S3</li>
                            <li>User logs out cleanly</li>
                        </ol>
                        <a href="../documents.php" class="test-button" target="_blank">Test Document Flow</a>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>🛡️ Security & Cross-Tenant</h3>
                    </div>
                    <div class="test-card-body">
                        <p>Verify security boundaries and tenant isolation:</p>
                        <ul>
                            <li>Customers can't access agent areas</li>
                            <li>Agents can't see customer-only content</li>
                            <li>Session isolation between user types</li>
                            <li>Proper role enforcement</li>
                        </ul>
                        <a href="../admin/cross_tenant_check.php" class="test-button warning" target="_blank">Test Security</a>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>☁️ AWS S3 Integration</h3>
                    </div>
                    <div class="test-card-body">
                        <p>Test AWS S3 document storage and retrieval:</p>
                        <ul>
                            <li>S3 credentials configuration</li>
                            <li>Bucket access permissions</li>
                            <li>File upload/download</li>
                            <li>Error handling for missing files</li>
                        </ul>
                        <a href="../test_s3_integration.php" class="test-button info" target="_blank">Test S3</a>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>🔄 Session Management & Logout</h3>
                    </div>
                    <div class="test-card-body">
                        <p>Test session persistence and logout security:</p>
                        <ul>
                            <li>Session timeout handling</li>
                            <li>Concurrent session management</li>
                            <li>Session security settings</li>
                            <li>Clean logout process</li>
                            <li>Cookie clearing</li>
                            <li>Azure signout integration</li>
                        </ul>
                        <div style="margin-top: 10px;">
                            <a href="test_logout.php" class="test-button info" target="_blank" style="margin-right: 5px;">🚪 Test Logout</a>
                            <a href="session_cleanup.php" class="test-button warning" target="_blank">🧹 Session Cleanup</a>
                        </div>
                        <button class="test-button" onclick="testSessionManagement()">Test Sessions</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Logs & Debug Tab -->
        <div id="logs" class="tab-content">
            <div class="test-grid">
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>📄 Application Logs</h3>
                    </div>
                    <div class="test-card-body">
                        <p>View application logs for debugging and monitoring:</p>
                        <a href="../view_logs.php" class="test-button" target="_blank">View All Logs</a>
                        <a href="../view_logs.php?filter=error" class="test-button danger" target="_blank">Error Logs</a>
                        <a href="../view_logs.php?filter=auth" class="test-button info" target="_blank">Auth Logs</a>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>🐛 Debug Information</h3>
                    </div>
                    <div class="test-card-body">
                        <p>Access debug tools and system information:</p>
                        <a href="../debug.php" class="test-button" target="_blank">Debug Info</a>
                        <a href="../debug_oidc_config.php" class="test-button" target="_blank">OIDC Config</a>
                        <a href="../debug_env.php" class="test-button" target="_blank">Environment</a>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-card-header">
                        <h3>⚙️ Configuration Check</h3>
                    </div>
                    <div class="test-card-body">
                        <p>Verify system configuration and connectivity:</p>
                        <a href="../admin/config_check.php" class="test-button" target="_blank">Config Check</a>
                        <a href="../security_test.php" class="test-button warning" target="_blank">Security Test</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        function clearAllSessions() {
            if (confirm('Clear all sessions and logout? This will end all active user sessions.')) {
                fetch('../logout.php')
                    .then(() => {
                        alert('All sessions cleared successfully');
                        location.reload();
                    });
            }
        }
        
        function testSessionManagement() {
            // Open multiple windows to test concurrent sessions
            window.open('../dashboard.php', '_blank');
            window.open('../dashboard.php', '_blank');
            alert('Opened multiple dashboard windows. Test concurrent session behavior.');
        }
    </script>
</body>
</html>
