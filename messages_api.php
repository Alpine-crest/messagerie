<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];
$contact_username = $_GET['contact'] ?? '';
if (!$contact_username) {
    http_response_code(400);
    echo json_encode(['error' => 'Contact manquant']);
    exit;
}

// Récupère l'ID du contact
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$contact_username]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    http_response_code(404);
    echo json_encode(['error' => 'Contact inconnu']);
    exit;
}
$contact_id = $contact['id'];

// Récupère les messages
$stmt = $pdo->prepare(
    "SELECT m.id, m.sender_id, u.username AS sender_username, m.content, m.sent_at
     FROM messages m
     JOIN users u ON m.sender_id = u.id
     WHERE (m.sender_id = :u1 AND m.receiver_id = :u2)
        OR (m.sender_id = :u2 AND m.receiver_id = :u1)
     ORDER BY m.sent_at ASC"
);
$stmt->execute(['u1' => $user_id, 'u2' => $contact_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// (Optionnel) Déchiffrement ici si tu stockes les messages chiffrés

echo json_encode(['messages' => $messages]);
exit;
?>