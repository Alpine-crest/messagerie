<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$contact_username = trim($_POST['to'] ?? '');
$plaintext = trim($_POST['message'] ?? '');

if (!$contact_username || !$plaintext) {
    http_response_code(400);
    exit;
}

// Récupère l'ID et la clé du destinataire
$stmt = $pdo->prepare('SELECT id, encryption_key FROM users WHERE username = ?');
$stmt->execute([$contact_username]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    http_response_code(404);
    exit;
}
$contact_id = $contact['id'];
$key = base64_decode($contact['encryption_key']); // clé binaire

// Génère IV aléatoire
$iv = openssl_random_pseudo_bytes(16);

// Chiffre le message
$content = openssl_encrypt($plaintext, 'AES-256-CBC', $key, 0, $iv);
$iv_b64 = base64_encode($iv);

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content, iv) VALUES (?, ?, ?, ?)');
$stmt->execute([$user_id, $contact_id, $content, $iv_b64]);

http_response_code(200);
exit;
?>