<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);
require_once 'includes/db.php';

header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\';');

if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.last_active
     FROM contacts c
     JOIN users u ON c.contact_id = u.id
     WHERE c.user_id = ?"
);
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function is_online($last_active) {
    if (!$last_active) return false;
    return (strtotime($last_active) > (time() - 120));
}
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
                        <span class="status <?php echo is_online($contact['last_active']) ? 'online' : 'offline'; ?>">
                            <?php echo is_online($contact['last_active']) ? '● En ligne' : '○ Hors ligne'; ?>
                     </span>
                     <a href="contacts_action.php?action=remove&contact=<?php echo urlencode($contact['username']); ?>" class="remove-contact" onclick="return confirm('Retirer ce contact ?');">🗑️</a>
                 </li>
              <?php endforeach; ?>
          <?php else: ?>
             <li>Aucun contact trouvé.</li>
          <?php endif; ?>
        </ul>
        <form action="contacts_action.php" method="get" class="add-contact-form" autocomplete="off">
          <input type="hidden" name="action" value="add">
          <input type="text" name="contact" placeholder="Ajouter un pseudo" required pattern="[a-zA-Z0-9_]{3,50}">
            <button type="submit">Ajouter</button>
        </form>
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