<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare(
    "SELECT u.id, u.username
     FROM contacts c
     JOIN users u ON c.contact_id = u.id
     WHERE c.user_id = ?"
);
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <?php if ($contacts): ?>
                <?php foreach ($contacts as $contact): ?>
                    <li>
                        <a href="chat.php?user=<?php echo urlencode($contact['username']); ?>">
                            <?php echo htmlspecialchars($contact['username']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Aucun contact trouvé.</li>
            <?php endif; ?>
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