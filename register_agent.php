<?php
// File: register-agent.php
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $remarks = $_POST['remarks'];

    $token = get_graph_token();

    $invitePayload = [
        "invitedUserEmailAddress" => $email,
        "inviteRedirectUrl" => "https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/",
        "sendInvitationMessage" => true,
        "invitedUserMessageInfo" => [
            "customizedMessageBody" => "Welcome to S-Cape Agents Portal! Access after approval.\n\nRemarks: $remarks"
        ]
    ];

    $response = call_graph_api("https://graph.microsoft.com/v1.0/invitations", $token, $invitePayload);
    echo "<p>Invitation sent to $email.</p>";

    // Optional: Add to WebAgents group if needed.
    // Log or store remarks for admin approval review.
    file_put_contents(__DIR__ . '/agent-requests.log', date('c') . " | $email | $remarks\n", FILE_APPEND);
}
?>
<!DOCTYPE html>
<html>
<head><title>Register Agent</title></head>
<body>
<h2>Agent Registration (requires approval)</h2>
<form method="post">
    Email: <input type="email" name="email" required><br>
    Remarks: <textarea name="remarks" rows="4" cols="40"></textarea><br>
    <input type="submit" value="Request Access">
</form>
</body>
</html>
