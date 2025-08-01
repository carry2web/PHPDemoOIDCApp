<?php
// File: lib/security_helper.php
// Security utilities for CSRF protection, input validation, and file upload security

require_once __DIR__ . '/logger.php';

class SecurityHelper {
    private static $instance = null;
    private $logger;
    
    private function __construct() {
        $this->logger = ScapeLogger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * CSRF Protection
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $this->logger->debug('New CSRF token generated');
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            $this->logger->warning('CSRF validation failed - no session token');
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        
        if (!$valid) {
            $this->logger->security('CSRF token validation failed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } else {
            $this->logger->debug('CSRF token validated successfully');
        }
        
        return $valid;
    }
    
    public function getCSRFInput() {
        $token = $this->generateCSRFToken();
        return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($token) . "'>";
    }
    
    /**
     * Input Validation
     */
    public function validateEmail($email) {
        if (empty($email)) {
            return ['valid' => false, 'error' => 'Email is required'];
        }
        
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            return ['valid' => false, 'error' => 'Invalid email format'];
        }
        
        // Additional security: check for suspicious patterns
        if (strlen($email) > 254) {
            return ['valid' => false, 'error' => 'Email too long'];
        }
        
        return ['valid' => true, 'value' => $email];
    }
    
    public function validateName($name) {
        if (empty($name)) {
            return ['valid' => false, 'error' => 'Name is required'];
        }
        
        $name = trim($name);
        
        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['valid' => false, 'error' => 'Name must be between 2 and 100 characters'];
        }
        
        // Allow letters, spaces, hyphens, apostrophes
        if (!preg_match("/^[a-zA-Z\s\-'\.]+$/u", $name)) {
            return ['valid' => false, 'error' => 'Name contains invalid characters'];
        }
        
        return ['valid' => true, 'value' => $name];
    }
    
    public function validateCompany($company) {
        if (empty($company)) {
            return ['valid' => false, 'error' => 'Company name is required'];
        }
        
        $company = trim($company);
        
        if (strlen($company) < 2 || strlen($company) > 200) {
            return ['valid' => false, 'error' => 'Company name must be between 2 and 200 characters'];
        }
        
        // Allow letters, numbers, spaces, common business characters
        if (!preg_match("/^[a-zA-Z0-9\s\-'\.&,()]+$/u", $company)) {
            return ['valid' => false, 'error' => 'Company name contains invalid characters'];
        }
        
        return ['valid' => true, 'value' => $company];
    }
    
    public function validateReason($reason) {
        if (empty($reason)) {
            return ['valid' => false, 'error' => 'Business reason is required'];
        }
        
        $reason = trim($reason);
        
        if (strlen($reason) < 10 || strlen($reason) > 2000) {
            return ['valid' => false, 'error' => 'Business reason must be between 10 and 2000 characters'];
        }
        
        // Basic XSS prevention - remove potential script tags
        $reason = strip_tags($reason);
        
        return ['valid' => true, 'value' => $reason];
    }
    
    public function validateAction($action, $allowedActions = []) {
        if (empty($action)) {
            return ['valid' => false, 'error' => 'Action is required'];
        }
        
        $action = filter_var($action, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        
        if (!in_array($action, $allowedActions)) {
            $this->logger->security('Invalid action attempted', [
                'action' => $action,
                'allowed' => $allowedActions,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return ['valid' => false, 'error' => 'Invalid action'];
        }
        
        return ['valid' => true, 'value' => $action];
    }
    
    /**
     * File Upload Security
     */
    public function validateFileUpload($file, $options = []) {
        // Default options
        $defaultOptions = [
            'maxSize' => 10 * 1024 * 1024, // 10MB
            'allowedMimeTypes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
            'allowedExtensions' => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'doc', 'docx'],
            'requireExtension' => true
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            $error = $errorMessages[$file['error']] ?? 'Unknown upload error';
            $this->logger->warning('File upload error', ['error' => $error, 'code' => $file['error']]);
            return ['valid' => false, 'error' => $error];
        }
        
        // Check file size
        if ($file['size'] > $options['maxSize']) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed size'];
        }
        
        if ($file['size'] === 0) {
            return ['valid' => false, 'error' => 'File is empty'];
        }
        
        // Validate filename
        $filename = $file['name'];
        if (empty($filename)) {
            return ['valid' => false, 'error' => 'Invalid filename'];
        }
        
        // Check for dangerous filenames
        if (preg_match('/[<>:"|?*]/', $filename) || strpos($filename, '..') !== false) {
            return ['valid' => false, 'error' => 'Filename contains invalid characters'];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($options['requireExtension'] && empty($extension)) {
            return ['valid' => false, 'error' => 'File must have an extension'];
        }
        
        if (!empty($extension) && !in_array($extension, $options['allowedExtensions'])) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        // Validate MIME type using finfo
        if (!file_exists($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Uploaded file not found'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            $this->logger->error('Failed to open finfo resource');
            return ['valid' => false, 'error' => 'Unable to validate file type'];
        }
        
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mimeType === false) {
            return ['valid' => false, 'error' => 'Unable to determine file type'];
        }
        
        if (!in_array($mimeType, $options['allowedMimeTypes'])) {
            $this->logger->security('Blocked file upload with disallowed MIME type', [
                'filename' => $filename,
                'mime_type' => $mimeType,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        // Additional security: scan for embedded executables (basic check)
        $fileContent = file_get_contents($file['tmp_name']);
        if ($fileContent === false) {
            return ['valid' => false, 'error' => 'Unable to read file content'];
        }
        
        // Check for executable signatures
        $dangerousSignatures = [
            "\x4d\x5a", // PE executable
            "\x7f\x45\x4c\x46", // ELF executable
            "\xfe\xed\xfa", // Mach-O executable
            "#!/bin/", // Shell script
            "<?php", // PHP script
            "<script" // JavaScript
        ];
        
        foreach ($dangerousSignatures as $signature) {
            if (strpos($fileContent, $signature) === 0 || strpos($fileContent, $signature) !== false) {
                $this->logger->security('Blocked file upload with dangerous content', [
                    'filename' => $filename,
                    'signature_detected' => bin2hex($signature),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return ['valid' => false, 'error' => 'File contains potentially dangerous content'];
            }
        }
        
        // Generate safe filename
        $safeFilename = $this->generateSafeFilename($filename);
        
        $this->logger->info('File upload validation successful', [
            'original_filename' => $filename,
            'safe_filename' => $safeFilename,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ]);
        
        return [
            'valid' => true,
            'filename' => $safeFilename,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name']
        ];
    }
    
    private function generateSafeFilename($filename) {
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 95 - strlen($extension)) . '.' . $extension;
        }
        
        // Add timestamp prefix to prevent conflicts
        $timestamp = time();
        return $timestamp . '_' . $filename;
    }
    
    /**
     * Rate Limiting
     */
    public function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $key = 'rate_limit_' . $identifier;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window has passed
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
            return true;
        }
        
        // Check if rate limit exceeded
        if ($data['count'] >= $maxAttempts) {
            $this->logger->security('Rate limit exceeded', [
                'identifier' => $identifier,
                'attempts' => $data['count'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
}

// Global helper functions
function csrf_token() {
    return SecurityHelper::getInstance()->generateCSRFToken();
}

function csrf_input() {
    return SecurityHelper::getInstance()->getCSRFInput();
}

function validate_csrf($token) {
    return SecurityHelper::getInstance()->validateCSRFToken($token);
}

function validate_file_upload($file, $options = []) {
    return SecurityHelper::getInstance()->validateFileUpload($file, $options);
}

function check_rate_limit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    return SecurityHelper::getInstance()->checkRateLimit($identifier, $maxAttempts, $timeWindow);
}
