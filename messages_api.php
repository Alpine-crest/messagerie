<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);
require_once 'includes/db.php';

header('Content-Type: application/json');

// Vérification authentification
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

// Récupère l'ID et la clé du user courant
$stmt = $pdo->prepare('SELECT id, encryption_key, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$self = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$self) {
    http_response_code(500);
    echo json_encode(['error' => 'Utilisateur courant introuvable']);
    exit;
}
$self_key = base64_decode($self['encryption_key']);

// Récupère l'ID et la clé du contact
$stmt = $pdo->prepare('SELECT id, encryption_key, username FROM users WHERE username = ?');
$stmt->execute([$contact_username]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    http_response_code(404);
    echo json_encode(['error' => 'Contact inconnu']);
    exit;
}
$contact_id = $contact['id'];
$contact_key = base64_decode($contact['encryption_key']);

// Récupère les messages
$stmt = $pdo->prepare(
    "SELECT m.id, m.sender_id, u.username AS sender_username, m.content, m.iv, m.sent_at
     FROM messages m
     JOIN users u ON m.sender_id = u.id
     WHERE (m.sender_id = :u1 AND m.receiver_id = :u2)
        OR (m.sender_id = :u2 AND m.receiver_id = :u1)
     ORDER BY m.sent_at ASC"
);
$stmt->execute(['u1' => $user_id, 'u2' => $contact_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Déchiffre chaque message
foreach ($messages as &$msg) {
    // Si je suis le destinataire => j'utilise ma clé, sinon j'utilise la clé du contact
    $key = ($msg['sender_id'] == $user_id) ? $contact_key : $self_key;
    $iv = base64_decode($msg['iv']);
    $ciphertext = base64_decode($msg['content']);
    $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $msg['content'] = ($decrypted !== false) ? $decrypted : '[Erreur de déchiffrement]';
    unset($msg['iv']); // Ne pas exposer l’IV côté JS
}

echo json_encode(['messages' => $messages]);
exit;
?>