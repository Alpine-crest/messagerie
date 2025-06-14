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
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\';');

if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// Met à jour le statut actif
$stmt = $pdo->prepare('UPDATE users SET last_active = NOW() WHERE id = ?');
$stmt->execute([$user_id]);

// Récupère contacts + statuts
$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.last_active
     FROM contacts c
     JOIN users u ON c.contact_id = u.id
     WHERE c.user_id = ?
     ORDER BY u.username ASC"
);
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function is_online($last_active) {
    if (!$last_active) return false;
    return (strtotime($last_active) > (time() - 120));
}

$contact_username = $_GET['user'] ?? '';
if ($contact_username && !in_array($contact_username, array_column($contacts, 'username'))) {
    header('Location: chat.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chat - Messagerie</title>
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
                        <a href="chat.php?user=<?php echo urlencode($contact['username']); ?>"
                           <?php if ($contact['username'] === $contact_username) echo 'class="selected"'; ?>>
                            <?php echo htmlspecialchars($contact['username']); ?>
                        </a>
                        <span class="status <?php echo is_online($contact['last_active']) ? 'online' : 'offline'; ?>">
                            <?php echo is_online($contact['last_active']) ? '● En ligne' : '○ Hors ligne'; ?>
                        </span>
                        <a href="contacts_action.php?action=remove&contact=<?php echo urlencode($contact['username']); ?>"
                           class="remove-contact" onclick="return confirm('Retirer ce contact ?');">🗑️</a>
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
            <h1>Chat avec <?php echo htmlspecialchars($contact_username ?: "…"); ?></h1>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </header>
        <div id="chat-messages" class="chat-messages" aria-live="polite"></div>
        <form id="chat-form" class="chat-form" method="post" action="send_message.php" autocomplete="off" novalidate>
            <input type="hidden" name="to" id="chat-to" value="<?php echo htmlspecialchars($contact_username); ?>">
            <input type="hidden" name="csrf_token" id="csrf-token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="text" name="message" id="message-input" maxlength="2000" placeholder="Ecris ton message..." required autocomplete="off">
            <button type="submit">Envoyer</button>
        </form>
        <div id="chat-error" class="error" style="display:none;"></div>
    </section>
</div>
<script>
window.APP_CHAT = {
    contact: "<?php echo addslashes($contact_username); ?>",
    myUsername: "<?php echo addslashes($_SESSION['username']); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>"
};
</script>
<script src="assets/script.js"></script>
</body>
</html>