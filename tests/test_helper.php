<?php
/**
 * Test Helper Functions
 * Common utilities for all test files
 */

/**
 * Disable Xdebug for test execution to prevent connection timeouts
 */
function disable_xdebug_for_tests() {
    // Check environment variable
    $env = $_ENV ?? getenv();
    $disable_xdebug = ($env['DISABLE_XDEBUG_IN_TESTS'] ?? 'true') === 'true';
    
    if (!$disable_xdebug || !extension_loaded('xdebug')) {
        return false;
    }
    
    // Disable remote debugging
    ini_set('xdebug.remote_enable', 0);
    ini_set('xdebug.remote_autostart', 0);
    
    // For Xdebug 3.x
    ini_set('xdebug.start_with_request', 'no');
    ini_set('xdebug.mode', 'off');
    
    // Disable step debugging
    if (function_exists('xdebug_disable')) {
        xdebug_disable();
    }
    
    // Suppress connection messages
    ini_set('xdebug.client_host', '');
    ini_set('xdebug.client_port', 0);
    
    return true;
}

/**
 * Setup test environment with proper configuration
 */
function setup_test_environment() {
    // Disable Xdebug
    $xdebug_disabled = disable_xdebug_for_tests();
    
    // Set error reporting for tests
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
    
    // Return status
    return [
        'xdebug_disabled' => $xdebug_disabled,
        'error_reporting_set' => true
    ];
}

/**
 * Check if we're in a test environment
 */
function is_test_environment() {
    return strpos($_SERVER['SCRIPT_NAME'] ?? '', '/tests/') !== false ||
           strpos($_SERVER['PHP_SELF'] ?? '', '/tests/') !== false;
}
?>
