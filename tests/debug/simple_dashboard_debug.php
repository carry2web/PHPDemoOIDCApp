<?php
// Simple dashboard debug - bypassing complex includes
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Dashboard Debug</h1>";

session_start();

echo "<h2>1. Session Status</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active<br>";
} else {
    echo "❌ No active session<br>";
}

echo "<h2>2. Session Data</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>3. Authentication Check</h2>";
if (empty($_SESSION['email'])) {
    echo "❌ User not authenticated - no email in session<br>";
    echo "<a href='index.php'>Go to Login</a>";
} else {
    echo "✅ User authenticated: " . $_SESSION['email'] . "<br>";
    echo "User Type: " . ($_SESSION['user_type'] ?? 'unknown') . "<br>";
    echo "Name: " . ($_SESSION['name'] ?? 'unknown') . "<br>";
}

echo "<h2>4. Testing Includes</h2>";

try {
    echo "Testing vendor autoload...<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Vendor autoload OK<br>";
} catch (Exception $e) {
    echo "❌ Vendor autoload failed: " . $e->getMessage() . "<br>";
}

try {
    echo "Testing config helper...<br>";
    require_once __DIR__ . '/lib/config_helper.php';
    echo "✅ Config helper OK<br>";
} catch (Exception $e) {
    echo "❌ Config helper failed: " . $e->getMessage() . "<br>";
}

try {
    echo "Testing OIDC...<br>";
    require_once __DIR__ . '/lib/oidc.php';
    echo "✅ OIDC OK<br>";
} catch (Exception $e) {
    echo "❌ OIDC failed: " . $e->getMessage() . "<br>";
}

try {
    echo "Testing AWS helper...<br>";
    require_once __DIR__ . '/lib/aws_helper.php';
    echo "✅ AWS helper OK<br>";
} catch (Exception $e) {
    echo "❌ AWS helper failed: " . $e->getMessage() . "<br>";
}

try {
    echo "Testing logger...<br>";
    require_once __DIR__ . '/lib/logger.php';
    echo "✅ Logger OK<br>";
} catch (Exception $e) {
    echo "❌ Logger failed: " . $e->getMessage() . "<br>";
}

echo "<h2>5. If everything works, the real dashboard should work</h2>";
echo "<a href='dashboard.php'>Try Real Dashboard</a>";
?>
