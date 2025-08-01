<?php
// File: admin/config_check.php
// Configuration validation page following Woodgrove patterns

require_once __DIR__ . '/../lib/config_helper.php';

$config = get_app_config();
$errors = validate_configuration();
$warnings = [];

// Check additional warnings
if ($config['app']['debug']) {
    $warnings[] = 'Debug mode is enabled - disable in production';
}

if (strpos($config['app']['redirect_uri'], 'localhost') !== false) {
    $warnings[] = 'Using localhost redirect URI - update for production';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Configuration Check - S-Cape Travel</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .config-section { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .config-good { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .config-warning { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .config-error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .config-table { width: 100%; border-collapse: collapse; }
        .config-table th, .config-table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .config-table th { background-color: #f8f9fa; }
        .mask { filter: blur(4px); }
    </style>
</head>
<body>
    <h1>Configuration Check</h1>
    <p><em>Following Woodgrove configuration validation patterns</em></p>
    
    <?php if (empty($errors) && empty($warnings)): ?>
    <div class="config-section config-good">
        <h2>✅ Configuration Valid</h2>
        <p>All required settings are properly configured.</p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="config-section config-error">
        <h2>❌ Configuration Errors</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($warnings)): ?>
    <div class="config-section config-warning">
        <h2>⚠️ Configuration Warnings</h2>
        <ul>
            <?php foreach ($warnings as $warning): ?>
            <li><?= htmlspecialchars($warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="config-section">
        <h2>B2C Configuration (Customer Tenant)</h2>
        <table class="config-table">
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Tenant ID</td><td><?= htmlspecialchars($config['b2c']['tenant_id']) ?></td></tr>
            <tr><td>Client ID</td><td class="<?= strpos($config['b2c']['client_id'], 'here') !== false ? 'mask' : '' ?>"><?= htmlspecialchars($config['b2c']['client_id']) ?></td></tr>
            <tr><td>Domain</td><td><?= htmlspecialchars($config['b2c']['domain']) ?></td></tr>
            <tr><td>Sign-up/Sign-in Policy</td><td><?= htmlspecialchars($config['b2c']['policy_signup_signin']) ?></td></tr>
        </table>
    </div>
    
    <div class="config-section">
        <h2>B2B Configuration (Internal Tenant)</h2>
        <table class="config-table">
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Tenant ID</td><td><?= htmlspecialchars($config['b2b']['tenant_id']) ?></td></tr>
            <tr><td>Client ID</td><td class="<?= strpos($config['b2b']['client_id'], 'here') !== false ? 'mask' : '' ?>"><?= htmlspecialchars($config['b2b']['client_id']) ?></td></tr>
            <tr><td>Domain</td><td><?= htmlspecialchars($config['b2b']['domain']) ?></td></tr>
        </table>
    </div>
    
    <div class="config-section">
        <h2>Microsoft Graph Configuration</h2>
        <table class="config-table">
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Client ID</td><td class="<?= strpos($config['graph']['client_id'], 'here') !== false ? 'mask' : '' ?>"><?= htmlspecialchars($config['graph']['client_id']) ?></td></tr>
            <tr><td>Tenant ID</td><td><?= htmlspecialchars($config['graph']['tenant_id']) ?></td></tr>
            <tr><td>Required Scopes</td><td><?= implode('<br>', $config['graph']['scopes']) ?></td></tr>
        </table>
    </div>
    
    <div class="config-section">
        <h2>AWS Configuration</h2>
        <table class="config-table">
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Region</td><td><?= htmlspecialchars($config['aws']['region']) ?></td></tr>
            <tr><td>S3 Bucket</td><td><?= htmlspecialchars($config['aws']['bucket']) ?></td></tr>
            <tr><td>Customer Role</td><td class="<?= strpos($config['aws']['roles']['customer'], 'ACCOUNT') !== false ? 'mask' : '' ?>"><?= htmlspecialchars($config['aws']['roles']['customer']) ?></td></tr>
            <tr><td>Agent Role</td><td class="<?= strpos($config['aws']['roles']['agent'], 'ACCOUNT') !== false ? 'mask' : '' ?>"><?= htmlspecialchars($config['aws']['roles']['agent']) ?></td></tr>
        </table>
    </div>
    
    <div class="config-section">
        <h2>B2C Policy URLs</h2>
        <?php $policyUrls = get_b2c_policy_urls(); ?>
        <table class="config-table">
            <tr><th>Policy</th><th>URL</th></tr>
            <tr><td>Sign-up/Sign-in</td><td><?= htmlspecialchars($policyUrls['signup_signin']) ?></td></tr>
            <tr><td>Password Reset</td><td><?= htmlspecialchars($policyUrls['password_reset']) ?></td></tr>
            <tr><td>Profile Edit</td><td><?= htmlspecialchars($policyUrls['profile_edit']) ?></td></tr>
        </table>
    </div>
    
    <div class="config-section">
        <h2>Application Settings</h2>
        <table class="config-table">
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Redirect URI</td><td><?= htmlspecialchars($config['app']['redirect_uri']) ?></td></tr>
            <tr><td>Debug Mode</td><td><?= $config['app']['debug'] ? 'Enabled' : 'Disabled' ?></td></tr>
            <tr><td>Admin Email</td><td><?= htmlspecialchars($config['app']['admin_email']) ?></td></tr>
        </table>
    </div>
    
    <p><a href="../index.php">Back to Portal</a> | <a href="agents.php">Agent Management</a></p>
</body>
</html>
