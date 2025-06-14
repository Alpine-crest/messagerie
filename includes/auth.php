<?php
// Vérifie si l'utilisateur est connecté, sinon redirige vers la page de login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>