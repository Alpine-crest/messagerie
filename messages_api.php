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
header('Content-Security-Policy: default-src \'none\';');
header('Content-Type: application/json');

// Authentification stricte
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];
$contact_username = $_GET['contact'] ?? '';

// Vérification que le contact existe ET est bien dans la liste
$stmt = $pdo->prepare('SELECT u.id, u.encryption_key, u.username FROM users u JOIN contacts c ON u.id = c.contact_id WHERE u.username = ? AND c.user_id = ?');
$stmt->execute([$contact_username, $user_id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    http_response_code(404);
    echo json_encode(['error' => 'Contact inconnu ou non autorisé']);
    exit;
}
$contact_id = $contact['id'];

// Clé du user courant
$stmt = $pdo->prepare('SELECT encryption_key, username FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$self = $stmt->fetch(PDO::FETCH_ASSOC);
$self_key = base64_decode($self['encryption_key']);
$contact_key = base64_decode($contact['encryption_key']);

// Récupère les messages uniquement entre user et contact
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
    $key = ($msg['sender_id'] == $user_id) ? $contact_key : $self_key;
    $iv = base64_decode($msg['iv']);
    $ciphertext = base64_decode($msg['content']);
    $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    // Empêche le XSS (output safe)
    $msg['content'] = htmlspecialchars($decrypted !== false ? $decrypted : '[Erreur de déchiffrement]', ENT_QUOTES, 'UTF-8');
    unset($msg['iv']);
}

echo json_encode(['messages' => $messages]);
exit;
?>