<?php
require_once __DIR__ . '/../lib/oidc.php';
require_once __DIR__ . '/../lib/s3_helper.php';

start_azure_safe_session();
ensure_authenticated();

// Toegangscontrole op basis van rol
if (!in_array('pdf_access', $_SESSION['roles'] ?? [])) {
    http_response_code(403);
    echo "Geen toegang tot dit bestand.";
    exit;
}

// Bestandsnaam valideren uit querystring
$filename = $_GET['file'] ?? null;
if (!$filename || !preg_match('/^[a-zA-Z0-9_\-\.]+\.pdf$/', $filename)) {
    http_response_code(400);
    echo "Ongeldig bestandsverzoek.";
    exit;
}

// Vraag pre-signed URL op
$url = get_presigned_url_for_file($filename);
if (!$url) {
    http_response_code(500);
    echo "Kon downloadlink niet genereren.";
    exit;
}

// Redirect naar S3 download
header("Location: $url");
exit;
