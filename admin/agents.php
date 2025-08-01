<?php
// File: admin/agents.php  
// Admin panel for agent application management with Entra ID authentication

require_once __DIR__ . '/../lib/oidc.php';
require_once __DIR__ . '/../lib/graph_helper.php';
require_once __DIR__ . '/../lib/config_helper.php';
require_once __DIR__ . '/../lib/logger.php';
require_once __DIR__ . '/../lib/email_helper.php';
require_once __DIR__ . '/../lib/security_helper.php';

start_azure_safe_session();

$config = get_app_config();
$logger = ScapeLogger::getInstance();
$security = SecurityHelper::getInstance();

// Validate configuration first (Woodgrove pattern)
$configErrors = validate_configuration();
if (!empty($configErrors)) {
    $logger->critical('Admin panel accessed with configuration errors', ['errors' => $configErrors]);
    die("System configuration error. Please check configuration.");
}

// Entra ID Admin Authentication (replaces password-based auth)
if (!isset($_SESSION['email']) || !isset($_SESSION['authenticated_at'])) {
    $logger->info('Admin panel access attempt - no OIDC authentication found');
    header('Location: ../index.php?login=1&type=agent');
    exit;
}

// Check if user has admin/agent role
$userType = $_SESSION['user_type'] ?? '';
$userRole = $_SESSION['role'] ?? '';

if ($userType !== 'agent' && $userRole !== 'admin') {
    $logger->warning('Admin panel access denied - insufficient privileges', [
        'user_type' => $userType,
        'role' => $userRole,
        'email' => $_SESSION['email'] ?? 'unknown'
    ]);
    die("Access denied. Admin privileges required.");
}

$_SESSION['admin_authenticated'] = true; // Set for compatibility
$_SESSION['admin_user'] = [
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'] ?? '',
    'roles' => [$userRole]
];
$logger->info('Admin panel access granted', ['email' => $_SESSION['email']]);

// Check for logout
if (isset($_POST['logout']) || isset($_GET['logout'])) {
    $logger->info('Admin logout', ['user' => $_SESSION['admin_user']['email'] ?? 'unknown']);
    unset($_SESSION['admin_authenticated']);
    unset($_SESSION['admin_user']);
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Handle approval/rejection actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Rate limiting for admin actions
    $adminEmail = $_SESSION['admin_user']['email'] ?? 'unknown';
    $rateLimitKey = 'admin_actions_' . $adminEmail;
    
    if (!$security->checkRateLimit($rateLimitKey, 20, 300)) {
        $message = "⚠️ Too many admin actions. Please wait before trying again.";
        $logger->warning('Admin rate limit exceeded', ['admin' => $adminEmail]);
    } else {
        // CSRF protection
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$security->validateCSRFToken($csrfToken)) {
            $message = "❌ Security token validation failed. Please refresh the page.";
            $logger->security('CSRF validation failed for admin action', [
                'admin' => $adminEmail,
                'action' => $_POST['action'] ?? 'unknown'
            ]);
        } else {
            // Validate action
            $actionValidation = $security->validateAction($_POST['action'] ?? '', ['approve', 'reject']);
            if (!$actionValidation['valid']) {
                $message = "❌ " . $actionValidation['error'];
            } else {
                $action = $actionValidation['value'];
                
                // Validate email
                $emailValidation = $security->validateEmail($_POST['email'] ?? '');
                if (!$emailValidation['valid']) {
                    $message = "❌ " . $emailValidation['error'];
                } else {
                    $email = $emailValidation['value'];
                    
                    // Validate name
                    $nameValidation = $security->validateName($_POST['name'] ?? '');
                    if (!$nameValidation['valid']) {
                        $message = "❌ " . $nameValidation['error'];
                    } else {
                        $name = $nameValidation['value'];
                        
                        $logger->info('Admin action initiated', [
                            'action' => $action,
                            'target_email' => $email,
                            'admin_user' => $adminEmail
                        ]);
                        
                        if ($action === 'approve') {
                            $startTime = microtime(true);
                            
                            // Send B2B invitation via Graph API
                            $result = invite_agent_to_internal_tenant($email, $name);
                            $duration = round((microtime(true) - $startTime) * 1000, 2);
                            
                            if ($result['success']) {
                                $message = "✅ Agent approved and B2B invitation sent to {$email}";
                                
                                $logger->info('Agent approval successful', [
                                    'email' => $email,
                                    'invitation_id' => $result['invitationId'] ?? 'unknown',
                                    'duration_ms' => $duration
                                ]);
                                
                                // Send approval notification email
                                $emailResult = notify_agent_approval($email, $name, $result['inviteRedeemUrl'] ?? null);
                                if (!$emailResult['success']) {
                                    $logger->warning('Agent approval email failed', [
                                        'email' => $email,
                                        'error' => $emailResult['error'] ?? 'unknown'
                                    ]);
                                }
                                
                                // Mark as approved in applications file
                                mark_application_approved($email);
                            } else {
                                $message = "❌ Failed to send invitation: " . $result['error'];
                                $logger->error('Agent approval failed', [
                                    'email' => $email,
                                    'error' => $result['error'],
                                    'duration_ms' => $duration
                                ]);
                            }
                        } elseif ($action === 'reject') {
                            // Validate rejection reason
                            $reasonValidation = $security->validateReason($_POST['rejection_reason'] ?? '');
                            if (!$reasonValidation['valid']) {
                                $message = "❌ " . $reasonValidation['error'];
                            } else {
                                $reason = $reasonValidation['value'];
                                
                                mark_application_rejected($email, $reason);
                                $message = "❌ Application for {$email} has been rejected";
                                
                                $logger->info('Agent application rejected', [
                                    'email' => $email,
                                    'reason' => $reason,
                                    'admin_user' => $adminEmail
                                ]);
                                
                                // Send rejection notification email
                                $emailResult = notify_agent_rejection($email, $name, $reason);
                                if (!$emailResult['success']) {
                                    $logger->warning('Agent rejection email failed', [
                                        'email' => $email,
                                        'error' => $emailResult['error'] ?? 'unknown'
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Helper functions
function hasAdminRole($userInfo) {
    $requiredRole = $_ENV['ADMIN_ROLE'] ?? 'Admin';
    
    // Check app roles (preferred method)
    if (isset($userInfo['roles']) && is_array($userInfo['roles'])) {
        return in_array($requiredRole, $userInfo['roles']);
    }
    
    // Fallback: check if user email matches admin email
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@scape-travel.com';
    if (isset($userInfo['email']) && $userInfo['email'] === $adminEmail) {
        return true;
    }
    
    // Additional check: look for admin indicator in claims
    if (isset($userInfo['extension_Admin']) && $userInfo['extension_Admin'] === 'true') {
        return true;
    }
    
    return false;
}

function redirectToAdminLogin() {
    // Redirect to internal tenant login for admin access
    $oidcClient = get_oidc_client('internal');
    $oidcClient->authenticate();
}

function showAccessDenied() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied - S-Cape Travel Admin</title>
        <link rel="stylesheet" href="../style.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <h1>🚫 Access Denied</h1>
        <div class="error">
            <h2>Insufficient Permissions</h2>
            <p>You do not have administrative privileges to access this panel.</p>
            <p>Administrator access requires the <strong>"Admin"</strong> app role in the Entra ID tenant.</p>
        </div>
        
        <div class="info-box">
            <h3>To gain admin access:</h3>
            <ol>
                <li>Contact your Azure administrator</li>
                <li>Request the "Admin" app role assignment</li>
                <li>Ensure you're logging in via the internal tenant</li>
            </ol>
        </div>
        
        <p><a href="../index.php" class="button">← Return to Main Site</a></p>
        
        <footer style="margin-top: 2em; text-align: center; font-size: 0.8em; color: #666;">
            Secure Admin Access | Entra ID App Roles | Woodgrove Security Pattern
        </footer>
    </body>
    </html>
    <?php
}

// Load applications
$applications = load_agent_applications();

function load_agent_applications() {
    $filename = __DIR__ . '/../data/agent_applications.json';
    if (!file_exists($filename)) {
        return [];
    }
    
    $data = file_get_contents($filename);
    $applications = json_decode($data, true) ?? [];
    
    // Ensure we have an array of applications
    if (!is_array($applications)) {
        return [];
    }
    
    // Handle old format (single application) vs new format (array of applications)
    if (isset($applications['email']) && !isset($applications[0])) {
        return [$applications];
    }
    
    return $applications;
}

function mark_application_approved($email) {
    $filename = __DIR__ . '/../data/agent_applications.json';
    $applications = load_agent_applications();
    
    foreach ($applications as &$app) {
        if ($app['email'] === $email) {
            $app['status'] = 'approved';
            $app['approved_at'] = date('c');
            break;
        }
    }
    
    file_put_contents($filename, json_encode($applications, JSON_PRETTY_PRINT));
}

function mark_application_rejected($email, $reason = null) {
    $filename = __DIR__ . '/../data/agent_applications.json';
    $applications = load_agent_applications();
    
    foreach ($applications as &$app) {
        if ($app['email'] === $email) {
            $app['status'] = 'rejected';
            $app['rejected_at'] = date('c');
            $app['rejection_reason'] = $reason ?: 'Application does not meet current requirements';
            break;
        }
    }
    
    file_put_contents($filename, json_encode($applications, JSON_PRETTY_PRINT));
    
    // Log the rejection
    $logger = ScapeLogger::getInstance();
    $logger->info('Application marked as rejected', [
        'email' => $email,
        'reason' => $reason
    ]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Agent Management - S-Cape Travel Admin</title>
    <link rel="stylesheet" href="../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .application-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5em;
            margin: 1em 0;
            background: #f9f9f9;
        }
        .application-card.pending { border-left: 4px solid #ffa500; }
        .application-card.approved { border-left: 4px solid #28a745; }
        .application-card.rejected { border-left: 4px solid #dc3545; }
        
        .application-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 1em;
        }
        
        .action-buttons {
            margin-top: 1em;
        }
        
        .action-buttons button {
            margin-right: 0.5em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1em;
            margin-bottom: 2em;
        }
        
        .stat-card {
            background: white;
            padding: 1em;
            border-radius: 8px;
            border-left: 4px solid #007cba;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div style="background: #007cba; color: white; padding: 1em; margin-bottom: 2em;">
        <h1>🔧 Agent Management Console</h1>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <p><em>B2B Partnership Management - Entra ID Secured</em></p>
            <div style="font-size: 0.9em;">
                👤 Admin: <?= htmlspecialchars($_SESSION['admin_user']['email'] ?? 'Unknown') ?> |
                🏢 Tenant: <?= htmlspecialchars($_SESSION['admin_user']['tid'] ?? 'Unknown') ?>
            </div>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="<?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php
    // Calculate statistics
    $totalApps = count($applications);
    $pendingApps = count(array_filter($applications, fn($app) => ($app['status'] ?? 'pending') === 'pending'));
    $approvedApps = count(array_filter($applications, fn($app) => ($app['status'] ?? 'pending') === 'approved'));
    $rejectedApps = count(array_filter($applications, fn($app) => ($app['status'] ?? 'pending') === 'rejected'));
    ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>📋 Total Applications</h3>
            <div style="font-size: 2em; font-weight: bold; color: #007cba;"><?= $totalApps ?></div>
        </div>
        <div class="stat-card">
            <h3>⏳ Pending Review</h3>
            <div style="font-size: 2em; font-weight: bold; color: #ffa500;"><?= $pendingApps ?></div>
        </div>
        <div class="stat-card">
            <h3>✅ Approved</h3>
            <div style="font-size: 2em; font-weight: bold; color: #28a745;"><?= $approvedApps ?></div>
        </div>
        <div class="stat-card">
            <h3>❌ Rejected</h3>
            <div style="font-size: 2em; font-weight: bold; color: #dc3545;"><?= $rejectedApps ?></div>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Agent Applications</h2>
        
        <?php if (empty($applications)): ?>
        <div class="info-box">
            <p><strong>No applications found.</strong></p>
            <p>Agent applications will appear here when submitted through the application form.</p>
        </div>
        <?php else: ?>
        
        <?php foreach ($applications as $app): 
            $status = $app['status'] ?? 'pending';
            $timestamp = $app['timestamp'] ?? $app['applied_at'] ?? 'Unknown';
        ?>
        <div class="application-card <?= $status ?>">
            <div class="application-meta">
                <strong>Status:</strong> <?= ucfirst($status) ?> | 
                <strong>Applied:</strong> <?= date('M j, Y g:i A', strtotime($timestamp)) ?>
                <?php if (isset($app['ip_address'])): ?>
                | <strong>IP:</strong> <?= htmlspecialchars($app['ip_address']) ?>
                <?php endif; ?>
            </div>
            
            <h3><?= htmlspecialchars($app['name'] ?? 'Unknown') ?></h3>
            <p><strong>Email:</strong> <?= htmlspecialchars($app['email'] ?? 'Unknown') ?></p>
            <p><strong>Company:</strong> <?= htmlspecialchars($app['company'] ?? 'Unknown') ?></p>
            <p><strong>Business Reason:</strong></p>
            <div style="background: white; padding: 1em; border-radius: 4px; margin: 0.5em 0;">
                <?= nl2br(htmlspecialchars($app['reason'] ?? 'No reason provided')) ?>
            </div>
            
            <?php if ($status === 'pending'): ?>
            <div class="action-buttons">
                <form method="post" style="display: inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($app['email']) ?>">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($app['name']) ?>">
                    <button type="submit" class="button customer" 
                            onclick="return confirm('Approve this agent application? This will send a B2B invitation to <?= htmlspecialchars($app['email']) ?>')">
                        ✅ Approve & Send B2B Invitation
                    </button>
                </form>
                
                <button type="button" class="button" style="background: #dc3545;" 
                        onclick="showRejectModal('<?= htmlspecialchars($app['email']) ?>', '<?= htmlspecialchars($app['name']) ?>')">
                    ❌ Reject Application
                </button>
            </div>
            <?php elseif ($status === 'approved'): ?>
            <div style="color: #28a745; font-weight: bold; margin-top: 1em;">
                ✅ B2B invitation sent on <?= date('M j, Y', strtotime($app['approved_at'] ?? $timestamp)) ?>
            </div>
            <?php elseif ($status === 'rejected'): ?>
            <div style="color: #dc3545; font-weight: bold; margin-top: 1em;">
                ❌ Application rejected on <?= date('M j, Y', strtotime($app['rejected_at'] ?? $timestamp)) ?>
                <?php if (!empty($app['rejection_reason'])): ?>
                <br><small>Reason: <?= htmlspecialchars($app['rejection_reason']) ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <div class="info-box" style="margin-top: 2em;">
        <h3>🔄 Woodgrove B2B Process</h3>
        <ol>
            <li><strong>Application Review:</strong> Review business credentials and partnership fit</li>
            <li><strong>Approval:</strong> Click "Approve" to automatically send Microsoft B2B guest invitation</li>
            <li><strong>Guest Invitation:</strong> Agent receives email to join S-Cape internal tenant as guest</li>
            <li><strong>Agent Access:</strong> Once accepted, agent can login via "Agent/Employee Login"</li>
        </ol>
        
        <div style="background: #e8f4fd; padding: 1em; border-radius: 4px; margin-top: 1em;">
            <strong>Security Note:</strong> All administrative actions are logged for audit compliance. 
            B2B guest invitations are managed through Microsoft Graph API with enterprise security controls.
        </div>
    </div>
    
    <div style="margin-top: 2em;">
        <a href="../admin/config_check.php" class="button">🔧 Configuration Check</a>
        <a href="../index.php" class="button customer">🏠 Back to Site</a>
        <form method="post" style="display: inline;">
            <?= csrf_input() ?>
            <input type="hidden" name="logout" value="1">
            <button type="submit" class="button" style="background: #dc3545;">🚪 Logout</button>
        </form>
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2em; border-radius: 8px; max-width: 500px; width: 90%;">
            <h3>Reject Agent Application</h3>
            <form method="post" id="rejectForm">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="email" id="rejectEmail">
                <input type="hidden" name="name" id="rejectName">
                
                <div class="form-group">
                    <label for="rejection_reason">Reason for rejection:</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" 
                              placeholder="Please provide a reason for rejecting this application..."
                              style="width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px;" required></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 1em;">
                    <button type="button" onclick="hideRejectModal()" class="button" style="background: #6c757d; margin-right: 1em;">Cancel</button>
                    <button type="submit" class="button" style="background: #dc3545;">Reject Application</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showRejectModal(email, name) {
            document.getElementById('rejectEmail').value = email;
            document.getElementById('rejectName').value = name;
            document.getElementById('rejectModal').style.display = 'block';
            document.getElementById('rejection_reason').focus();
        }
        
        function hideRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('rejection_reason').value = '';
        }
        
        // Close modal when clicking outside
        document.getElementById('rejectModal').onclick = function(e) {
            if (e.target === this) {
                hideRejectModal();
            }
        }
    </script>
    
    <footer style="margin-top: 2em; text-align: center; font-size: 0.8em; color: #666;">
        Admin Panel | Microsoft B2B Management | Entra ID Authentication | Enhanced Logging
    </footer>
</body>
</html>
