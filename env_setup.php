<?php
// Quick environment variable checker and setter for Azure
echo "<!DOCTYPE html>";
echo "<html><head><title>Environment Variables Setup</title>";
echo "<style>body{font-family:monospace;background:#1e1e1e;color:#fff;padding:20px;}</style>";
echo "</head><body>";
echo "<h1>🔧 Environment Variables for Azure Application Settings</h1>";

echo "<div style='background:#2d2d2d;padding:15px;margin:20px 0;border-radius:5px;'>";
echo "<h2>📋 Copy These to Azure Application Settings:</h2>";
echo "<pre style='background:#000;color:#0f0;padding:15px;overflow-x:auto;'>";

// Read from .env file to show what should be in Azure
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    
    echo "# Add these in Azure Portal → Configuration → Application Settings\n\n";
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && !str_starts_with($line, '#')) {
            // Parse the line
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, "'\"");
                
                // Don't show placeholder values
                if (!str_contains($value, 'your-') && !empty($value)) {
                    echo "$key=$value\n";
                } else {
                    echo "$key=*** REPLACE WITH ACTUAL VALUE ***\n";
                }
            }
        }
    }
} else {
    echo "# .env file not found\n";
    echo "# Use these template values:\n\n";
    echo "EXTERNAL_CLIENT_ID=2d24e26e-99ee-4232-8921-06b161b65bb5\n";
    echo "EXTERNAL_CLIENT_SECRET=*** GET FROM AZURE PORTAL ***\n";
    echo "EXTERNAL_TENANT_ID=37a2c2da-5eec-4680-b380-2c0a72013f67\n";
    echo "INTERNAL_CLIENT_ID=756223c3-0313-4195-8540-b03063366f3a\n";
    echo "INTERNAL_CLIENT_SECRET=*** GET FROM AZURE PORTAL ***\n";
    echo "INTERNAL_TENANT_ID=48a85b75-4e7d-4d8c-9cc6-a72722124be8\n";
    echo "GRAPH_CLIENT_ID=ad222b8d-5eb1-40b4-9f6b-34d69a445f74\n";
    echo "GRAPH_CLIENT_SECRET=*** GET FROM AZURE PORTAL ***\n";
    echo "AWS_ACCESS_KEY_ID=*** FROM AWS SETUP ***\n";
    echo "AWS_SECRET_ACCESS_KEY=*** FROM AWS SETUP ***\n";
    echo "REDIRECT_URI=https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/callback.php\n";
    echo "DEBUG=true\n";
}

echo "</pre>";
echo "</div>";

echo "<div style='background:#1976d2;color:white;padding:15px;border-radius:5px;margin:20px 0;'>";
echo "<h3>📝 Instructions:</h3>";
echo "1. Go to <strong>Azure Portal</strong> → Your Web App → <strong>Configuration</strong><br>";
echo "2. Click <strong>+ New application setting</strong> for each variable above<br>";
echo "3. Copy the <strong>Name</strong> and <strong>Value</strong> exactly<br>";
echo "4. Click <strong>Save</strong> when done<br>";
echo "5. Web app will restart automatically<br>";
echo "6. Test your application again<br>";
echo "</div>";

echo "<div style='background:#d32f2f;color:white;padding:15px;border-radius:5px;margin:20px 0;'>";
echo "<h3>🔐 Important Notes:</h3>";
echo "• Replace <strong>*** REPLACE WITH ACTUAL VALUE ***</strong> with real secrets from Azure Portal<br>";
echo "• Client secrets are found in Azure AD App Registrations → Certificates & secrets<br>";
echo "• AWS credentials are from your AWS setup (Account ID: 955654668431)<br>";
echo "• Keep all secrets secure and never commit them to source code<br>";
echo "</div>";

echo "<p><a href='debug_azure.php' style='color:#64b5f6;'>← Back to Debug Console</a></p>";
echo "</body></html>";
?>
