<?php
require_once 'lib/security_helper.php';

echo "<h1>Security Features Test</h1>";

// Test CSRF Token Generation
echo "<h2>CSRF Token Test</h2>";
SecurityHelper::getInstance()->startSecureSession();
$token = SecurityHelper::getInstance()->generateCSRFToken();
echo "Generated CSRF Token: " . htmlspecialchars($token) . "<br>";
echo "Is Valid: " . (SecurityHelper::getInstance()->validateCSRFToken($token) ? "Yes" : "No") . "<br>";

// Test Input Validation
echo "<h2>Input Validation Test</h2>";
$testEmail = "test@example.com";
$invalidEmail = "not-an-email";
echo "Valid Email (" . htmlspecialchars($testEmail) . "): " . (SecurityHelper::getInstance()->validateEmail($testEmail) ? "Pass" : "Fail") . "<br>";
echo "Invalid Email (" . htmlspecialchars($invalidEmail) . "): " . (SecurityHelper::getInstance()->validateEmail($invalidEmail) ? "Pass" : "Fail") . "<br>";

$validName = "John Doe";
$invalidName = "<script>alert('xss')</script>";
echo "Valid Name (" . htmlspecialchars($validName) . "): " . (SecurityHelper::getInstance()->validateName($validName) ? "Pass" : "Fail") . "<br>";
echo "Invalid Name (XSS attempt): " . (SecurityHelper::getInstance()->validateName($invalidName) ? "Pass" : "Fail") . "<br>";

// Test File Upload Validation
echo "<h2>File Upload Validation Test</h2>";
$validFile = ['name' => 'document.pdf', 'type' => 'application/pdf', 'size' => 1024];
$invalidFile = ['name' => 'script.php', 'type' => 'application/x-php', 'size' => 1024];
echo "Valid PDF file: " . (SecurityHelper::getInstance()->validateFileUpload($validFile) ? "Pass" : "Fail") . "<br>";
echo "Invalid PHP file: " . (SecurityHelper::getInstance()->validateFileUpload($invalidFile) ? "Pass" : "Fail") . "<br>";

// Test Rate Limiting
echo "<h2>Rate Limiting Test</h2>";
$canSubmit1 = SecurityHelper::getInstance()->checkRateLimit('apply_agent', 3, 3600);
$canSubmit2 = SecurityHelper::getInstance()->checkRateLimit('apply_agent', 3, 3600);
$canSubmit3 = SecurityHelper::getInstance()->checkRateLimit('apply_agent', 3, 3600);
$canSubmit4 = SecurityHelper::getInstance()->checkRateLimit('apply_agent', 3, 3600); // Should fail
echo "First submit: " . ($canSubmit1 ? "Allowed" : "Blocked") . "<br>";
echo "Second submit: " . ($canSubmit2 ? "Allowed" : "Blocked") . "<br>";
echo "Third submit: " . ($canSubmit3 ? "Allowed" : "Blocked") . "<br>";
echo "Fourth submit (should be blocked): " . ($canSubmit4 ? "Allowed" : "Blocked") . "<br>";

echo "<h2>Security Test Complete</h2>";
echo "<p>All security features have been tested.</p>";
?>
