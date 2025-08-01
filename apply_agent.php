<?php
// File: apply_agent.php
require_once __DIR__ . '/lib/logger.php';

$logger = ScapeLogger::getInstance();
$logger->info('Agent application page accessed');

start_azure_safe_session();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Partner Agent Application - S-Cape Travel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Partner Agent Application</h1>
        
        <div class="info-box">
            <h3>Become a S-Cape Travel Partner Agent</h3>
            <p>To become a partner agent, you need to:</p>
            <ol>
                <li>Contact our partnerships team at <strong>partnerships@scape-travel.com</strong></li>
                <li>Complete the partner agreement process</li>
                <li>Receive an invitation to our internal tenant</li>
                <li>Access the agent portal with your B2B guest account</li>
            </ol>
        </div>
        
        <div class="contact-form">
            <h3>Quick Contact Form</h3>
            <form action="mailto:partnerships@scape-travel.com" method="get" enctype="text/plain">
                <p>
                    <label>Your Name:</label><br>
                    <input type="text" name="subject" placeholder="Partner Application - Your Name" style="width: 100%; padding: 8px;">
                </p>
                <p>
                    <label>Message:</label><br>
                    <textarea name="body" rows="5" style="width: 100%; padding: 8px;" placeholder="Please include:&#10;- Your company name&#10;- Your role&#10;- Why you want to partner with S-Cape Travel&#10;- Your contact information"></textarea>
                </p>
                <p>
                    <input type="submit" value="Send Email" class="btn btn-primary">
                </p>
            </form>
        </div>
        
        <p><a href="index.php">← Back to Login</a></p>
        
        <div class="footer">
            <p>Secured by Microsoft Identity Platform | Following Woodgrove Security Patterns</p>
        </div>
    </div>
</body>
</html>
