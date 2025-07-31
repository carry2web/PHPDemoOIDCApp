<?php
require_once __DIR__ . '/../lib/oidc.php';

start_azure_safe_session();

if (!is_debug_mode()) {
    http_response_code(403);
    echo "DEBUG is uitgeschakeld.";
    exit;
}

echo "<h1>🔍 DEBUG MODE</h1>";
echo "<pre>";
echo "SESSION:\n";
print_r($_SESSION);
echo "\nENV:\n";
print_r(parse_ini_file(__DIR__ . '/../.env'));
echo "</pre>";
echo "\nPHP ERROR LOG:\n";
$log = file_exists('/home/logs/php_errors.log') ? file_get_contents('/home/logs/php_errors.log') : 'Geen fouten gelogd.';
echo "<pre>" . htmlspecialchars($log) . "</pre>";
