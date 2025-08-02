<?php
// File: tests/test_enhanced_logout.php
// Enhanced logout testing with Microsoft Graph API integration

require_once __DIR__ . '/../lib/oidc.php';
require_once __DIR__ . '/../lib/logger.php';

$logger = ScapeLogger::getInstance();
start_azure_safe_session();

$isLoggedIn = !empty($_SESSION['email']);
$userType = $_SESSION['user_type'] ?? 'unknown';
$email = $_SESSION['email'] ?? 'Not logged in';
$hasAccessToken = !empty($_SESSION['access_token']);
$userOid = $_SESSION['userinfo']['oid'] ?? $_SESSION['userinfo']['sub'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Logout Testing</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .test-section {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #007cba;
        }
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 10px 0;
        }
        .status-item {
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .logout-button {
            display: inline-block;
            margin: 10px;
            padding: 12px 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .logout-button:hover {
            background: #c82333;
        }
        .logout-button.enhanced {
            background: #6f42c1;
        }
        .logout-button.enhanced:hover {
            background: #5a359a;
        }
        .logout-button.complete {
            background: #fd7e14;
        }
        .logout-button.complete:hover {
            background: #e66100;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚪 Enhanced Logout Testing</h1>
        <p>Test different logout methods including Microsoft Graph API integration</p>

        <div class="test-section">
            <h2>📊 Current Session Status</h2>
            <div class="status-grid">
                <div class="status-item">
                    <strong>Logged In:</strong> <?php echo $isLoggedIn ? '✅ Yes' : '❌ No'; ?>
                </div>
                <div class="status-item">
                    <strong>User Type:</strong> <?php echo htmlspecialchars($userType); ?>
                </div>
                <div class="status-item">
                    <strong>Email:</strong> <?php echo htmlspecialchars($email); ?>
                </div>
                <div class="status-item">
                    <strong>Access Token:</strong> <?php echo $hasAccessToken ? '✅ Available' : '❌ Not available'; ?>
                </div>
                <div class="status-item">
                    <strong>User OID:</strong> <?php echo $userOid ? '✅ Available' : '❌ Not available'; ?>
                </div>
                <div class="status-item">
                    <strong>Session ID:</strong> <?php echo session_id(); ?>
                </div>
            </div>
        </div>

        <?php if ($isLoggedIn): ?>
            <div class="test-section">
                <h2>🔓 Logout Options</h2>
                <p>Choose the type of logout to test:</p>

                <a href="../logout.php" class="logout-button">
                    🏠 Standard Local Logout
                </a>
                <div class="code-block">
                    Clears local session and cookies only<br>
                    URL: logout.php
                </div>

                <?php if ($hasAccessToken && $userOid): ?>
                    <a href="../logout.php?graph_logout=1" class="logout-button enhanced">
                        🔐 Graph API Logout
                    </a>
                    <div class="code-block">
                        Local logout + Microsoft Graph API token revocation<br>
                        URL: logout.php?graph_logout=1<br>
                        Calls: revokeSignInSessions + invalidateAllRefreshTokens
                    </div>

                    <a href="../logout.php?complete_logout=1" class="logout-button complete">
                        🌐 Complete Enhanced Logout
                    </a>
                    <div class="code-block">
                        Full logout with Graph API + optional Azure logout<br>
                        URL: logout.php?complete_logout=1<br>
                        Includes all logout methods
                    </div>
                <?php else: ?>
                    <div class="error-message">
                        ⚠️ Graph API logout not available: 
                        <?php if (!$hasAccessToken): ?>Missing access token. <?php endif; ?>
                        <?php if (!$userOid): ?>Missing user OID. <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="../logout.php?azure_logout=1" class="logout-button">
                    🌐 Azure Identity Platform Logout
                </a>
                <div class="code-block">
                    Redirects to Microsoft logout endpoint<br>
                    URL: logout.php?azure_logout=1<br>
                    Performs single sign-out from Microsoft
                </div>
            </div>

            <div class="test-section">
                <h2>🔍 Graph API Requirements</h2>
                <p>For Graph API logout to work, the application needs:</p>
                <ul>
                    <li>✅ Valid access token (<?php echo $hasAccessToken ? 'Available' : 'Missing'; ?>)</li>
                    <li>✅ User object ID (<?php echo $userOid ? 'Available' : 'Missing'; ?>)</li>
                    <li>⚠️ User.RevokeSessions.All permission (Check app registration)</li>
                    <li>⚠️ Admin consent for the permission (Check tenant admin)</li>
                </ul>
                
                <h3>API Endpoints Used:</h3>
                <div class="code-block">
                    POST https://graph.microsoft.com/v1.0/users/{user-oid}/revokeSignInSessions<br>
                    POST https://graph.microsoft.com/v1.0/users/{user-oid}/invalidateAllRefreshTokens
                </div>
            </div>
        <?php else: ?>
            <div class="error-message">
                ❌ You are not currently logged in. <a href="../index.php">Go to login page</a>
            </div>
        <?php endif; ?>

        <div class="test-section">
            <h2>📝 Session Debug Information</h2>
            <div class="code-block">
                <?php
                echo "Session Data:\n";
                if (!empty($_SESSION)) {
                    foreach ($_SESSION as $key => $value) {
                        if ($key === 'access_token' && !empty($value)) {
                            echo "$key: " . substr($value, 0, 20) . "... (truncated)\n";
                        } elseif (is_array($value)) {
                            echo "$key: " . json_encode($value, JSON_PRETTY_PRINT) . "\n";
                        } else {
                            echo "$key: " . htmlspecialchars($value) . "\n";
                        }
                    }
                } else {
                    echo "No session data available\n";
                }
                ?>
            </div>
        </div>

        <div class="test-section">
            <h2>🔧 Testing Tools</h2>
            <a href="session_cleanup.php" class="logout-button">🧹 Force Session Cleanup</a>
            <a href="test_logout.php" class="logout-button">🧪 Interactive Logout Test</a>
            <a href="../index.php" class="logout-button" style="background: #28a745;">🏠 Back to Home</a>
        </div>
    </div>
</body>
</html>
