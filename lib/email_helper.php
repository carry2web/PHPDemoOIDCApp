<?php
// File: lib/email_helper.php
// Email notification service using Microsoft Graph API

require_once __DIR__ . '/config_helper.php';
require_once __DIR__ . '/logger.php';

class EmailNotificationService {
    private static $instance = null;
    private $logger;
    private $config;
    private $graphToken;
    
    private function __construct() {
        $this->logger = ScapeLogger::getInstance();
        $this->config = get_app_config();
        $this->logger->debug('EmailNotificationService initialized with Graph API');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get Microsoft Graph API access token
     */
    private function getGraphToken() {
        if ($this->graphToken && $this->graphToken['expires'] > time()) {
            return $this->graphToken['token'];
        }
        
        $this->logger->debug('Requesting new Graph API token for email');
        
        $tokenUrl = 'https://login.microsoftonline.com/' . $this->config['graph']['tenant_id'] . '/oauth2/v2.0/token';
        
        $postData = [
            'client_id' => $this->config['graph']['client_id'],
            'client_secret' => $this->config['graph']['client_secret'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $this->logger->error('Failed to get Graph API token for email', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new Exception('Failed to get Graph API token');
        }
        
        $tokenData = json_decode($response, true);
        
        $this->graphToken = [
            'token' => $tokenData['access_token'],
            'expires' => time() + $tokenData['expires_in'] - 60 // 1 minute buffer
        ];
        
        $this->logger->debug('Graph API token obtained successfully for email');
        return $this->graphToken['token'];
    }
    
    /**
     * Send email using Microsoft Graph API
     */
    public function sendEmail($toEmail, $subject, $htmlContent, $textContent = null) {
        $startTime = microtime(true);
        
        try {
            $token = $this->getGraphToken();
            $fromEmail = $this->config['app']['admin_email'];
            
            $this->logger->info('Sending email via Graph API', [
                'to' => $toEmail,
                'subject' => $subject,
                'from' => $fromEmail
            ]);
            
            // Graph API endpoint to send mail as the admin user
            $graphUrl = 'https://graph.microsoft.com/v1.0/users/' . urlencode($fromEmail) . '/sendMail';
            
            $emailData = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $htmlContent
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $toEmail
                            ]
                        ]
                    ],
                    'from' => [
                        'emailAddress' => [
                            'address' => $fromEmail,
                            'name' => 'S-Cape Travel'
                        ]
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $graphUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($httpCode === 202) {
                $this->logger->info('Email sent successfully via Graph API', [
                    'to' => $toEmail,
                    'subject' => $subject,
                    'duration_ms' => $duration
                ]);
                return true;
            } else {
                $this->logger->error('Failed to send email via Graph API', [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'curl_error' => $curlError,
                    'to' => $toEmail,
                    'duration_ms' => $duration
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Email sending failed', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            return false;
        }
    }
    
    /**
     * Send agent approval notification
     */
    public function sendAgentApprovalNotification($agentEmail, $agentName) {
        $subject = 'Welcome to S-Cape Travel - Agent Access Approved';
        
        $htmlContent = $this->getApprovalEmailTemplate($agentName);
        
        $this->logger->info('Sending agent approval notification', [
            'agent_email' => $agentEmail,
            'agent_name' => $agentName
        ]);
        
        return $this->sendEmail($agentEmail, $subject, $htmlContent);
    }
    
    /**
     * Send agent rejection notification
     */
    public function sendAgentRejectionNotification($agentEmail, $agentName, $reason = '') {
        $subject = 'S-Cape Travel Agent Application Update';
        
        $htmlContent = $this->getRejectionEmailTemplate($agentName, $reason);
        
        $this->logger->info('Sending agent rejection notification', [
            'agent_email' => $agentEmail,
            'agent_name' => $agentName,
            'reason' => $reason
        ]);
        
        return $this->sendEmail($agentEmail, $subject, $htmlContent);
    }
    
    /**
     * Send admin notification for new agent application
     */
    public function sendAdminAgentApplicationNotification($name, $email, $company, $reason) {
        $adminEmail = $this->config['app']['admin_email'];
        $subject = '🚨 New Agent Application - ' . $company;
        
        $htmlContent = $this->getAdminNotificationTemplate($name, $email, $company, $reason);
        
        $this->logger->info('Sending admin notification for new agent application', [
            'applicant_email' => $email,
            'applicant_name' => $name,
            'company' => $company
        ]);
        
        return $this->sendEmail($adminEmail, $subject, $htmlContent);
    }
    
    /**
     * Get approval email template
     */
    private function getApprovalEmailTemplate($agentName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to S-Cape Travel</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007acc; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #007acc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🌍 Welcome to S-Cape Travel</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($agentName) . ",</h2>
                    <p>Great news! Your agent application has been <strong>approved</strong>.</p>
                    <p>You now have access to the S-Cape Travel agent portal.</p>
                    <a href='https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/' class='button'>Access Agent Portal</a>
                    <p><strong>The S-Cape Travel Team</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2025 S-Cape Travel. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get rejection email template
     */
    private function getRejectionEmailTemplate($agentName, $reason) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>S-Cape Travel Application Update</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #666; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .reason { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🌍 S-Cape Travel</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($agentName) . ",</h2>
                    <p>Thank you for your interest in becoming a S-Cape Travel agent.</p>
                    <p>After careful review, we are unable to approve your application at this time.</p>
                    " . (!empty($reason) ? "<div class='reason'><strong>Reason:</strong> " . htmlspecialchars($reason) . "</div>" : "") . "
                    <p><strong>The S-Cape Travel Team</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2025 S-Cape Travel. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get admin notification template
     */
    private function getAdminNotificationTemplate($name, $email, $company, $reason) {
        $reviewUrl = $this->getBaseUrl() . '/admin/agents.php';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Agent Application</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007cba; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .info-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                .info-table .label { font-weight: bold; width: 120px; }
                .reason-box { background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba; margin: 20px 0; }
                .button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 New Agent Application</h1>
                </div>
                <div class='content'>
                    <h2>Application Details</h2>
                    <table class='info-table'>
                        <tr><td class='label'>Name:</td><td>" . htmlspecialchars($name) . "</td></tr>
                        <tr><td class='label'>Email:</td><td>" . htmlspecialchars($email) . "</td></tr>
                        <tr><td class='label'>Company:</td><td>" . htmlspecialchars($company) . "</td></tr>
                        <tr><td class='label'>Submitted:</td><td>" . date('F j, Y \\a\\t g:i A') . "</td></tr>
                    </table>
                    
                    <h3>Business Reason:</h3>
                    <div class='reason-box'>
                        " . nl2br(htmlspecialchars($reason)) . "
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='" . $reviewUrl . "' class='button'>🔍 Review Application</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>S-Cape Travel Admin Portal | Microsoft B2B Partnership Program</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get base URL for links in emails
     */
    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host;
    }
}

// Global email helper functions
function send_admin_notification($subject, $message, $context = []) {
    $emailService = EmailNotificationService::getInstance();
    $config = get_app_config();
    return $emailService->sendEmail(
        $config['app']['admin_email'],
        $subject,
        $message
    );
}

function notify_agent_application($name, $email, $company, $reason) {
    $emailService = EmailNotificationService::getInstance();
    return $emailService->sendAdminAgentApplicationNotification($name, $email, $company, $reason);
}

function notify_agent_approval($email, $name) {
    $emailService = EmailNotificationService::getInstance();
    return $emailService->sendAgentApprovalNotification($email, $name);
}

function notify_agent_rejection($email, $name, $reason = null) {
    $emailService = EmailNotificationService::getInstance();
    return $emailService->sendAgentRejectionNotification($email, $name, $reason);
}
?>
