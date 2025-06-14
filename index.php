<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie Sécurisée - Accueil</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container center">
        <h1>Bienvenue sur la messagerie ultra sécurisée !</h1>
        <p>Inspirée de WhatsApp, Telegram et Signal.<br>
        Chiffrement, confidentialité et simplicité.</p>
        <div class="actions">
            <a class="btn" href="login.php">Connexion</a>
            <a class="btn" href="register.php">Inscription</a>
        </div>
        <div class="info">
            <p>Crée un compte pour découvrir l'appli !</p>
        </div>
    </div>
</body>
</html>