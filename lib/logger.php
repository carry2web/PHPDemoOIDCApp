<?php
// File: lib/logger.php
// Modern logging using Monolog - industry standard PHP logging library

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;

class ScapeLogger {
    private static $instance = null;
    private $logger;
    private $errorLogger;
    private $debugLogger;
    
    private function __construct() {
        $this->setupLoggers();
        $this->setupErrorHandling();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function setupLoggers() {
        // Use /tmp for Azure Web Apps compatibility, fallback to data directory
        $logDir = '/tmp';
        if (!is_writable($logDir)) {
            $logDir = __DIR__ . '/../data';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
        
        // Main application logger
        $this->logger = new Logger('scape_app');
        
        // Use rotating file handler to prevent huge log files
        $mainHandler = new RotatingFileHandler($logDir . '/app.log', 30, Logger::DEBUG);
        $mainHandler->setFormatter(new LineFormatter(
            "[%datetime%] %level_name% %channel%: %message% %context%\n",
            'Y-m-d H:i:s'
        ));
        
        $this->logger->pushHandler($mainHandler);
        
        // Error logger for PHP errors/exceptions
        $this->errorLogger = new Logger('php_errors');
        $errorHandler = new RotatingFileHandler($logDir . '/error.log', 30, Logger::WARNING);
        $errorHandler->setFormatter(new LineFormatter(
            "[%datetime%] %level_name%: %message% %context%\n",
            'Y-m-d H:i:s'
        ));
        $this->errorLogger->pushHandler($errorHandler);
        
        // Debug logger for detailed debugging
        $this->debugLogger = new Logger('debug');
        $debugHandler = new RotatingFileHandler($logDir . '/debug.log', 7, Logger::DEBUG);
        $debugHandler->setFormatter(new LineFormatter(
            "[%datetime%] %level_name%: %message% %context%\n",
            'Y-m-d H:i:s'
        ));
        $this->debugLogger->pushHandler($debugHandler);
        
        // Add processors for additional context
        $webProcessor = new WebProcessor();
        $memoryProcessor = new MemoryUsageProcessor();
        
        $this->logger->pushProcessor($webProcessor);
        $this->logger->pushProcessor($memoryProcessor);
        $this->logger->pushProcessor(function ($record) {
            $record['extra']['request_id'] = $this->getRequestId();
            $record['extra']['user_id'] = $this->getCurrentUserId();
            return $record;
        });
        
        // In debug mode, also log to Azure App Service logs
        if (($_ENV['DEBUG'] ?? 'false') === 'true') {
            $azureHandler = new StreamHandler('php://stderr', Logger::DEBUG);
            $azureHandler->setFormatter(new LineFormatter(
                "%level_name%: %message% | Context: %context%\n"
            ));
            $this->logger->pushHandler($azureHandler);
        }
    }
    
    private function setupErrorHandling() {
        // Custom error handler
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);
        
        // Configure PHP error logging
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/../data/php_system_errors.log');
        
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
            E_ERROR => 'error',
            E_WARNING => 'warning',
            E_PARSE => 'error',
            E_NOTICE => 'info',
            E_CORE_ERROR => 'error',
            E_CORE_WARNING => 'warning',
            E_COMPILE_ERROR => 'error',
            E_COMPILE_WARNING => 'warning',
            E_USER_ERROR => 'error',
            E_USER_WARNING => 'warning',
            E_USER_NOTICE => 'info',
            E_STRICT => 'info',
            E_RECOVERABLE_ERROR => 'error',
            E_DEPRECATED => 'warning',
            E_USER_DEPRECATED => 'warning'
        ];
        
        $level = $errorTypes[$severity] ?? 'error';
        
        $this->errorLogger->$level("PHP Error: $message", [
            'file' => basename($file),
            'line' => $line,
            'severity' => $severity,
            'full_path' => $file
        ]);
        
        return true;
    }
    
    public function exceptionHandler($exception) {
        $this->errorLogger->error('Uncaught Exception: ' . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'full_path' => $exception->getFile()
        ]);
    }
    
    public function shutdownHandler() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->errorLogger->critical('Fatal Error: ' . $error['message'], [
                'file' => basename($error['file']),
                'line' => $error['line'],
                'type' => $error['type'],
                'full_path' => $error['file']
            ]);
        }
    }
    
    // Convenience methods for different log levels
    public function debug($message, array $context = []) {
        $this->debugLogger->debug($message, $context);
        $this->logger->debug($message, $context);
    }
    
    public function info($message, array $context = []) {
        $this->logger->info($message, $context);
    }
    
    public function warning($message, array $context = []) {
        $this->logger->warning($message, $context);
    }
    
    public function error($message, array $context = []) {
        $this->errorLogger->error($message, $context);
        $this->logger->error($message, $context);
    }
    
    public function critical($message, array $context = []) {
        $this->errorLogger->critical($message, $context);
        $this->logger->critical($message, $context);
    }
    
    private function getRequestId() {
        static $requestId = null;
        if ($requestId === null) {
            $requestId = 'req_' . uniqid();
        }
        return $requestId;
    }
    
    private function getCurrentUserId() {
        return $_SESSION['email'] ?? 'anonymous';
    }
}
