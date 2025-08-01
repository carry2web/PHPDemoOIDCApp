<?php
// Simple Azure deployment test
header('Content-Type: application/json');

$result = [
    'status' => 'deployed',
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s'),
    'env_check' => [
        'has_external_client_id' => !empty($_ENV['EXTERNAL_CLIENT_ID']),
        'has_internal_client_id' => !empty($_ENV['INTERNAL_CLIENT_ID']),
        'redirect_uri' => $_ENV['REDIRECT_URI'] ?? 'not_set'
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>
