<?php
require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/aws_helper.php';
require_once __DIR__ . '/lib/logger.php';

$logger = ScapeLogger::getInstance();
$logger->info('Dashboard accessed', ['session_id' => session_id()]);

start_azure_safe_session();
ensure_authenticated();

$email = $_SESSION['email'] ?? 'unknown';
$name = $_SESSION['name'] ?? 'User';
$userType = $_SESSION['user_type'] ?? 'customer'; // customer or agent
$userRole = $_SESSION['user_role'] ?? 'customer'; // customer, employee_agent, guest_agent
$entraUserType = $_SESSION['entra_user_type'] ?? 'Member'; // Member or Guest
$isGuestAgent = $_SESSION['is_guest_agent'] ?? false;
$isScapeEmployee = $_SESSION['is_scape_employee'] ?? false;
$roles = $_SESSION['roles'] ?? [];
$claims = $_SESSION['claims'] ?? null;
$id_token = $_SESSION['id_token'] ?? '';

$logger->debug('Dashboard session data loaded', [
    'email' => $email,
    'user_type' => $userType,
    'user_role' => $userRole,
    'is_guest_agent' => $isGuestAgent,
    'is_scape_employee' => $isScapeEmployee
]);

// Generate status message
$statusMessage = '';
$tenantInfo = '';
if ($userType === 'agent') {
    $tenantInfo = 'Internal Tenant (scapetravel)';
    if ($isScapeEmployee) {
        $statusMessage = 'S-Cape Employee';
    } elseif ($isGuestAgent) {
        $statusMessage = 'B2B Partner Agent (Guest User)';
    } else {
        $statusMessage = 'Internal Tenant User';
    }
} else {
    $tenantInfo = 'External Tenant (B2C)';
    $statusMessage = 'Customer';
}

// Get AWS credentials (refresh if expired)
$awsCredentials = null;
if (isset($_SESSION['aws_credentials']) && time() < $_SESSION['aws_expiry']) {
    $awsCredentials = $_SESSION['aws_credentials'];
} else {
    // Refresh AWS credentials
    $awsCredentials = get_aws_credentials($userRole, $email);
    if ($awsCredentials) {
        $_SESSION['aws_credentials'] = $awsCredentials;
        $_SESSION['aws_expiry'] = time() + 3600;
    }
}

// Get available PDFs
$availablePdfs = [];
if ($awsCredentials) {
    $availablePdfs = list_available_pdfs($userRole, $awsCredentials);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?= ucfirst($userType) ?> Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($name) ?></h1>
    
    <div class="user-info">
        <h2>User Information</h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($name) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($statusMessage) ?></p>
        <p><strong>Tenant:</strong> <?= htmlspecialchars($tenantInfo) ?></p>
        <p><strong>Entra User Type:</strong> <?= htmlspecialchars($entraUserType) ?>
           <?= $entraUserType === 'Guest' ? '<span style="color: #e74c3c;"> (Invited B2B User)</span>' : '' ?></p>
        <p><strong>Access Level:</strong> <?= ucfirst($userRole) ?></p>
        
        <?php if (!empty($roles)): ?>
        <p><strong>Entra Roles:</strong></p>
        <ul>
            <?php foreach ($roles as $role): ?>
            <li><?= htmlspecialchars($role) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    
    <div class="pdfs-section">
        <h2>Available Documents</h2>
        <?php if (empty($availablePdfs)): ?>
            <p>No documents available or AWS access denied.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Size</th>
                        <th>Modified</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availablePdfs as $pdf): ?>
                    <tr>
                        <td><?= htmlspecialchars($pdf['name']) ?></td>
                        <td><?= number_format($pdf['size'] / 1024, 1) ?> KB</td>
                        <td><?= $pdf['modified']->format('Y-m-d H:i') ?></td>
                        <td>
                            <a href="download.php?file=<?= urlencode($pdf['key']) ?>" target="_blank">Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="claims-section">
        <h2>All Claims</h2>
        <pre><?= htmlspecialchars(print_r($claims, true)) ?></pre>
    </div>
    
    <div class="token-section">
        <h2>ID Token</h2>
        <textarea readonly style="width:100%; height:100px;"><?= htmlspecialchars($id_token) ?></textarea>
    </div>
    
    <div class="actions">
        <p><a href="documents.php">📁 Manage Documents</a></p>
        <p><a href="test_s3_integration.php">🧪 Test S3 Integration</a></p>
        <p><a href="/download.php?file=test.pdf">Test download</a></p>
        <p><a href="/debug.php">Debug info</a></p>
        <p><a href="/logout.php">Logout</a></p>
    </div>
</body>
</html>
