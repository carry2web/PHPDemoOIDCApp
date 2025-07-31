<?php
// File: register-customer.php
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $token = get_graph_token();

    $invitePayload = [
        "invitedUserEmailAddress" => $email,
        "inviteRedirectUrl" => "https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/",
        "sendInvitationMessage" => true,
        "invitedUserMessageInfo" => [
            "customizedMessageBody" => "Welcome to S-Cape! Please click the link to access your travel portal."
        ]
    ];

    $response = call_graph_api("https://graph.microsoft.com/v1.0/invitations", $token, $invitePayload);
    echo "<p>Invitation sent to $email.</p>";
}

function get_graph_token() {
    $env = parse_ini_file(__DIR__ . '/.env');
    $token_response = json_decode(file_get_contents("https://login.microsoftonline.com/{$env['TENANT_ID']}/oauth2/v2.0/token", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'client_id' => $env['CLIENT_ID'],
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => $env['CLIENT_SECRET'],
                'grant_type' => 'client_credentials'
            ])
        ]
    ])), true);

    return $token_response['access_token'];
}

function call_graph_api($url, $token, $data) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $token\r\nContent-Type: application/json",
            'content' => json_encode($data)
        ]
    ];
    return json_decode(file_get_contents($url, false, stream_context_create($opts)), true);
}
?>
<!DOCTYPE html>
<html>
<head><title>Register Customer</title></head>
<body>
<h2>Customer Registration</h2>
<form method="post">
    Email: <input type="email" name="email" required><br>
    <input type="submit" value="Register">
</form>
</body>
</html>