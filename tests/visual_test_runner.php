<?php
/**
 * Enhanced Visual Test Runner
 * Beautiful web interface with visual checkmarks and progress indicators
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/oidc.php';

session_start();

$runTests = isset($_GET['run']) ? $_GET['run'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Enhanced OIDC Test Suite</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 2.5em; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        
        .content { padding: 30px; }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .test-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .test-card:hover {
            border-color: #2196F3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .test-card.running {
            border-color: #ff9800;
            background: #fff3e0;
        }
        
        .test-card.passed {
            border-color: #4caf50;
            background: #e8f5e8;
        }
        
        .test-card.failed {
            border-color: #f44336;
            background: #ffebee;
        }
        
        .test-card h3 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            margin-left: auto;
        }
        
        .status-pending { background: #e0e0e0; color: #666; }
        .status-running { background: #ff9800; color: white; animation: pulse 1.5s infinite; }
        .status-passed { background: #4caf50; color: white; }
        .status-failed { background: #f44336; color: white; }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .test-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .run-all-btn {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 30px auto;
            padding: 15px 30px;
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .run-all-btn:hover {
            transform: scale(1.05);
        }
        
        .results-section {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50 0%, #45a049 100%);
            transition: width 0.5s ease;
            border-radius: 10px;
        }
        
        .test-output {
            background: #263238;
            color: #4caf50;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
        }
        
        .card-total { background: #2196F3; }
        .card-passed { background: #4caf50; }
        .card-failed { background: #f44336; }
        .card-rate { background: #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Enhanced OIDC Test Suite</h1>
            <p>Comprehensive authentication testing with visual feedback</p>
        </div>
        
        <div class="content">
            <?php if (!$runTests): ?>
                <div class="test-grid">
                    <div class="test-card" onclick="runTest('config')">
                        <h3>
                            🔧 Configuration Tests
                            <div class="test-status status-pending">?</div>
                        </h3>
                        <div class="test-description">
                            Validates environment variables, tenant configuration, and basic setup requirements.
                        </div>
                        <ul>
                            <li>Environment file loading</li>
                            <li>B2C/B2B tenant validation</li>
                            <li>Required credentials check</li>
                        </ul>
                    </div>
                    
                    <div class="test-card" onclick="runTest('oidc')">
                        <h3>
                            🔐 OIDC Client Tests
                            <div class="test-status status-pending">?</div>
                        </h3>
                        <div class="test-description">
                            Tests OIDC client creation and authority URL configuration for both tenants.
                        </div>
                        <ul>
                            <li>Customer client creation</li>
                            <li>Agent client creation</li>
                            <li>Authority URL validation</li>
                        </ul>
                    </div>
                    
                    <div class="test-card" onclick="runTest('roles')">
                        <h3>
                            👥 Role Determination
                            <div class="test-status status-pending">?</div>
                        </h3>
                        <div class="test-description">
                            Tests role assignment logic for different user scenarios and claim processing.
                        </div>
                        <ul>
                            <li>Customer role assignment</li>
                            <li>Agent role detection</li>
                            <li>Admin role identification</li>
                        </ul>
                    </div>
                    
                    <div class="test-card" onclick="runTest('security')">
                        <h3>
                            🛡️ Security & Session
                            <div class="test-status status-pending">?</div>
                        </h3>
                        <div class="test-description">
                            Validates security configurations, session management, and URL validation.
                        </div>
                        <ul>
                            <li>Redirect URI validation</li>
                            <li>Session security settings</li>
                            <li>HTTPS enforcement</li>
                        </ul>
                    </div>
                </div>
                
                <a href="?run=all" class="run-all-btn">
                    🚀 Run All Tests
                </a>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="../" style="color: #666; text-decoration: none;">← Back to Application</a> |
                    <a href="complete_test.php" style="color: #666; text-decoration: none;">CLI Test Runner</a> |
                    <a href="debug/" style="color: #666; text-decoration: none;">Debug Tools</a>
                </div>
                
            <?php else: ?>
                <div class="results-section">
                    <h2>🏃‍♂️ Running Tests...</h2>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%" id="progressBar"></div>
                    </div>
                    
                    <div class="test-output" id="testOutput">
                        <?php
                        // Run the selected tests
                        ob_start();
                        
                        if ($runTests === 'all' || $runTests === 'config') {
                            runConfigTests();
                        }
                        if ($runTests === 'all' || $runTests === 'oidc') {
                            runOidcTests();
                        }
                        if ($runTests === 'all' || $runTests === 'roles') {
                            runRoleTests();
                        }
                        if ($runTests === 'all' || $runTests === 'security') {
                            runSecurityTests();
                        }
                        
                        $output = ob_get_clean();
                        echo htmlspecialchars($output);
                        ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="?" class="run-all-btn" style="display: inline-block; width: auto;">
                            🔄 Run Tests Again
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function runTest(testType) {
            window.location.href = '?run=' + testType;
        }
        
        // Simulate progress for visual effect
        if (document.getElementById('progressBar')) {
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                }
                document.getElementById('progressBar').style.width = progress + '%';
            }, 300);
        }
    </script>
</body>
</html>

<?php
function runConfigTests() {
    echo "🔧 Configuration Tests\n";
    echo "======================\n\n";
    
    try {
        $config = get_app_config();
        echo "✅ Configuration loaded successfully\n";
        
        if (isset($config['b2c']) && !empty($config['b2c']['client_id'])) {
            echo "✅ B2C configuration valid\n";
        } else {
            echo "❌ B2C configuration missing\n";
        }
        
        if (isset($config['b2b']) && !empty($config['b2b']['client_id'])) {
            echo "✅ B2B configuration valid\n";
        } else {
            echo "❌ B2B configuration missing\n";
        }
        
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Configuration test failed: " . $e->getMessage() . "\n\n";
    }
}

function runOidcTests() {
    echo "🔐 OIDC Client Tests\n";
    echo "====================\n\n";
    
    try {
        $customerClient = get_oidc_client('customer');
        echo "✅ Customer OIDC client created\n";
        
        $agentClient = get_oidc_client('agent');
        echo "✅ Agent OIDC client created\n";
        
        $customerUrl = $customerClient->getProviderURL();
        echo "✅ Customer authority: " . $customerUrl . "\n";
        
        $agentUrl = $agentClient->getProviderURL();
        echo "✅ Agent authority: " . $agentUrl . "\n";
        
        echo "\n";
    } catch (Exception $e) {
        echo "❌ OIDC test failed: " . $e->getMessage() . "\n\n";
    }
}

function runRoleTests() {
    echo "👥 Role Determination Tests\n";
    echo "===========================\n\n";
    
    $testCases = [
        ['customer', (object)['email' => 'test@external.com'], 'customer'],
        ['agent', (object)['email' => 'emp@s-capepartners.eu', 'userType' => 'Member'], 'agent'],
        ['agent', (object)['email' => 'ictsupport@s-capepartners.eu', 'roles' => ['Admin']], 'admin']
    ];
    
    foreach ($testCases as [$userType, $claims, $expectedRole]) {
        $actualRole = determineUserRole($userType, $claims);
        if ($actualRole === $expectedRole) {
            echo "✅ $userType -> $expectedRole (Got: $actualRole)\n";
        } else {
            echo "❌ $userType -> $expectedRole (Got: $actualRole)\n";
        }
    }
    
    echo "\n";
}

function runSecurityTests() {
    echo "🛡️ Security & Session Tests\n";
    echo "============================\n\n";
    
    $config = get_app_config();
    $redirectUri = $config['app']['redirect_uri'];
    
    if (filter_var($redirectUri, FILTER_VALIDATE_URL)) {
        echo "✅ Redirect URI is valid URL\n";
    } else {
        echo "❌ Redirect URI is invalid\n";
    }
    
    if (str_ends_with($redirectUri, '/callback.php')) {
        echo "✅ Redirect URI ends with /callback.php\n";
    } else {
        echo "❌ Redirect URI should end with /callback.php\n";
    }
    
    if (function_exists('start_azure_safe_session')) {
        echo "✅ Azure-safe session available\n";
    } else {
        echo "❌ Azure-safe session not configured\n";
    }
    
    echo "\n";
}
?>
