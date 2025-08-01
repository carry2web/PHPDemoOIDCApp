<?php
// File: logout.php
require_once __DIR__ . '/lib/oidc.php';
start_azure_safe_session();

// Clear all session data
session_unset();
session_destroy();

// Redirect to index
header('Location: index.php');
exit;
?>
