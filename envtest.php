<?php
header("Content-Type: text/plain");
echo getenv('IDENTITY_ENDPOINT') . "\n";
echo getenv('AZURE_CLIENT_ID') . "\n";
?>
