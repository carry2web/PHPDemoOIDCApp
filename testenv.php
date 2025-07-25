<?php
$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    die("⚠️ .env file not found at: $envPath");
}

$env = parse_ini_file($envPath);

if ($env === false) {
    die("⚠️ Failed to parse .env file.");
}

echo "<pre>";
print_r($env);
echo "</pre>";
