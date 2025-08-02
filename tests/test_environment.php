<?php
/**
 * Test Configuration and Environment Setup
 * Handles Xdebug management and test environment setup
 */

class TestEnvironment {
    private static $instance = null;
    private $config = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->loadConfig();
        $this->setupEnvironment();
    }
    
    private function loadConfig() {
        // Load environment variables
        $env = $_ENV ?? [];
        
        $this->config = [
            'disable_xdebug' => ($env['DISABLE_XDEBUG_IN_TESTS'] ?? 'true') === 'true',
            'test_mode' => ($env['TEST_MODE'] ?? 'false') === 'true',
            'log_level' => $env['LOG_LEVEL'] ?? 'INFO',
            'show_debug_info' => ($env['DEBUG'] ?? 'false') === 'true'
        ];
    }
    
    private function setupEnvironment() {
        // Disable Xdebug if configured to do so
        if ($this->config['disable_xdebug']) {
            $this->disableXdebug();
        }
        
        // Set error reporting for tests
        if ($this->isTestEnvironment()) {
            error_reporting(E_ALL & ~E_NOTICE);
            ini_set('display_errors', 0);
        }
    }
    
    public function disableXdebug() {
        if (!extension_loaded('xdebug')) {
            return false;
        }
        
        // Disable all Xdebug features that might cause connection attempts
        $xdebug_settings = [
            'xdebug.remote_enable' => 0,
            'xdebug.remote_autostart' => 0,
            'xdebug.start_with_request' => 'no',
            'xdebug.mode' => 'off',
            'xdebug.client_host' => '',
            'xdebug.client_port' => 0,
        ];
        
        foreach ($xdebug_settings as $setting => $value) {
            @ini_set($setting, $value);
        }
        
        // Call xdebug_disable if available
        if (function_exists('xdebug_disable')) {
            @xdebug_disable();
        }
        
        return true;
    }
    
    public function isTestEnvironment() {
        $script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        return strpos($script_name, '/tests/') !== false || 
               strpos(basename($script_name), 'test') === 0;
    }
    
    public function getConfig($key = null) {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }
    
    public function showStatus() {
        echo "🔧 Test Environment Status\n";
        echo "=========================\n";
        echo "Xdebug Extension: " . (extension_loaded('xdebug') ? '✅ Loaded' : '❌ Not loaded') . "\n";
        echo "Xdebug Disabled: " . ($this->config['disable_xdebug'] ? '✅ Yes' : '❌ No') . "\n";
        echo "Test Mode: " . ($this->config['test_mode'] ? '✅ Enabled' : '❌ Disabled') . "\n";
        echo "Is Test Environment: " . ($this->isTestEnvironment() ? '✅ Yes' : '❌ No') . "\n";
        echo "\n";
    }
}

// Auto-initialize when included
if (!defined('TEST_ENV_INITIALIZED')) {
    define('TEST_ENV_INITIALIZED', true);
    TestEnvironment::getInstance();
}
?>
