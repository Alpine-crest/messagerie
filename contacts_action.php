<?php
session_start();
require_once 'includes/db.php';

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('UPDATE users SET last_active = NOW() WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$contact_username = trim($_GET['contact'] ?? '');

if (!$contact_username) {
    header('Location: home.php?error=Contact invalide');
    exit;
}

// Récupère l'ID du contact
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$contact_username]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    header('Location: home.php?error=Utilisateur inconnu');
    exit;
}

$contact_id = $contact['id'];

if ($action === 'add') {
    // Ne pas ajouter deux fois le même contact
    $stmt = $pdo->prepare('SELECT 1 FROM contacts WHERE user_id = ? AND contact_id = ?');
    $stmt->execute([$user_id, $contact_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO contacts (user_id, contact_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $contact_id]);
    }
    header('Location: home.php?success=Contact ajouté');
    exit;
}

if ($action === 'remove') {
    $stmt = $pdo->prepare('DELETE FROM contacts WHERE user_id = ? AND contact_id = ?');
    $stmt->execute([$user_id, $contact_id]);
    header('Location: home.php?success=Contact supprimé');
    exit;
}

header('Location: home.php');
exit;
?>