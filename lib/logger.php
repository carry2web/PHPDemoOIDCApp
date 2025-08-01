<?php
// File: lib/logger.php
// Comprehensive logging system for Azure Web App environment
// Handles file logging, Azure logging, and debug information

class ScapeLogger {
    private static $instance = null;
    private $logLevel;
    private $logFilePath;
    private $errorLogPath;
    private $debugLogPath;
    private $enableFileLogging;
    private $enableAzureLogging;
    
    const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    private function __construct() {
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'INFO';
        $this->logFilePath = $_ENV['LOG_FILE_PATH'] ?? '/home/LogFiles/application.log';
        $this->errorLogPath = $_ENV['ERROR_LOG_PATH'] ?? '/home/LogFiles/error.log';
        $this->debugLogPath = $_ENV['DEBUG_LOG_PATH'] ?? '/home/LogFiles/debug.log';
        $this->enableFileLogging = ($_ENV['ENABLE_FILE_LOGGING'] ?? 'true') === 'true';
        $this->enableAzureLogging = ($_ENV['ENABLE_AZURE_LOGGING'] ?? 'true') === 'true';
        
        // Ensure log directories exist
        $this->ensureLogDirectories();
        
        // Set up PHP error handling for Azure
        $this->setupErrorHandling();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureLogDirectories() {
        $logDir = dirname($this->logFilePath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // For local development, use a local logs directory
        if (!is_writable($logDir)) {
            $localLogDir = __DIR__ . '/../logs';
            if (!is_dir($localLogDir)) {
                mkdir($localLogDir, 0755, true);
            }
            $this->logFilePath = $localLogDir . '/application.log';
            $this->errorLogPath = $localLogDir . '/error.log';
            $this->debugLogPath = $localLogDir . '/debug.log';
        }
    }
    
    private function setupErrorHandling() {
        // Custom error handler for Azure Web Apps
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);
        
        // Configure PHP error logging
        ini_set('log_errors', 1);
        ini_set('error_log', $this->errorLogPath);
        
        // Set error reporting based on debug mode
        if (($_ENV['DEBUG'] ?? 'false') === 'true') {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        }
    }
    
    public function errorHandler($severity, $message, $file, $line) {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'ERROR',
            E_NOTICE => 'INFO',
            E_CORE_ERROR => 'ERROR',
            E_CORE_WARNING => 'WARNING',
            E_COMPILE_ERROR => 'ERROR',
            E_COMPILE_WARNING => 'WARNING',
            E_USER_ERROR => 'ERROR',
            E_USER_WARNING => 'WARNING',
            E_USER_NOTICE => 'INFO',
            E_STRICT => 'INFO',
            E_RECOVERABLE_ERROR => 'ERROR',
            E_DEPRECATED => 'WARNING',
            E_USER_DEPRECATED => 'WARNING'
        ];
        
        $level = $errorTypes[$severity] ?? 'ERROR';
        $this->log($level, "PHP {$level}: {$message}", [
            'file' => $file,
            'line' => $line,
            'severity' => $severity
        ]);
        
        return true; // Don't execute PHP internal error handler
    }
    
    public function exceptionHandler($exception) {
        $this->log('ERROR', 'Uncaught Exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    public function shutdownHandler() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->log('CRITICAL', 'Fatal Error: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ]);
        }
    }
    
    public function log($level, $message, $context = []) {
        // Check if we should log this level
        if (self::LEVELS[$level] < self::LEVELS[$this->logLevel]) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $requestId = $this->getRequestId();
        $userId = $this->getCurrentUserId();
        
        // Build log entry
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'request_id' => $requestId,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'context' => $context
        ];
        
        // Format for file logging
        $formattedMessage = sprintf(
            "[%s] %s %s: %s | RequestID: %s | User: %s | IP: %s\n",
            $timestamp,
            $level,
            $requestId,
            $message,
            $requestId,
            $userId,
            $logEntry['ip']
        );
        
        // Add context if present
        if (!empty($context)) {
            $formattedMessage .= "Context: " . json_encode($context, JSON_UNESCAPED_SLASHES) . "\n";
        }
        
        // File logging
        if ($this->enableFileLogging) {
            $this->writeToFile($level, $formattedMessage);
        }
        
        // Azure Application Insights logging (if available)
        if ($this->enableAzureLogging) {
            $this->writeToAzure($logEntry);
        }
        
        // Security event logging for specific events
        if (strpos($message, 'SECURITY:') === 0) {
            $this->logSecurityEvent($logEntry);
        }
    }
    
    private function writeToFile($level, $message) {
        $logFile = $this->logFilePath;
        
        // Use specific files for different log levels
        if ($level === 'ERROR' || $level === 'CRITICAL') {
            $logFile = $this->errorLogPath;
        } elseif ($level === 'DEBUG') {
            $logFile = $this->debugLogPath;
        }
        
        // Ensure file is writable
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0644);
        }
        
        error_log($message, 3, $logFile);
    }
    
    private function writeToAzure($logEntry) {
        // Azure Web Apps automatically captures error_log output
        // We can also use Application Insights if configured
        
        // Send to Azure monitoring (this will appear in Azure Log Stream)
        error_log(json_encode($logEntry), 0);
        
        // If Application Insights is configured, send structured data
        if (function_exists('ApplicationInsights\\Telemetry\\Trace')) {
            // Application Insights integration would go here
            // This requires the Application Insights PHP SDK
        }
    }
    
    private function logSecurityEvent($logEntry) {
        // Additional security logging
        $securityLogPath = str_replace('application.log', 'security.log', $this->logFilePath);
        $securityMessage = sprintf(
            "[SECURITY] %s: %s | User: %s | IP: %s | Context: %s\n",
            $logEntry['timestamp'],
            $logEntry['message'],
            $logEntry['user_id'],
            $logEntry['ip'],
            json_encode($logEntry['context'])
        );
        
        error_log($securityMessage, 3, $securityLogPath);
    }
    
    private function getRequestId() {
        // Generate or get request ID for tracing
        if (!isset($_SERVER['HTTP_X_REQUEST_ID'])) {
            $_SERVER['HTTP_X_REQUEST_ID'] = uniqid('req_', true);
        }
        return $_SERVER['HTTP_X_REQUEST_ID'];
    }
    
    private function getCurrentUserId() {
        // Get current user ID from session
        if (isset($_SESSION['user_info']['sub'])) {
            return $_SESSION['user_info']['sub'];
        } elseif (isset($_SESSION['user_info']['email'])) {
            return $_SESSION['user_info']['email'];
        }
        return 'anonymous';
    }
    
    // Convenience methods
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log('CRITICAL', $message, $context);
    }
    
    public function security($message, $context = []) {
        $this->log('WARNING', 'SECURITY: ' . $message, $context);
    }
    
    // Azure-specific debugging
    public function azureDebug($message, $context = []) {
        $azureContext = array_merge($context, [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
        
        $this->debug('AZURE: ' . $message, $azureContext);
    }
    
    // Performance monitoring
    public function performance($action, $duration, $context = []) {
        $this->info("PERFORMANCE: {$action} completed in {$duration}ms", $context);
    }
    
    // Database query logging
    public function query($sql, $duration = null, $params = []) {
        $context = ['sql' => $sql, 'params' => $params];
        if ($duration !== null) {
            $context['duration_ms'] = $duration;
        }
        $this->debug('DATABASE: Query executed', $context);
    }
}

// Global logging functions for convenience
function log_debug($message, $context = []) {
    ScapeLogger::getInstance()->debug($message, $context);
}

function log_info($message, $context = []) {
    ScapeLogger::getInstance()->info($message, $context);
}

function log_warning($message, $context = []) {
    ScapeLogger::getInstance()->warning($message, $context);
}

function log_error($message, $context = []) {
    ScapeLogger::getInstance()->error($message, $context);
}

function log_critical($message, $context = []) {
    ScapeLogger::getInstance()->critical($message, $context);
}

function log_security($message, $context = []) {
    ScapeLogger::getInstance()->security($message, $context);
}

function log_azure_debug($message, $context = []) {
    ScapeLogger::getInstance()->azureDebug($message, $context);
}

function log_performance($action, $duration, $context = []) {
    ScapeLogger::getInstance()->performance($action, $duration, $context);
}
