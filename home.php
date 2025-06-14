<?php
session_start();
require_once 'includes/auth.php';
require_login(); // Redirige si non connecté

// Récupérer les infos utilisateur et les contacts ici (à compléter)
$user = $_SESSION['username']; // Exemple

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
                <!-- Liste des contacts ici -->
                <li><a href="chat.php?user=Paul">Paul</a></li>
                <li><a href="chat.php?user=Marie">Marie</a></li>
            </ul>
        </aside>
        <section class="content">
            <header>
                <h1>Bonjour, <?php echo htmlspecialchars($user) ?> !</h1>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </header>
            <div class="welcome">
                <p>Ceci est la page d'accueil après connexion. Sélectionne un contact pour discuter !</p>
            </div>
        </section>
    </div>
</body>
</html>