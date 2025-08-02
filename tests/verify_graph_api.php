<?php
// File: tests/verify_graph_api.php
// Test Microsoft Graph API connectivity and permissions

require_once __DIR__ . '/../lib/oidc.php';
require_once __DIR__ . '/../lib/logger.php';

$logger = ScapeLogger::getInstance();
start_azure_safe_session();

$isLoggedIn = !empty($_SESSION['email']);
$access_token = $_SESSION['access_token'] ?? null;
$user_oid = $_SESSION['userinfo']['oid'] ?? $_SESSION['userinfo']['sub'] ?? null;

/**
 * Test Graph API connectivity
 */
function testGraphApiConnectivity($access_token, $user_oid) {
    if (!$access_token || !$user_oid) {
        return ['success' => false, 'error' => 'Missing access token or user OID'];
    }
    
    // Test basic Graph API call (get user profile)
    $url = "https://graph.microsoft.com/v1.0/users/$user_oid";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $http_code === 200,
        'http_code' => $http_code,
        'response' => $response
    ];
}

/**
 * Test Graph API permissions for logout operations
 */
function testGraphApiPermissions($access_token, $user_oid) {
    if (!$access_token || !$user_oid) {
        return ['success' => false, 'error' => 'Missing access token or user OID'];
    }
    
    // Test permissions by trying to call revokeSignInSessions with dry-run approach
    // Note: This will actually revoke sessions if successful, so use with caution
    
    $url = "https://graph.microsoft.com/v1.0/users/$user_oid";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'http_code' => $http_code,
            'can_read_user' => false
        ];
    }
    
    // If we can read user, check token scope
    $user_data = json_decode($response, true);
    
    return [
        'success' => true,
        'http_code' => $http_code,
        'can_read_user' => true,
        'user_data' => $user_data,
        'note' => 'User read successful - logout permissions need testing with actual logout call'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graph API Verification</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .test-result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #ffc107; }
        .code-output {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Microsoft Graph API Verification</h1>
        <p>Test Graph API connectivity and permissions for enhanced logout</p>

        <?php if (!$isLoggedIn): ?>
            <div class="test-result error">
                <h3>❌ Not Logged In</h3>
                <p>You need to be logged in to test Graph API connectivity.</p>
                <a href="../index.php" class="button">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="test-result">
                <h3>📊 Current Session</h3>
                <ul>
                    <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></li>
                    <li><strong>User Type:</strong> <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'unknown'); ?></li>
                    <li><strong>Access Token:</strong> <?php echo $access_token ? '✅ Available' : '❌ Missing'; ?></li>
                    <li><strong>User OID:</strong> <?php echo $user_oid ?: '❌ Missing'; ?></li>
                </ul>
            </div>

            <?php if ($access_token && $user_oid): ?>
                <?php
                echo "<div class='test-result'>";
                echo "<h3>🌐 Graph API Connectivity Test</h3>";
                
                $connectivity_test = testGraphApiConnectivity($access_token, $user_oid);
                if ($connectivity_test['success']) {
                    echo "<p class='success'>✅ Graph API connectivity successful!</p>";
                    $user_data = json_decode($connectivity_test['response'], true);
                    echo "<p><strong>User Display Name:</strong> " . htmlspecialchars($user_data['displayName'] ?? 'N/A') . "</p>";
                    echo "<p><strong>User Principal Name:</strong> " . htmlspecialchars($user_data['userPrincipalName'] ?? 'N/A') . "</p>";
                } else {
                    echo "<p class='error'>❌ Graph API connectivity failed</p>";
                    echo "<p><strong>HTTP Code:</strong> " . $connectivity_test['http_code'] . "</p>";
                    
                    if ($connectivity_test['http_code'] == 401) {
                        echo "<p>🔐 Access token may be expired or invalid</p>";
                    } elseif ($connectivity_test['http_code'] == 403) {
                        echo "<p>🚫 Access denied - check permissions</p>";
                    }
                }
                echo "</div>";
                
                echo "<div class='test-result'>";
                echo "<h3>🔑 Permissions Test</h3>";
                
                $permissions_test = testGraphApiPermissions($access_token, $user_oid);
                if ($permissions_test['success'] && $permissions_test['can_read_user']) {
                    echo "<p class='success'>✅ Can read user profile via Graph API</p>";
                    echo "<p class='warning'>⚠️ Logout permissions (User.RevokeSessions.All) need actual logout test</p>";
                } else {
                    echo "<p class='error'>❌ Cannot read user profile</p>";
                    echo "<p>This suggests insufficient permissions for Graph API access</p>";
                }
                echo "</div>";
                
                echo "<div class='test-result'>";
                echo "<h3>🧪 Test Enhanced Logout</h3>";
                echo "<p>To test the actual Graph API logout functionality:</p>";
                echo "<a href='test_enhanced_logout.php' class='button'>🚪 Go to Enhanced Logout Test</a>";
                echo "<p class='warning'>⚠️ Note: This will actually log you out and revoke tokens</p>";
                echo "</div>";
                
                echo "<div class='test-result'>";
                echo "<h3>📋 Required Permissions</h3>";
                echo "<p>For Graph API enhanced logout to work, ensure these permissions are granted:</p>";
                echo "<ul>";
                echo "<li><strong>User.RevokeSessions.All</strong> - Required for revokeSignInSessions</li>";
                echo "<li><strong>User.ReadWrite.All</strong> - Alternative permission</li>";
                echo "<li><strong>Directory.ReadWrite.All</strong> - Higher level permission</li>";
                echo "</ul>";
                echo "<p>These permissions typically require admin consent.</p>";
                echo "</div>";
            ?>
            <?php else: ?>
                <div class="test-result error">
                    <h3>❌ Missing Required Data</h3>
                    <p>Cannot test Graph API without:</p>
                    <ul>
                        <?php if (!$access_token): ?><li>Access Token</li><?php endif; ?>
                        <?php if (!$user_oid): ?><li>User Object ID (OID)</li><?php endif; ?>
                    </ul>
                    <p>Try logging out and logging back in to refresh tokens.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="test-result">
            <h3>🔧 Testing Tools</h3>
            <a href="test_enhanced_logout.php" class="button">🚪 Enhanced Logout Test</a>
            <a href="session_cleanup.php" class="button">🧹 Session Cleanup</a>
            <a href="../dashboard.php" class="button">📊 Dashboard</a>
            <a href="../index.php" class="button">🏠 Home</a>
        </div>

        <div class="test-result">
            <h3>📖 Documentation</h3>
            <p>For detailed information about the enhanced logout system:</p>
            <a href="../ENHANCED_LOGOUT_GUIDE.md" class="button">📚 Read Enhanced Logout Guide</a>
        </div>
    </div>
</body>
</html>
