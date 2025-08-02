<?php
/**
 * Session Cleanup Utility
 * Forcefully clear all session files and data
 */

echo "<h2>🧹 Session Cleanup Utility</h2>";

// Start session to get session path
session_start();
$sessionPath = session_save_path();
$sessionId = session_id();

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>⚠️ Session Information</h3>";
echo "<p><strong>Session Path:</strong> $sessionPath</p>";
echo "<p><strong>Current Session ID:</strong> $sessionId</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "</div>";

if (isset($_POST['cleanup'])) {
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>🚀 Cleanup Process</h3>";
    
    // Step 1: Clear current session
    $_SESSION = array();
    echo "<p>✅ Step 1: Session variables cleared</p>";
    
    // Step 2: Remove session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
        echo "<p>✅ Step 2: Session cookie removed</p>";
    }
    
    // Step 3: Destroy session
    session_destroy();
    echo "<p>✅ Step 3: Session destroyed</p>";
    
    // Step 4: Try to clean session files (if we have write access)
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        $files = glob($sessionPath . '/sess_*');
        $removed = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $removed++;
            }
        }
        echo "<p>✅ Step 4: Removed $removed session files</p>";
    } else {
        echo "<p>⚠️ Step 4: Cannot access session files (permission denied)</p>";
    }
    
    // Step 5: Clear any authentication cookies
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'PHPSESSID') !== false || 
            strpos($name, 'session') !== false ||
            strpos($name, 'auth') !== false ||
            strpos($name, 'login') !== false) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
    echo "<p>✅ Step 5: Authentication cookies cleared</p>";
    
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
    echo "<strong>🎉 Cleanup Complete!</strong><br>";
    echo "All session data and cookies have been cleared. You can now test fresh login.";
    echo "</div>";
    
    echo "<p><a href='../index.php' style='padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>🏠 Go to Login Page</a></p>";
    
    echo "</div>";
} else {
    // Show current session status
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>📊 Current Session Status</h3>";
    
    if (!empty($_SESSION)) {
        echo "<p><strong>Session Data Found:</strong></p>";
        echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 4px;'>";
        print_r($_SESSION);
        echo "</pre>";
    } else {
        echo "<p>✅ No session data found</p>";
    }
    
    if (!empty($_COOKIE)) {
        echo "<p><strong>Cookies Found:</strong></p>";
        echo "<ul>";
        foreach ($_COOKIE as $name => $value) {
            echo "<li><code>$name</code>: " . htmlspecialchars(substr($value, 0, 50)) . "...</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>✅ No cookies found</p>";
    }
    
    // Check session files
    if (is_dir($sessionPath)) {
        $files = glob($sessionPath . '/sess_*');
        echo "<p><strong>Session Files:</strong> " . count($files) . " files found</p>";
    }
    
    echo "</div>";
    
    // Cleanup form
    echo "<form method='post' style='margin: 20px 0;'>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🧹 Force Cleanup</h3>";
    echo "<p>This will forcefully clear all session data, cookies, and session files.</p>";
    echo "<p><strong>Warning:</strong> This will log out all users and clear all session data.</p>";
    echo "<button type='submit' name='cleanup' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;'>🗑️ Force Cleanup All Sessions</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='test_logout.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🧪 Test Logout</a>";
    echo "<a href='../index.php' style='padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>🏠 Back to Home</a>";
    echo "</div>";
}

?>
