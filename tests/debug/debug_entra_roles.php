<?php
/**
 * Debug Entra ID Roles and Claims
 * Shows what roles and claims are available from Entra ID authentication
 */

require_once 'lib/oidc.php';
require_once 'lib/config_helper.php';

start_azure_safe_session();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Entra ID Roles</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .claims-section {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .role-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Entra ID Roles & Claims Debug</h1>
        
        <?php if (isset($_SESSION['email'])): ?>
            <div class="role-info">
                <h3>🎭 Current User Information</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['name'] ?? 'Unknown'); ?></p>
                <p><strong>User Type:</strong> <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Unknown'); ?></p>
                <p><strong>Determined Role:</strong> <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Unknown'); ?></p>
                <p><strong>Entra User Type:</strong> <?php echo htmlspecialchars($_SESSION['entra_user_type'] ?? 'Unknown'); ?></p>
            </div>
            
            <div class="claims-section">
                <h3>📋 All Session Variables</h3>
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
            
            <?php if (isset($_SESSION['claims'])): ?>
                <?php $claims = json_decode($_SESSION['claims'], true); ?>
                <div class="claims-section">
                    <h3>🔐 Raw Entra ID Claims</h3>
                    <pre><?php print_r($claims); ?></pre>
                </div>
                
                <div class="claims-section">
                    <h3>🎯 Role-Related Claims Analysis</h3>
                    
                    <?php
                    $roleClaimNames = ['roles', 'groups', 'extension_roles', 'wids'];
                    $foundRoles = false;
                    
                    foreach ($roleClaimNames as $claimName) {
                        if (isset($claims[$claimName])) {
                            $foundRoles = true;
                            echo "<h4>📌 Claim: '$claimName'</h4>";
                            echo "<pre>" . print_r($claims[$claimName], true) . "</pre>";
                        }
                    }
                    
                    if (!$foundRoles) {
                        echo "<div class='warning'>";
                        echo "<h4>⚠️ No Role Claims Found</h4>";
                        echo "<p>The current user doesn't have any of the standard role claims:</p>";
                        echo "<ul>";
                        foreach ($roleClaimNames as $claimName) {
                            echo "<li><code>$claimName</code></li>";
                        }
                        echo "</ul>";
                        echo "<p><strong>Possible reasons:</strong></p>";
                        echo "<ul>";
                        echo "<li>Roles are not configured in Entra ID application registration</li>";
                        echo "<li>User is not assigned to any application roles</li>";
                        echo "<li>Role claims are not included in the token</li>";
                        echo "<li>Custom claim mappings need to be configured</li>";
                        echo "</ul>";
                        echo "</div>";
                    }
                    ?>
                </div>
                
                <?php if (isset($_SESSION['entra_roles'])): ?>
                    <div class="claims-section">
                        <h3>🏷️ Processed Entra Roles</h3>
                        <pre><?php print_r($_SESSION['entra_roles']); ?></pre>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <div class="claims-section">
                <h3>🔧 Entra ID Configuration Guide</h3>
                <p>To properly configure roles in Entra ID:</p>
                <ol>
                    <li><strong>App Registration:</strong> Go to your app registration in Entra ID</li>
                    <li><strong>App Roles:</strong> Add application roles in the "App roles" section</li>
                    <li><strong>Assign Users:</strong> Assign users to roles in "Enterprise applications"</li>
                    <li><strong>Token Configuration:</strong> Ensure roles are included in tokens</li>
                    <li><strong>Scopes:</strong> Make sure your app requests appropriate scopes</li>
                </ol>
                
                <h4>Example App Roles to Create:</h4>
                <ul>
                    <li><strong>Travel Admin</strong> - Full administrative access</li>
                    <li><strong>Travel Agent</strong> - Agent permissions for customer management</li>
                    <li><strong>Customer</strong> - Basic customer access (if needed)</li>
                </ul>
            </div>
            
        <?php else: ?>
            <div class="warning">
                <h3>⚠️ Not Authenticated</h3>
                <p>You need to authenticate first to see your Entra ID roles and claims.</p>
                <a href="index.php?user_type=customer" class="btn">Login as Customer</a>
                <a href="index.php?user_type=agent" class="btn">Login as Agent</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="dashboard.php" class="btn">← Back to Dashboard</a>
            <a href="logout.php" class="btn" style="background: #dc3545;">Logout</a>
            <a href="javascript:location.reload()" class="btn" style="background: #28a745;">🔄 Refresh</a>
        </div>
    </div>
</body>
</html>
