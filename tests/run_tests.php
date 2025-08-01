<?php
/**
 * Command Line Test Runner
 * Simple CLI interface for running authentication tests
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AuthenticationTestSuite.php';

// Prevent HTML output in CLI
if (php_sapi_name() === 'cli') {
    echo "🧪 OIDC Authentication Test Suite - CLI Runner\n";
    echo "================================================\n\n";
}

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parse command line arguments
$testType = $argv[1] ?? 'all';

try {
    $suite = new AuthenticationTestSuite();
    
    switch($testType) {
        case 'config':
            echo "Running Configuration Tests...\n";
            $suite->testConfigurationOnly();
            break;
            
        case 'oidc':
            echo "Running OIDC Client Tests...\n";
            $suite->testOidcOnly();
            break;
            
        case 'session':
            echo "Running Session Tests...\n";
            $suite->testSessionOnly();
            break;
            
        case 'all':
        default:
            echo "Running All Tests...\n";
            $suite->runAllTests();
            break;
    }
    
    echo "\n✅ Test execution completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test execution failed: " . $e->getMessage() . "\n";
    exit(1);
}
