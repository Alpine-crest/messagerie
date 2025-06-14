<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);

// Headers de sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// Redirige si non connecté
if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Messagerie</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <h2>Contacts</h2>
        <ul class="contact-list">
            <!-- À intégrer dynamiquement depuis la base -->
            <li><a href="chat.php?user=Paul">Paul</a></li>
            <li><a href="chat.php?user=Marie">Marie</a></li>
        </ul>
    </aside>
    <section class="content">
        <header>
            <h1>Bonjour, <?php echo $username; ?> !</h1>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </header>
        <div class="welcome">
            <p>Ceci est la page d'accueil après connexion. Sélectionne un contact pour discuter !</p>
        </div>
    </section>
</div>
</body>
</html>