<?php
// token_handler.php — Fetch AWS STS token using Azure User-Assigned Managed Identity

header('Content-Type: application/json');

// Set the known client ID of your user-assigned managed identity (TravelSpiritPDFs)
$clientId = '4f80970e-2dab-48ed-bcba-bbbc0a896e81';

// Read from environment
$identityEndpoint = getenv('IDENTITY_ENDPOINT');

if (!$identityEndpoint || !$clientId) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Missing IDENTITY_ENDPOINT or clientId"
    ]);
    exit;
}

// Metadata service call with required headers
$url = $identityEndpoint . "?api-version=2019-08-01&resource=sts.amazonaws.com";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Metadata: true",
    "X-IDENTITY-CLIENT-ID: $clientId"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode([
        "status" => "success",
        "access_token" => substr($data['access_token'], 0, 40) . "...",
        "expires_on" => $data['expires_on'],
        "client_id" => $clientId
    ]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch token from metadata endpoint",
        "http_code" => $httpCode,
        "curl_error" => $error,
        "raw_response" => $response
    ]);
}
