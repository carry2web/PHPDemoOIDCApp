<?php
require_once 'vendor/autoload.php';

echo "<h1>Environment Debug</h1>";

// Test manual .env parsing like in config_helper.php
echo "<h2>Manual .env Parsing Test</h2>";
$env = [];
if (file_exists('.env')) {
    echo "✅ .env file exists<br>";
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "Lines read: " . count($lines) . "<br><br>";
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1], "'\"");
            $env[$key] = $value;
            echo "$key = '$value'<br>";
        }
    }
} else {
    echo "❌ .env file not found<br>";
}

echo "<h2>Specific Variables Test</h2>";
echo "EXTERNAL_TENANT_ID: " . ($env['EXTERNAL_TENANT_ID'] ?? 'NOT SET') . "<br>";
echo "EXTERNAL_CLIENT_ID: " . ($env['EXTERNAL_CLIENT_ID'] ?? 'NOT SET') . "<br>";
echo "EXTERNAL_CLIENT_SECRET: " . (isset($env['EXTERNAL_CLIENT_SECRET']) ? 'SET (length: ' . strlen($env['EXTERNAL_CLIENT_SECRET']) . ')' : 'NOT SET') . "<br>";
echo "B2C_TENANT_NAME: " . ($env['B2C_TENANT_NAME'] ?? 'NOT SET') . "<br>";
echo "B2C_POLICY_SIGNUP_SIGNIN: " . ($env['B2C_POLICY_SIGNUP_SIGNIN'] ?? 'NOT SET') . "<br>";

// Test B2C URL construction
echo "<h2>B2C URL Construction Test</h2>";
if (isset($env['B2C_TENANT_NAME']) && isset($env['B2C_POLICY_SIGNUP_SIGNIN'])) {
    $tenantName = $env['B2C_TENANT_NAME'];
    $policy = $env['B2C_POLICY_SIGNUP_SIGNIN'];
    
    $b2cUrl = "https://$tenantName.b2clogin.com/$tenantName.onmicrosoft.com/$policy/v2.0/.well-known/openid_configuration";
    echo "B2C Well-known URL: <a href='$b2cUrl' target='_blank'>$b2cUrl</a><br>";
    
    // Test if reachable
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $b2cUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Response Code: $httpCode<br>";
    if ($httpCode == 200) {
        echo "✅ B2C endpoint is reachable!<br>";
    } else {
        echo "❌ B2C endpoint returned error<br>";
    }
}
?>
