<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html>
<head><title>User Dashboard</title></head>
<body>
    <h2>User Details</h2>
    <pre><?php print_r($user['claims']); ?></pre>
    <h3>ID Token</h3>
    <pre><?php echo $user['id_token']; ?></pre>
    <a href="index.php">Logout</a>
</body>
</html>
