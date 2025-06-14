<?php
session_start();
require_once 'includes/auth.php';
require_login();

$user = $_SESSION['username'];
$contact = $_GET['user'] ?? null;
if (!$contact) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Discussion avec <?php echo htmlspecialchars($contact); ?> - Messagerie</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/script.js" defer></script>
</head>
<body>
    <div class="main-layout">
        <aside class="sidebar">
            <h2>Contacts</h2>
            <ul class="contact-list">
                <!-- À compléter dynamiquement -->
                <li><a href="chat.php?user=Paul">Paul</a></li>
                <li><a href="chat.php?user=Marie">Marie</a></li>
            </ul>
        </aside>
        <section class="content">
            <header>
                <h1>Discussion avec <?php echo htmlspecialchars($contact); ?></h1>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </header>
            <div id="messages" class="messages">
                <!-- Les messages s’afficheront ici (à compléter avec AJAX/JS) -->
            </div>
            <form id="sendForm" action="send_message.php" method="post" class="send-form">
                <input type="hidden" name="to" value="<?php echo htmlspecialchars($contact); ?>">
                <input type="text" name="message" id="message" placeholder="Tape ton message…" autocomplete="off" required>
                <button class="btn" type="submit">Envoyer</button>
            </form>
        </section>
    </div>
</body>
</html>