<?php
// File: admin/cross_tenant_check.php
// Cross-tenant configuration diagnostic tool

require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/logger.php';

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, "'\"");
        $_ENV[$name] = $value;
    }
}

$logger = ScapeLogger::getInstance();
$config = get_app_config();

function testGraphAPIToken() {
    global $config, $logger;
    
    echo "<h3>🔑 Testing Graph API Authentication</h3>";
    
    $tokenUrl = 'https://login.microsoftonline.com/' . $config['graph']['tenant_id'] . '/oauth2/v2.0/token';
    
    $postData = [
        'client_id' => $config['graph']['client_id'],
        'client_secret' => $config['graph']['client_secret'],
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        echo "<div class='success'>✅ Graph API token obtained successfully</div>";
        echo "<p><strong>Scope:</strong> " . htmlspecialchars($tokenData['scope']) . "</p>";
        return $tokenData['access_token'];
    } else {
        echo "<div class='error'>❌ Failed to get Graph API token</div>";
        echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
        echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
        return false;
    }
}

function testGraphPermissions($token) {
    echo "<h3>🔐 Testing Graph API Permissions</h3>";
    
    $tests = [
        'Application.ReadWrite.All' => 'https://graph.microsoft.com/v1.0/applications?$top=1',
        'User.ReadWrite.All' => 'https://graph.microsoft.com/v1.0/users?$top=1',
        'Directory.ReadWrite.All' => 'https://graph.microsoft.com/v1.0/directoryObjects?$top=1'
    ];
    
    foreach ($tests as $permission => $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "<div class='success'>✅ $permission permission working</div>";
        } else {
            echo "<div class='error'>❌ $permission permission failed (HTTP: $httpCode)</div>";
        }
    }
}

function checkConfigurationCompleteness() {
    global $config;
    
    echo "<h3>📋 Configuration Completeness Check</h3>";
    
    $checks = [
        'Internal Client ID' => !empty($config['b2b']['client_id']),
        'Internal Client Secret' => !empty($config['b2b']['client_secret']),
        'External Client ID' => !empty($config['b2c']['client_id']),
        'External Client Secret' => !empty($config['b2c']['client_secret']),
        'Graph Client ID' => !empty($config['graph']['client_id']),
        'Graph Client Secret' => !empty($config['graph']['client_secret']),
        'Admin Email' => !empty($config['app']['admin_email']),
        'AWS S3 Bucket' => !empty($config['aws']['bucket']),
        'AWS Customer Role' => !empty($config['aws']['roles']['customer']) && strpos($config['aws']['roles']['customer'], 'ACCOUNT') === false,
        'AWS Agent Role' => !empty($config['aws']['roles']['agent']) && strpos($config['aws']['roles']['agent'], 'ACCOUNT') === false,
    ];
    
    foreach ($checks as $item => $status) {
        if ($status) {
            echo "<div class='success'>✅ $item: Configured</div>";
        } else {
            echo "<div class='error'>❌ $item: Not configured</div>";
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cross-Tenant Configuration Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin: 5px 0; }
        h1 { color: #333; border-bottom: 2px solid #007acc; padding-bottom: 10px; }
        h3 { color: #555; margin-top: 30px; }
        .tenant-info { background: #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .checklist { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 S-Cape Travel Cross-Tenant Configuration Check</h1>
        
        <div class="tenant-info">
            <h3>Tenant Information</h3>
            <p><strong>Internal Tenant (B2B):</strong> <?= htmlspecialchars($config['b2b']['tenant_id']) ?></p>
            <p><strong>External Tenant (B2C):</strong> <?= htmlspecialchars($config['b2c']['tenant_id']) ?></p>
            <p><strong>Graph API Tenant:</strong> <?= htmlspecialchars($config['graph']['tenant_id']) ?></p>
        </div>
        
        <?php
        checkConfigurationCompleteness();
        
        $token = testGraphAPIToken();
        if ($token) {
            testGraphPermissions($token);
        }
        ?>
        
        <h3>📝 Manual Azure Portal Checks Required</h3>
        <div class="checklist">
            <h4>1. Graph API Permissions Check:</h4>
            <ol>
                <li>Go to <strong>Azure Portal</strong> → <strong>App registrations</strong></li>
                <li>Find your Graph API app: <code><?= htmlspecialchars($config['graph']['client_id']) ?></code></li>
                <li>Click <strong>API permissions</strong></li>
                <li>Verify these permissions exist with <strong>Admin consent granted</strong>:</li>
                <ul>
                    <li>✅ <strong>Application.ReadWrite.All</strong> (Application)</li>
                    <li>✅ <strong>Directory.ReadWrite.All</strong> (Application)</li>
                    <li>✅ <strong>User.ReadWrite.All</strong> (Application)</li>
                    <li>✅ <strong>Mail.Send</strong> (Application) - <em>Add this for email functionality</em></li>
                </ul>
            </ol>
        </div>
        
        <div class="checklist">
            <h4>2. Admin Role Configuration:</h4>
            <ol>
                <li>Go to your <strong>Internal tenant</strong> app registration: <code><?= htmlspecialchars($config['b2b']['client_id']) ?></code></li>
                <li>Click <strong>App roles</strong></li>
                <li>Verify <strong>"Admin"</strong> role exists</li>
                <li>Go to <strong>Enterprise applications</strong> → Find your app → <strong>Users and groups</strong></li>
                <li>Assign admin users to the <strong>"Admin"</strong> role</li>
            </ol>
        </div>
        
        <div class="checklist">
            <h4>3. B2B Collaboration Settings:</h4>
            <ol>
                <li>In your <strong>Internal tenant</strong> → <strong>External Identities</strong></li>
                <li><strong>External collaboration settings</strong></li>
                <li>Verify <strong>Guest invite settings</strong> allow invitations</li>
                <li>Check <strong>Collaboration restrictions</strong> for domain allowlists</li>
            </ol>
        </div>
        
        <div class="checklist">
            <h4>4. Email Setup:</h4>
            <ol>
                <li>Verify <code><?= htmlspecialchars($config['app']['admin_email']) ?></code> mailbox exists</li>
                <li>Add <strong>Mail.Send</strong> permission to Graph API app (see step 1)</li>
                <li>Grant admin consent for the Mail.Send permission</li>
            </ol>
        </div>
        
        <h3>🔄 Next Steps</h3>
        <div class="warning">
            <h4>Immediate Actions:</h4>
            <ol>
                <li>Fix any ❌ failed configuration items above</li>
                <li>Add <strong>Mail.Send</strong> permission to Graph API if not present</li>
                <li>Configure AWS Account ID to replace 'ACCOUNT' in role ARNs</li>
                <li>Test authentication flows: <a href="../index.php">Test Customer Login</a> | <a href="../index.php">Test Agent Login</a></li>
                <li>Test admin panel: <a href="agents.php">Admin Panel</a></li>
            </ol>
        </div>
        
        <div class="success">
            <h4>✅ When All Green:</h4>
            <p>Your cross-tenant configuration is ready for production! Deploy to Azure Web Apps and monitor the enhanced logging system.</p>
        </div>
    </div>
</body>
</html>
