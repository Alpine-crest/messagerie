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
    <title>Xion — Messagerie Sécurisée</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/xion.css">
</head>
<body>
<header class="xion-header">
    <div class="xion-header__container">
        <span class="xion-logo">Xion</span>
        <nav>
            <a href="login.php" class="xion-nav-link">Connexion</a>
            <a href="register.php" class="xion-nav-link">Inscription</a>
        </nav>
    </div>
</header>
<main class="xion-main">
    <section class="xion-hero">
        <h1 class="xion-title">Bienvenue sur <span class="xion-logo">Xion</span></h1>
        <p class="xion-desc">La messagerie privée, rapide et ultra-sécurisée.<br>
        Chiffrement de bout en bout, confidentialité garantie, inspiration GitHub.</p>
        <div class="xion-actions">
            <a class="xion-btn xion-btn--primary" href="register.php">Créer un compte</a>
            <a class="xion-btn" href="login.php">Déjà inscrit ? Connexion</a>
        </div>
    </section>
    <section class="xion-info">
        <div class="xion-card">
            <h2>🔒 Sécurité avant tout</h2>
            <p>Vos messages sont chiffrés et ne sont visibles que par vous et vos contacts.</p>
        </div>
        <div class="xion-card">
            <h2>⚡ Simplicité</h2>
            <p>Une interface épurée, inspirée de GitHub, pour rester concentré sur l’essentiel : vos discussions.</p>
        </div>
        <div class="xion-card">
            <h2>🌙 Look moderne</h2>
            <p>Mode sombre, responsive, agréable de jour comme de nuit.</p>
        </div>
    </section>
</main>
<footer class="xion-footer">
    <span>&copy; 2025 Xion</span>
</footer>
</body>
</html>