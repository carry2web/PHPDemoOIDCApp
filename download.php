<?php
require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/aws_helper.php';
start_azure_safe_session();
ensure_authenticated();

$fileKey = $_GET['file'] ?? '';
if (empty($fileKey)) {
    die('No file specified');
}

$userRole = $_SESSION['user_role'] ?? 'customer';
$awsCredentials = $_SESSION['aws_credentials'] ?? null;

// Check if AWS credentials are still valid
if (!$awsCredentials || time() >= $_SESSION['aws_expiry']) {
    // Refresh credentials
    $awsCredentials = get_aws_credentials($userRole, $_SESSION['email']);
    if ($awsCredentials) {
        $_SESSION['aws_credentials'] = $awsCredentials;
        $_SESSION['aws_expiry'] = time() + 3600;
    } else {
        die('AWS access denied');
    }
}

// Verify user has access to this file (role-based path check)
$allowedPrefix = ($userRole === 'agent') ? 'agents/' : 'customers/';
if (!str_starts_with($fileKey, $allowedPrefix)) {
    die('Access denied: File not in your authorized path');
}

// Generate presigned URL
$downloadUrl = get_pdf_download_url($fileKey, $awsCredentials, 300); // 5 minutes

if ($downloadUrl) {
    // Redirect to the presigned URL
    header('Location: ' . $downloadUrl);
    exit;
} else {
    die('Failed to generate download URL');
}
