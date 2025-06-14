<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict'
]);
require_once 'includes/db.php';

// Vérification authentification
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

// Protection CSRF (si AJAX, passer le token en header ou POST, ici en POST)
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('CSRF invalide');
}
// Désactive le token après usage
unset($_SESSION['csrf_token']);

$user_id = $_SESSION['user_id'];
$contact_username = trim($_POST['to'] ?? '');
$plaintext = trim($_POST['message'] ?? '');

if (!$contact_username || !$plaintext || strlen($plaintext) > 2000) {
    http_response_code(400);
    exit('Entrée invalide');
}

// Récupère l'ID et la clé du destinataire
$stmt = $pdo->prepare('SELECT id, encryption_key FROM users WHERE username = ?');
$stmt->execute([$contact_username]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contact) {
    http_response_code(404);
    exit('Destinataire inconnu');
}
$contact_id = $contact['id'];
$key = base64_decode($contact['encryption_key']);
if (strlen($key) !== 32) {
    http_response_code(500);
    exit('Clé de chiffrement invalide');
}

// Génère IV aléatoire
$iv = random_bytes(16);

// Chiffre le message
$ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
if ($ciphertext === false) {
    http_response_code(500);
    exit('Erreur de chiffrement');
}

// Encode pour stockage
$ciphertext_b64 = base64_encode($ciphertext);
$iv_b64 = base64_encode($iv);

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content, iv) VALUES (?, ?, ?, ?)');
if (!$stmt->execute([$user_id, $contact_id, $ciphertext_b64, $iv_b64])) {
    http_response_code(500);
    exit('Erreur base de données');
}

http_response_code(200);
exit;
?>