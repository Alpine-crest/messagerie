<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$contact_username = trim($_POST['to'] ?? '');
$content = trim($_POST['message'] ?? '');

if (!$contact_username || !$content) {
    header("Location: chat.php?user=" . urlencode($contact_username) . "&error=Message ou destinataire manquant");
    exit;
}

// Récupère l'ID du contact
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$contact_username]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    header("Location: chat.php?user=" . urlencode($contact_username) . "&error=Contact inconnu");
    exit;
}
$contact_id = $contact['id'];

// (Optionnel) Chiffrement ici

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)');
$stmt->execute([$user_id, $contact_id, $content]);

header("Location: chat.php?user=" . urlencode($contact_username));
exit;
?>