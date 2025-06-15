<?php
header('Content-Type: application/javascript; charset=UTF-8');
session_start();
$contact_username = isset($_GET['contact']) ? $_GET['contact'] : '';
$csrf_token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>
window.APP_CHAT = {
    contact: "<?php echo addslashes($contact_username); ?>",
    myUsername: "<?php echo addslashes($username); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>"
};