<?php
/**
 * Test Role-Based Document Access
 * Quick test to verify the new role-based system is working
 */

require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/document_manager.php';

start_azure_safe_session();

// Mock different user types for testing
$testUsers = [
    'customer' => [
        'email' => 'carry.megens+customer2@gmail.com',
        'name' => 'Carry Customer 2',
        'user_type' => 'customer',
        'user_role' => 'customer'
    ],
    'agent' => [
        'email' => 'agent@scape.com.au',
        'name' => 'Test Agent',
        'user_type' => 'agent',
        'user_role' => 'agent'
    ],
    'admin' => [
        'email' => 'carry.megens@scape.com.au',
        'name' => 'Carry Admin',
        'user_type' => 'agent',
        'user_role' => 'admin'
    ]
];

$currentUser = $_GET['test_as'] ?? 'customer';
$user = $testUsers[$currentUser] ?? $testUsers['customer'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Role-Based Access Test</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .test-switch {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .role-indicator {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .role-customer { background: #d1ecf1; color: #0c5460; }
        .role-agent { background: #d4edda; color: #155724; }
        .role-admin { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Role-Based Document Access Test</h1>
            
            <div class="test-switch">
                <h3>🎭 Test as different user types:</h3>
                <a href="?test_as=customer" style="margin-right: 10px; padding: 8px 15px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px;">👤 Customer</a>
                <a href="?test_as=agent" style="margin-right: 10px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">🏢 Agent</a>
                <a href="?test_as=admin" style="margin-right: 10px; padding: 8px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px;">⚡ Admin</a>
            </div>
            
            <div class="role-indicator role-<?php echo $user['user_role']; ?>">
                🔐 Currently testing as: <strong><?php echo $user['name']; ?></strong> 
                (<?php echo $user['email']; ?>) - Role: <?php echo ucfirst($user['user_role']); ?>
            </div>
        </div>
        
        <div class="card">
            <h2>📊 Access Permissions Test</h2>
            
            <?php
            if (DocumentManager::isConfigured()) {
                $docManager = new DocumentManager();
                $listResult = $docManager->listDocuments($user['user_type'], $user);
                
                if ($listResult['success']) {
                    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
                    echo "<h3>✅ Access Analysis</h3>";
                    echo "<ul>";
                    echo "<li><strong>User Role:</strong> " . ($listResult['user_role'] ?? 'unknown') . "</li>";
                    echo "<li><strong>Can Upload:</strong> " . ($listResult['can_upload'] ? '✅ Yes' : '❌ No') . "</li>";
                    echo "<li><strong>Accessible Folders:</strong> " . count($listResult['folders']) . "</li>";
                    echo "<li><strong>Total Documents:</strong> " . count($listResult['documents']) . "</li>";
                    echo "</ul>";
                    echo "</div>";
                    
                    if (!empty($listResult['folders'])) {
                        echo "<h3>📁 Accessible Folders</h3>";
                        echo "<ul>";
                        foreach ($listResult['folders'] as $folder) {
                            echo "<li>📂 " . htmlspecialchars($folder) . "</li>";
                        }
                        echo "</ul>";
                    }
                    
                    if (!empty($listResult['documents'])) {
                        echo "<h3>📄 Documents Found</h3>";
                        echo "<table style='width: 100%; border-collapse: collapse;'>";
                        echo "<thead><tr style='background: #f8f9fa;'>";
                        echo "<th style='padding: 10px; border: 1px solid #ddd;'>File</th>";
                        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Folder</th>";
                        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Size</th>";
                        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Can Delete</th>";
                        echo "</tr></thead><tbody>";
                        
                        foreach ($listResult['documents'] as $doc) {
                            echo "<tr>";
                            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($doc['filename']) . "</td>";
                            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($doc['folder']) . "</td>";
                            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . number_format($doc['size'] / 1024, 1) . " KB</td>";
                            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . ($doc['can_delete'] ? '✅ Yes' : '❌ No') . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<p>📁 No documents found in accessible folders</p>";
                    }
                } else {
                    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                    echo "<strong>❌ Error:</strong> " . htmlspecialchars($listResult['error']);
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
                echo "<strong>⚠️ AWS Not Configured:</strong> This test requires AWS credentials to be configured.";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="card">
            <h2>🔗 Quick Actions</h2>
            <a href="documents.php" style="margin-right: 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">📄 Go to Documents Page</a>
            <a href="dashboard.php" style="margin-right: 10px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">🏠 Go to Dashboard</a>
            <a href="tests/index.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">🧪 Test Suite</a>
        </div>
    </div>
</body>
</html>
