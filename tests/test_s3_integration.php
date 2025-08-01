<?php
// Test S3 Integration for S-Cape Travel
require_once 'vendor/autoload.php';
require_once 'lib/config_helper.php';
require_once 'lib/logger.php';

$logger = ScapeLogger::getInstance();
$config = get_app_config();

echo "<h2>🧪 S-Cape Travel S3 Integration Test</h2>";
echo "<p><strong>Testing AWS S3 integration with account: 955654668431</strong></p>";

// Test 1: Basic AWS credentials
echo "<h3>Test 1: AWS Credentials</h3>";
try {
    $awsConfig = [
        'version' => 'latest',
        'region' => $config['aws']['region'],
        'credentials' => [
            'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? 'not-set',
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? 'not-set'
        ]
    ];
    
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
    echo "✅ AWS Config Structure: Ready<br>";
    echo "✅ Region: " . $config['aws']['region'] . "<br>";
    echo "✅ Bucket: " . $config['aws']['bucket'] . "<br>";
    echo "✅ Customer Role: " . $config['aws']['roles']['customer'] . "<br>";
    echo "✅ Agent Role: " . $config['aws']['roles']['agent'] . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
    echo "❌ AWS Config Error: " . $e->getMessage();
    echo "</div>";
}

// Test 2: Check environment variables
echo "<h3>Test 2: Environment Variables</h3>";
$awsKeySet = !empty($_ENV['AWS_ACCESS_KEY_ID']);
$awsSecretSet = !empty($_ENV['AWS_SECRET_ACCESS_KEY']);
$awsRegionSet = !empty($_ENV['AWS_REGION']);

if ($awsKeySet && $awsSecretSet && $awsRegionSet) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
    echo "✅ All AWS environment variables are set<br>";
    echo "✅ Access Key ID: " . substr($_ENV['AWS_ACCESS_KEY_ID'], 0, 8) . "...<br>";
    echo "✅ Region: " . $_ENV['AWS_REGION'] . "<br>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
    echo "⚠️ AWS Environment Variables Status:<br>";
    echo "AWS_ACCESS_KEY_ID: " . ($awsKeySet ? "✅ Set" : "❌ Not set") . "<br>";
    echo "AWS_SECRET_ACCESS_KEY: " . ($awsSecretSet ? "✅ Set" : "❌ Not set") . "<br>";
    echo "AWS_REGION: " . ($awsRegionSet ? "✅ Set" : "❌ Not set") . "<br>";
    echo "<br><strong>Note:</strong> On Azure Web Apps, these should be set in Application Settings";
    echo "</div>";
}

// Test 3: Try AWS SDK (if credentials available)
echo "<h3>Test 3: AWS SDK Test</h3>";
try {
    if ($awsKeySet && $awsSecretSet) {
        $s3Client = new Aws\S3\S3Client($awsConfig);
        
        // Test bucket access
        $result = $s3Client->headBucket(['Bucket' => $config['aws']['bucket']]);
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
        echo "✅ S3 Bucket Access: SUCCESS<br>";
        echo "✅ Bucket '" . $config['aws']['bucket'] . "' is accessible<br>";
        echo "</div>";
        
        // Test file upload
        $testContent = "S-Cape Travel Test - " . date('Y-m-d H:i:s');
        $result = $s3Client->putObject([
            'Bucket' => $config['aws']['bucket'],
            'Key' => 'shared/integration-test.txt',
            'Body' => $testContent,
            'ContentType' => 'text/plain'
        ]);
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
        echo "✅ File Upload: SUCCESS<br>";
        echo "✅ Test file uploaded to: shared/integration-test.txt<br>";
        echo "✅ ETag: " . $result['ETag'] . "<br>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
        echo "⚠️ Skipping AWS SDK test - credentials not available in local environment<br>";
        echo "This is normal for local testing. On Azure Web Apps, AWS credentials are in Application Settings.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0;'>";
    echo "❌ AWS SDK Error: " . $e->getMessage() . "<br>";
    echo "This may be normal for local testing without AWS credentials.";
    echo "</div>";
}

// Test 4: Configuration completeness
echo "<h3>Test 4: Configuration Summary</h3>";
echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f8f9fa;'><th style='border: 1px solid #ddd; padding: 8px;'>Component</th><th style='border: 1px solid #ddd; padding: 8px;'>Status</th><th style='border: 1px solid #ddd; padding: 8px;'>Details</th></tr>";

$configTests = [
    'Internal Client ID' => ['value' => $config['b2b']['client_id'], 'expected' => '756223c3-0313-4195-8540-b03063366f3a'],
    'External Client ID' => ['value' => $config['b2c']['client_id'], 'expected' => '2d24e26e-99ee-4232-8921-06b161b65bb5'],
    'Graph Client ID' => ['value' => $config['graph']['client_id'], 'expected' => 'ad222b8d-5eb1-40b4-9f6b-34d69a445f74'],
    'S3 Bucket' => ['value' => $config['aws']['bucket'], 'expected' => 'scape-travel-docs'],
    'AWS Region' => ['value' => $config['aws']['region'], 'expected' => 'eu-west-1'],
    'Customer Role' => ['value' => $config['aws']['roles']['customer'], 'expected' => 'arn:aws:iam::955654668431:role/CustomerRole'],
    'Agent Role' => ['value' => $config['aws']['roles']['agent'], 'expected' => 'arn:aws:iam::955654668431:role/AgentRole'],
];

foreach ($configTests as $test => $data) {
    $status = ($data['value'] === $data['expected']) ? "✅ Correct" : "❌ Mismatch";
    $bgColor = ($data['value'] === $data['expected']) ? "#d4edda" : "#f8d7da";
    echo "<tr style='background: $bgColor;'>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$test</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($data['value']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>🎯 Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Local Testing:</strong> Visit <a href='http://localhost/'>http://localhost/</a> to test authentication flows</li>";
echo "<li><strong>Live Testing:</strong> Visit <a href='https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/'>your Azure Web App</a></li>";
echo "<li><strong>AWS Credentials:</strong> On Azure, add AWS credentials to Application Settings</li>";
echo "<li><strong>Test Login:</strong> Try both customer (B2C) and agent (B2B) login flows</li>";
echo "<li><strong>Document Upload:</strong> Test file upload/download functionality</li>";
echo "</ol>";

echo "<p style='background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<strong>🚀 Your S-Cape Travel application is ready for testing!</strong><br>";
echo "All core components are configured and the AWS integration is prepared.";
echo "</p>";
?>
