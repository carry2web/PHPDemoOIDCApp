<?php
// File: apply_agent.php
require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/security_helper.php';

$logger = ScapeLogger::getInstance();
$security = SecurityHelper::getInstance();

start_azure_safe_session();

$submitResult = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting check
    $rateLimitKey = 'agent_application_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!$security->checkRateLimit($rateLimitKey, 3, 3600)) { // 3 submissions per hour
        $submitResult = [
            'success' => false,
            'error' => 'Too many applications submitted. Please try again later.'
        ];
    } else {
        // CSRF protection
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$security->validateCSRFToken($csrfToken)) {
            $submitResult = [
                'success' => false,
                'error' => 'Security token validation failed. Please refresh the page and try again.'
            ];
        } else {
            // Validate form data
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $company = trim($_POST['company'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $message = trim($_POST['message'] ?? '');
            
            $errors = [];
            
            if (empty($name) || strlen($name) < 2) {
                $errors[] = 'Name is required and must be at least 2 characters';
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email address is required';
            }
            
            if (empty($company) || strlen($company) < 2) {
                $errors[] = 'Company name is required';
            }
            
            if (empty($role)) {
                $errors[] = 'Your role is required';
            }
            
            if (empty($message) || strlen($message) < 10) {
                $errors[] = 'Message is required and must be at least 10 characters';
            }
            
            if (!empty($errors)) {
                $submitResult = [
                    'success' => false,
                    'error' => 'Please fix the following errors: ' . implode(', ', $errors)
                ];
            } else {
                // Process the application
                $applicationData = [
                    'name' => $name,
                    'email' => $email,
                    'company' => $company,
                    'role' => $role,
                    'message' => $message,
                    'submitted_at' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ];
                
                // Log the application
                $logger->info('Agent application submitted', $applicationData);
                
                // Save to file for processing (you can also integrate with email service)
                $applicationFile = __DIR__ . '/data/agent_applications_' . date('Y-m') . '.json';
                $applications = [];
                if (file_exists($applicationFile)) {
                    $applications = json_decode(file_get_contents($applicationFile), true) ?: [];
                }
                $applications[] = $applicationData;
                
                // Ensure data directory exists
                if (!is_dir(__DIR__ . '/data')) {
                    mkdir(__DIR__ . '/data', 0755, true);
                }
                
                file_put_contents($applicationFile, json_encode($applications, JSON_PRETTY_PRINT));
                
                // Send notification email (if email service is configured)
                try {
                    $emailSent = sendAgentApplicationNotification($applicationData);
                    
                    $submitResult = [
                        'success' => true,
                        'message' => 'Your application has been submitted successfully! We will review your application and contact you within 2-3 business days.',
                        'email_sent' => $emailSent
                    ];
                } catch (Exception $e) {
                    $logger->error('Failed to send application notification', [
                        'error' => $e->getMessage(),
                        'application' => $applicationData
                    ]);
                    
                    $submitResult = [
                        'success' => true,
                        'message' => 'Your application has been submitted successfully! We will review your application and contact you within 2-3 business days.',
                        'email_sent' => false
                    ];
                }
                
                // Clear form data on success
                if ($submitResult['success']) {
                    $_POST = [];
                }
            }
        }
    }
    
    // Redirect to prevent resubmission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $redirectUrl = $_SERVER['REQUEST_URI'];
        if ($submitResult['success']) {
            $redirectUrl .= '?submitted=success';
        } else {
            $redirectUrl .= '?submitted=error&msg=' . urlencode($submitResult['error']);
        }
        header("Location: $redirectUrl");
        exit;
    }
}

// Handle redirect parameters
if (isset($_GET['submitted'])) {
    if ($_GET['submitted'] === 'success') {
        $submitResult = ['success' => true, 'message' => 'Your application has been submitted successfully!'];
    } elseif ($_GET['submitted'] === 'error') {
        $submitResult = ['success' => false, 'error' => $_GET['msg'] ?? 'Submission failed'];
    }
}

/**
 * Send agent application notification email
 */
function sendAgentApplicationNotification($applicationData) {
    // This is a placeholder - integrate with your email service
    // You can use services like SendGrid, AWS SES, or SMTP
    
    $to = 'partnerships@scape-travel.com';
    $subject = 'New Partner Agent Application - ' . $applicationData['name'];
    
    $message = "
New Partner Agent Application Received

Name: {$applicationData['name']}
Email: {$applicationData['email']}
Company: {$applicationData['company']}
Role: {$applicationData['role']}

Message:
{$applicationData['message']}

Submitted: {$applicationData['submitted_at']}
IP Address: {$applicationData['ip_address']}

Please review this application and follow up with the applicant.
";
    
    $headers = [
        'From: noreply@scape-travel.com',
        'Reply-To: ' . $applicationData['email'],
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    // For production, use a proper email service
    // return mail($to, $subject, $message, implode("\r\n", $headers));
    
    // For now, just log that we would send an email
    $logger = ScapeLogger::getInstance();
    $logger->info('Would send agent application email', [
        'to' => $to,
        'subject' => $subject,
        'applicant' => $applicationData['email']
    ]);
    
    return true; // Simulate successful email sending
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Partner Agent Application - S-Cape Travel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .application-steps {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤝 Partner Agent Application</h1>
        
        <?php if ($submitResult): ?>
            <?php if ($submitResult['success']): ?>
                <div class="alert alert-success">
                    <strong>✅ Application Submitted!</strong><br>
                    <?php echo htmlspecialchars($submitResult['message']); ?>
                </div>
                <div style="text-align: center; margin: 30px 0;">
                    <p><strong>What happens next?</strong></p>
                    <ol style="text-align: left; display: inline-block;">
                        <li>Our partnerships team will review your application</li>
                        <li>We'll contact you within 2-3 business days</li>
                        <li>If approved, you'll receive an invitation to our agent portal</li>
                        <li>Complete the partner agreement process</li>
                        <li>Start managing your customers through our platform</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>❌ Submission Error:</strong><br>
                    <?php echo htmlspecialchars($submitResult['error']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="application-steps">
            <h3>📋 Become a S-Cape Travel Partner Agent</h3>
            <p><strong>Partner agents get access to:</strong></p>
            <ul>
                <li>🏢 Dedicated B2B portal with full customer management</li>
                <li>📄 Document management for all your customers</li>
                <li>💼 Commission tracking and reporting</li>
                <li>🎯 Priority support and training</li>
                <li>🌐 Access to exclusive travel deals and packages</li>
            </ul>
        </div>
        
        <?php if (!$submitResult || !$submitResult['success']): ?>
        <div class="card">
            <h3>📝 Application Form</h3>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <?php echo csrf_input(); ?>
                
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Your full name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="your.email@company.com">
                </div>
                
                <div class="form-group">
                    <label for="company">Company Name <span class="required">*</span></label>
                    <input type="text" id="company" name="company" required 
                           value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>"
                           placeholder="Your travel agency or company name">
                </div>
                
                <div class="form-group">
                    <label for="role">Your Role <span class="required">*</span></label>
                    <select id="role" name="role" required>
                        <option value="">Select your role...</option>
                        <option value="Travel Agent" <?php echo ($_POST['role'] ?? '') === 'Travel Agent' ? 'selected' : ''; ?>>Travel Agent</option>
                        <option value="Agency Owner" <?php echo ($_POST['role'] ?? '') === 'Agency Owner' ? 'selected' : ''; ?>>Agency Owner</option>
                        <option value="Manager" <?php echo ($_POST['role'] ?? '') === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="Sales Representative" <?php echo ($_POST['role'] ?? '') === 'Sales Representative' ? 'selected' : ''; ?>>Sales Representative</option>
                        <option value="Other" <?php echo ($_POST['role'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Tell us about your business <span class="required">*</span></label>
                    <textarea id="message" name="message" required 
                              placeholder="Please include:&#10;• Your company's focus (corporate travel, leisure, etc.)&#10;• Years of experience in travel industry&#10;• Current customer base size&#10;• Why you want to partner with S-Cape Travel&#10;• Any relevant certifications or memberships"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">📤 Submit Application</button>
                </div>
                
                <p style="font-size: 12px; color: #666; margin-top: 20px;">
                    <strong>Privacy Notice:</strong> Your information will be used solely for evaluating your partner application and will not be shared with third parties.
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <p><a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Login</a></p>
        </div>
        
        <div class="footer">
            <p>Secured by Microsoft Identity Platform | Following Woodgrove Security Patterns</p>
        </div>
    </div>
</body>
</html>
