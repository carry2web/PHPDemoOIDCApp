<?php
// File: apply_agent_secure.php
// Secure agent application form with comprehensive validation

require_once __DIR__ . '/lib/oidc.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/security_helper.php';
require_once __DIR__ . '/lib/email_helper.php';

$logger = ScapeLogger::getInstance();
$security = SecurityHelper::getInstance();

start_azure_safe_session();

$successMessage = '';
$errorMessage = '';
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitKey = 'agent_application_' . $ip;
    
    if (!$security->checkRateLimit($rateLimitKey, 3, 3600)) { // 3 applications per hour
        $errorMessage = 'Too many applications from your IP address. Please try again later.';
        $logger->security('Agent application rate limit exceeded', ['ip' => $ip]);
    } else {
        // CSRF protection
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$security->validateCSRFToken($csrfToken)) {
            $errorMessage = 'Security token validation failed. Please refresh the page and try again.';
            $logger->security('CSRF validation failed for agent application', ['ip' => $ip]);
        } else {
            // Validate all inputs
            $validationErrors = [];
            
            // Validate name
            $nameValidation = $security->validateName($_POST['name'] ?? '');
            if (!$nameValidation['valid']) {
                $validationErrors[] = $nameValidation['error'];
            } else {
                $formData['name'] = $nameValidation['value'];
            }
            
            // Validate email
            $emailValidation = $security->validateEmail($_POST['email'] ?? '');
            if (!$emailValidation['valid']) {
                $validationErrors[] = $emailValidation['error'];
            } else {
                $formData['email'] = $emailValidation['value'];
            }
            
            // Validate company
            $companyValidation = $security->validateCompany($_POST['company'] ?? '');
            if (!$companyValidation['valid']) {
                $validationErrors[] = $companyValidation['error'];
            } else {
                $formData['company'] = $companyValidation['value'];
            }
            
            // Validate reason
            $reasonValidation = $security->validateReason($_POST['reason'] ?? '');
            if (!$reasonValidation['valid']) {
                $validationErrors[] = $reasonValidation['error'];
            } else {
                $formData['reason'] = $reasonValidation['value'];
            }
            
            // Additional validation: phone number (optional)
            if (!empty($_POST['phone'])) {
                $phone = trim($_POST['phone']);
                if (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone)) {
                    $validationErrors[] = 'Invalid phone number format';
                } else {
                    $formData['phone'] = $phone;
                }
            }
            
            // Additional validation: website (optional)
            if (!empty($_POST['website'])) {
                $website = filter_var($_POST['website'], FILTER_VALIDATE_URL);
                if ($website === false) {
                    $validationErrors[] = 'Invalid website URL';
                } else {
                    $formData['website'] = $website;
                }
            }
            
            if (!empty($validationErrors)) {
                $errorMessage = 'Please correct the following errors: ' . implode(', ', $validationErrors);
            } else {
                // Save application to file
                $application = [
                    'name' => $formData['name'],
                    'email' => $formData['email'],
                    'company' => $formData['company'],
                    'reason' => $formData['reason'],
                    'phone' => $formData['phone'] ?? '',
                    'website' => $formData['website'] ?? '',
                    'status' => 'pending',
                    'submitted_at' => date('Y-m-d H:i:s'),
                    'ip_address' => $ip,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ];
                
                // Save to file
                $dataDir = __DIR__ . '/data';
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                
                $filename = $dataDir . '/agent_applications.json';
                $applications = [];
                
                if (file_exists($filename)) {
                    $data = file_get_contents($filename);
                    $applications = json_decode($data, true) ?? [];
                    
                    // Handle old format (single application) vs new format (array)
                    if (isset($applications['email']) && !isset($applications[0])) {
                        $applications = [$applications];
                    }
                }
                
                // Check for duplicate application
                $duplicate = false;
                foreach ($applications as $app) {
                    if ($app['email'] === $formData['email']) {
                        $duplicate = true;
                        break;
                    }
                }
                
                if ($duplicate) {
                    $errorMessage = 'An application with this email address already exists.';
                } else {
                    $applications[] = $application;
                    
                    if (file_put_contents($filename, json_encode($applications, JSON_PRETTY_PRINT))) {
                        $successMessage = 'Your agent application has been submitted successfully! You will receive an email confirmation shortly.';
                        
                        $logger->info('Agent application submitted', [
                            'name' => $formData['name'],
                            'email' => $formData['email'],
                            'company' => $formData['company'],
                            'ip' => $ip
                        ]);
                        
                        // Send notification emails
                        try {
                            notify_agent_application($formData['name'], $formData['email'], $formData['company'], $formData['reason']);
                        } catch (Exception $e) {
                            $logger->error('Failed to send agent application notification', [
                                'error' => $e->getMessage(),
                                'email' => $formData['email']
                            ]);
                        }
                        
                        // Clear form data on success
                        $formData = [];
                    } else {
                        $errorMessage = 'Failed to save application. Please try again.';
                        $logger->error('Failed to save agent application', ['email' => $formData['email']]);
                    }
                }
            }
        }
    }
}

$logger->info('Agent application page accessed', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Partner Agent Application - S-Cape Travel</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-group {
            margin-bottom: 1.5em;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 0.5em;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75em;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .required {
            color: #dc3545;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1em;
            border-radius: 4px;
            margin: 1em 0;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1em;
            border-radius: 4px;
            margin: 1em 0;
            border: 1px solid #f5c6cb;
        }
        .security-notice {
            background: #e7f3ff;
            color: #004085;
            padding: 1em;
            border-radius: 4px;
            margin: 1em 0;
            border: 1px solid #b8daff;
            font-size: 0.9em;
        }
        .form-help {
            font-size: 0.85em;
            color: #666;
            margin-top: 0.25em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤝 Partner Agent Application</h1>
        
        <div class="info-box">
            <h3>Become a S-Cape Travel Partner Agent</h3>
            <p>Join our exclusive partner network and gain access to:</p>
            <ul>
                <li>Advanced booking and management tools</li>
                <li>Dedicated agent support and training</li>
                <li>Competitive commission structures</li>
                <li>Priority customer service</li>
                <li>Marketing and promotional support</li>
            </ul>
        </div>
        
        <?php if (!empty($successMessage)): ?>
            <div class="success-message">
                ✅ <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                ❌ <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <div class="security-notice">
            🔒 <strong>Security Notice:</strong> This form is protected against spam and malicious submissions. 
            All applications are reviewed manually within 2-3 business days.
        </div>
        
        <div class="card">
            <h2>Application Form</h2>
            <form method="post">
                <?= csrf_input() ?>
                
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           required 
                           maxlength="100"
                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                           placeholder="Enter your full name">
                    <div class="form-help">Your legal name as it appears on official documents</div>
                </div>
                
                <div class="form-group">
                    <label for="email">Business Email <span class="required">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           maxlength="254"
                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                           placeholder="your.name@company.com">
                    <div class="form-help">Please use your business email address for verification</div>
                </div>
                
                <div class="form-group">
                    <label for="company">Company Name <span class="required">*</span></label>
                    <input type="text" 
                           id="company" 
                           name="company" 
                           required 
                           maxlength="200"
                           value="<?= htmlspecialchars($formData['company'] ?? '') ?>"
                           placeholder="Your company or agency name">
                    <div class="form-help">Official registered business name</div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           maxlength="20"
                           value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                           placeholder="+1 (555) 123-4567">
                    <div class="form-help">Optional: Business phone number for priority support</div>
                </div>
                
                <div class="form-group">
                    <label for="website">Company Website</label>
                    <input type="url" 
                           id="website" 
                           name="website" 
                           maxlength="255"
                           value="<?= htmlspecialchars($formData['website'] ?? '') ?>"
                           placeholder="https://www.yourcompany.com">
                    <div class="form-help">Optional: Your business website for verification</div>
                </div>
                
                <div class="form-group">
                    <label for="reason">Business Justification <span class="required">*</span></label>
                    <textarea id="reason" 
                              name="reason" 
                              required 
                              maxlength="2000"
                              placeholder="Please describe your business, experience in travel industry, target market, and why you want to partner with S-Cape Travel..."><?= htmlspecialchars($formData['reason'] ?? '') ?></textarea>
                    <div class="form-help">Minimum 10 characters. Please provide detailed information about your business and partnership goals.</div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1em; padding: 1em;">
                        🚀 Submit Application
                    </button>
                </div>
            </form>
        </div>
        
        <div class="info-box" style="margin-top: 2em;">
            <h3>📋 Application Process</h3>
            <ol>
                <li><strong>Submit Application:</strong> Complete this secure form with your business details</li>
                <li><strong>Review Process:</strong> Our team will review your application within 2-3 business days</li>
                <li><strong>Verification:</strong> We may contact you for additional information or documentation</li>
                <li><strong>Approval:</strong> Approved partners receive a B2B invitation to join our internal tenant</li>
                <li><strong>Onboarding:</strong> Access to agent portal, training materials, and support resources</li>
            </ol>
        </div>
        
        <p style="text-align: center; margin-top: 2em;">
            <a href="index.php" class="btn btn-secondary">← Back to Login</a>
        </p>
        
        <div class="footer">
            <p>🔐 Secured by Microsoft Identity Platform | Enterprise Security Standards</p>
            <p><small>All applications are processed in accordance with our privacy policy and data protection standards.</small></p>
        </div>
    </div>
</body>
</html>
