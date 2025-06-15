<?php
header('Content-Type: application/javascript');
session_start();
$contact_username = $_GET['contact'] ?? '';
$csrf_token = $_SESSION['csrf_token'] ?? '';
$username = $_SESSION['username'] ?? '';
?>
window.APP_CHAT = {
    contact: "<?php echo addslashes($contact_username); ?>",
    myUsername: "<?php echo addslashes($username); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>"
};